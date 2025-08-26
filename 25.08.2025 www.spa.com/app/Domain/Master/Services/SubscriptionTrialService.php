<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Models\MasterSubscription;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;

/**
 * Сервис управления пробными периодами подписок
 */
class SubscriptionTrialService
{
    private SubscriptionManager $subscriptionManager;

    public function __construct(SubscriptionManager $subscriptionManager)
    {
        $this->subscriptionManager = $subscriptionManager;
    }

    /**
     * Начать пробный период
     */
    public function startTrial(
        MasterProfile $master,
        SubscriptionPlan $plan = SubscriptionPlan::PREMIUM,
        int $days = 14
    ): MasterSubscription {
        // Проверяем, был ли уже пробный период
        if ($this->hasUsedTrial($master)) {
            throw new \Exception('Пробный период уже был использован');
        }

        // Деактивируем старые подписки
        $this->subscriptionManager->deactivateOldSubscriptions($master);

        // Создаем подписку с пробным периодом
        $subscription = MasterSubscription::create([
            'master_profile_id' => $master->id,
            'plan' => $plan,
            'status' => SubscriptionStatus::TRIAL,
            'price' => 0,
            'period_months' => 0,
            'start_date' => now(),
            'trial_ends_at' => now()->addDays($days),
        ]);

        $subscription->logHistory('trial_started', "Начат пробный период на {$days} дней");

        return $subscription;
    }

    /**
     * Проверить, можно ли начать пробный период
     */
    public function canStartTrial(MasterProfile $master): bool
    {
        return !$this->hasUsedTrial($master);
    }

    /**
     * Получить информацию о пробном периоде
     */
    public function getTrialInfo(MasterProfile $master): array
    {
        $trialSubscription = $master->subscriptions()
            ->where('status', SubscriptionStatus::TRIAL)
            ->orWhereNotNull('trial_ends_at')
            ->first();

        if (!$trialSubscription) {
            return [
                'available' => true,
                'used' => false,
                'remaining_days' => null,
            ];
        }

        $remainingDays = $trialSubscription->trial_ends_at ? 
            now()->diffInDays($trialSubscription->trial_ends_at, false) : 0;

        return [
            'available' => false,
            'used' => true,
            'subscription_id' => $trialSubscription->id,
            'plan' => $trialSubscription->plan,
            'started_at' => $trialSubscription->start_date,
            'ends_at' => $trialSubscription->trial_ends_at,
            'remaining_days' => max(0, $remainingDays),
            'is_active' => $trialSubscription->status === SubscriptionStatus::TRIAL,
        ];
    }

    /**
     * Конвертировать пробный период в полную подписку
     */
    public function convertTrialToSubscription(
        MasterSubscription $trialSubscription,
        int $periodMonths,
        array $paymentData = []
    ): void {
        if ($trialSubscription->status !== SubscriptionStatus::TRIAL) {
            throw new \Exception('Подписка не является пробной');
        }

        // Рассчитываем стоимость полной подписки
        $price = $trialSubscription->plan->calculateTotal($periodMonths);

        // Обновляем подписку
        $trialSubscription->update([
            'status' => SubscriptionStatus::PENDING,
            'price' => $price,
            'period_months' => $periodMonths,
            'payment_method' => $paymentData['method'] ?? null,
            'transaction_id' => $paymentData['transaction_id'] ?? null,
            'metadata' => array_merge($trialSubscription->metadata ?? [], $paymentData['metadata'] ?? []),
            'trial_ends_at' => null, // Убираем ограничение пробного периода
        ]);

        $trialSubscription->logHistory('trial_converted', "Пробный период конвертирован в подписку на {$periodMonths} мес.");
    }

    /**
     * Завершить пробный период (истек или отменен)
     */
    public function endTrial(MasterSubscription $trialSubscription, string $reason = 'expired'): void
    {
        if ($trialSubscription->status !== SubscriptionStatus::TRIAL) {
            return;
        }

        $trialSubscription->update([
            'status' => SubscriptionStatus::EXPIRED,
            'end_date' => now(),
        ]);

        $trialSubscription->logHistory('trial_ended', "Пробный период завершен: {$reason}");
    }

    /**
     * Проверить, использовался ли пробный период
     */
    private function hasUsedTrial(MasterProfile $master): bool
    {
        return $master->subscriptions()
            ->where('status', SubscriptionStatus::TRIAL)
            ->orWhere(function ($query) {
                $query->whereNotNull('trial_ends_at');
            })
            ->exists();
    }
}