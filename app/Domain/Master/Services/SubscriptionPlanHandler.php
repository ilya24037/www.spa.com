<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterSubscription;
use App\Enums\SubscriptionPlan;

/**
 * Сервис управления планами подписок
 */
class SubscriptionPlanHandler
{
    /**
     * Изменить план подписки
     */
    public function changePlan(
        MasterSubscription $subscription,
        SubscriptionPlan $newPlan
    ): array {
        $oldPlan = $subscription->plan;

        // Проверяем возможность изменения
        if ($oldPlan === $newPlan) {
            throw new \Exception('Выбран текущий план');
        }

        // Рассчитываем разницу в цене (пропорционально)
        $priceDifference = $this->calculatePlanChangeCost($subscription, $newPlan);

        // Сохраняем информацию о смене плана
        $changeInfo = [
            'from' => $oldPlan->value,
            'to' => $newPlan->value,
            'difference' => $priceDifference['difference'],
            'proration_type' => $priceDifference['type'],
            'date' => now()->toDateTimeString(),
        ];

        $subscription->metadata = array_merge($subscription->metadata ?? [], [
            'plan_change' => $changeInfo,
        ]);

        // Меняем план
        $subscription->changePlan($newPlan);

        $subscription->logHistory('plan_changed', "План изменен с {$oldPlan->value} на {$newPlan->value}");

        return $changeInfo;
    }

    /**
     * Получить доступные планы для перехода
     */
    public function getAvailablePlans(MasterSubscription $subscription): array
    {
        $currentPlan = $subscription->plan;
        $allPlans = SubscriptionPlan::getAllPlans();
        
        return $allPlans->map(function($plan) use ($currentPlan, $subscription) {
            $changeCost = $plan !== $currentPlan ? 
                $this->calculatePlanChangeCost($subscription, $plan) : null;

            return [
                'plan' => $plan,
                'name' => $plan->getName(),
                'description' => $plan->getDescription(),
                'features' => $plan->getFeatures(),
                'price_monthly' => $plan->getMonthlyPrice(),
                'is_current' => $plan === $currentPlan,
                'can_switch' => $this->canSwitchToPlan($subscription, $plan),
                'change_cost' => $changeCost,
            ];
        })->toArray();
    }

    /**
     * Получить сравнение планов
     */
    public function comparePlans(SubscriptionPlan $currentPlan, SubscriptionPlan $targetPlan): array
    {
        $comparison = [];
        $features = array_unique(array_merge(
            array_keys($currentPlan->getFeatures()),
            array_keys($targetPlan->getFeatures())
        ));

        foreach ($features as $feature) {
            $currentValue = $currentPlan->getFeatures()[$feature] ?? null;
            $targetValue = $targetPlan->getFeatures()[$feature] ?? null;

            $comparison[$feature] = [
                'current' => $currentValue,
                'target' => $targetValue,
                'change' => $this->getFeatureChangeType($currentValue, $targetValue),
            ];
        }

        return [
            'features' => $comparison,
            'price_change' => $targetPlan->getMonthlyPrice() - $currentPlan->getMonthlyPrice(),
            'upgrade' => $targetPlan->getMonthlyPrice() > $currentPlan->getMonthlyPrice(),
        ];
    }

    /**
     * Рассчитать стоимость смены плана
     */
    private function calculatePlanChangeCost(MasterSubscription $subscription, SubscriptionPlan $newPlan): array
    {
        $remainingDays = $subscription->getRemainingDays();
        $totalDays = $subscription->period_months * 30;
        
        if ($remainingDays <= 0 || $totalDays <= 0) {
            return [
                'difference' => 0,
                'type' => 'no_proration',
                'remaining_days' => 0,
            ];
        }

        $remainingPercent = $remainingDays / $totalDays;
        $oldPlanCredit = $subscription->price * $remainingPercent;
        $newPlanCost = $newPlan->calculateTotal($subscription->period_months) * $remainingPercent;
        $difference = $newPlanCost - $oldPlanCredit;

        return [
            'difference' => round($difference, 2),
            'type' => $difference > 0 ? 'charge' : ($difference < 0 ? 'credit' : 'no_change'),
            'old_plan_credit' => round($oldPlanCredit, 2),
            'new_plan_cost' => round($newPlanCost, 2),
            'remaining_days' => $remainingDays,
            'remaining_percent' => round($remainingPercent * 100, 1),
        ];
    }

    /**
     * Проверить, можно ли переключиться на план
     */
    private function canSwitchToPlan(MasterSubscription $subscription, SubscriptionPlan $targetPlan): bool
    {
        // Нельзя переключиться на тот же план
        if ($subscription->plan === $targetPlan) {
            return false;
        }

        // Проверяем статус подписки
        if (!in_array($subscription->status, [\App\Enums\SubscriptionStatus::ACTIVE, \App\Enums\SubscriptionStatus::TRIAL])) {
            return false;
        }

        // Дополнительные проверки для специальных планов
        if ($targetPlan === SubscriptionPlan::VIP) {
            // VIP план доступен только для проверенных мастеров
            return $subscription->masterProfile->is_verified ?? false;
        }

        return true;
    }

    /**
     * Определить тип изменения функции
     */
    private function getFeatureChangeType($currentValue, $targetValue): string
    {
        if ($currentValue === $targetValue) {
            return 'no_change';
        }

        if ($currentValue === null || $currentValue === false) {
            return 'added';
        }

        if ($targetValue === null || $targetValue === false) {
            return 'removed';
        }

        if (is_numeric($currentValue) && is_numeric($targetValue)) {
            return $targetValue > $currentValue ? 'increased' : 'decreased';
        }

        return 'changed';
    }
}