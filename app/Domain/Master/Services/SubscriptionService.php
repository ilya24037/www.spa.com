<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Models\MasterSubscription;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Сервис управления подписками мастеров
 */
class SubscriptionService
{
    /**
     * Создать новую подписку
     */
    public function createSubscription(
        MasterProfile $master,
        SubscriptionPlan $plan,
        int $periodMonths = 1,
        array $paymentData = []
    ): MasterSubscription {
        // Деактивируем старые подписки
        $this->deactivateOldSubscriptions($master);

        // Рассчитываем стоимость
        $price = $plan->calculateTotal($periodMonths);

        // Создаем подписку
        $subscription = MasterSubscription::create([
            'master_profile_id' => $master->id,
            'plan' => $plan,
            'status' => SubscriptionStatus::PENDING,
            'price' => $price,
            'period_months' => $periodMonths,
            'payment_method' => $paymentData['method'] ?? null,
            'transaction_id' => $paymentData['transaction_id'] ?? null,
            'metadata' => $paymentData['metadata'] ?? null,
        ]);

        // Если это бесплатный план, сразу активируем
        if ($plan === SubscriptionPlan::FREE) {
            $subscription->activate();
        }

        return $subscription;
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
        $this->deactivateOldSubscriptions($master);

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

        // Обновляем статус мастера
        $this->updateMasterStatus($master, $subscription);

        return $subscription;
    }

    /**
     * Активировать подписку после оплаты
     */
    public function activateSubscription(
        MasterSubscription $subscription,
        array $paymentData = []
    ): void {
        if ($subscription->status === SubscriptionStatus::ACTIVE) {
            return; // Уже активна
        }

        // Обновляем платежные данные
        if (!empty($paymentData)) {
            $subscription->update([
                'payment_method' => $paymentData['method'] ?? $subscription->payment_method,
                'transaction_id' => $paymentData['transaction_id'] ?? $subscription->transaction_id,
                'metadata' => array_merge($subscription->metadata ?? [], $paymentData['metadata'] ?? []),
            ]);
        }

        // Активируем
        $subscription->activate();

        // Обновляем статус мастера
        $this->updateMasterStatus($subscription->masterProfile, $subscription);
    }

    /**
     * Продлить подписку
     */
    public function renewSubscription(
        MasterSubscription $subscription,
        int $periodMonths = null,
        array $paymentData = []
    ): void {
        $periodMonths = $periodMonths ?? $subscription->period_months;
        
        // Обновляем цену
        $newPrice = $subscription->plan->calculateTotal($periodMonths);
        $subscription->price = $newPrice;

        // Обновляем платежные данные
        if (!empty($paymentData)) {
            $subscription->update([
                'payment_method' => $paymentData['method'] ?? $subscription->payment_method,
                'transaction_id' => $paymentData['transaction_id'] ?? null,
            ]);
        }

        // Продлеваем
        $subscription->renew($periodMonths);

        // Обновляем статус мастера
        $this->updateMasterStatus($subscription->masterProfile, $subscription);
    }

    /**
     * Изменить план подписки
     */
    public function changePlan(
        MasterSubscription $subscription,
        SubscriptionPlan $newPlan
    ): void {
        $oldPlan = $subscription->plan;

        // Проверяем возможность изменения
        if ($oldPlan === $newPlan) {
            throw new \Exception('Выбран текущий план');
        }

        // Рассчитываем разницу в цене (пропорционально)
        $remainingDays = $subscription->getRemainingDays();
        $totalDays = $subscription->period_months * 30;
        
        if ($remainingDays > 0 && $totalDays > 0) {
            $remainingPercent = $remainingDays / $totalDays;
            $oldPlanCredit = $subscription->price * $remainingPercent;
            $newPlanCost = $newPlan->calculateTotal($subscription->period_months) * $remainingPercent;
            $difference = $newPlanCost - $oldPlanCredit;

            // Сохраняем информацию о доплате/возврате
            $subscription->metadata = array_merge($subscription->metadata ?? [], [
                'plan_change' => [
                    'from' => $oldPlan->value,
                    'to' => $newPlan->value,
                    'difference' => $difference,
                    'date' => now()->toDateTimeString(),
                ],
            ]);
        }

        // Меняем план
        $subscription->changePlan($newPlan);

        // Обновляем статус мастера
        $this->updateMasterStatus($subscription->masterProfile, $subscription);
    }

    /**
     * Отменить подписку
     */
    public function cancelSubscription(
        MasterSubscription $subscription,
        string $reason = null,
        bool $immediate = false
    ): void {
        if ($immediate) {
            // Немедленная отмена
            $subscription->cancel($reason);
            $this->updateMasterStatus($subscription->masterProfile, null);
        } else {
            // Отмена в конце периода
            $subscription->update(['auto_renew' => false]);
            $subscription->logHistory('cancel_scheduled', 'Запланирована отмена в конце периода');
        }
    }

    /**
     * Проверить и обновить истекшие подписки
     */
    public function checkExpirations(): int
    {
        $count = 0;
        
        MasterSubscription::active()
            ->chunk(100, function ($subscriptions) use (&$count) {
                foreach ($subscriptions as $subscription) {
                    $subscription->checkExpiration();
                    
                    if ($subscription->status === SubscriptionStatus::EXPIRED) {
                        $this->updateMasterStatus($subscription->masterProfile, null);
                        $count++;
                    }
                }
            });

        return $count;
    }

    /**
     * Отправить напоминания об истекающих подписках
     */
    public function sendExpirationReminders(): int
    {
        $count = 0;
        
        MasterSubscription::expiring(7)
            ->whereDoesntHave('history', function ($query) {
                $query->where('action', 'expiration_reminder')
                      ->where('created_at', '>', now()->subDays(3));
            })
            ->chunk(100, function ($subscriptions) use (&$count) {
                foreach ($subscriptions as $subscription) {
                    // Здесь бы отправлялось уведомление
                    // $this->notificationService->sendExpirationReminder($subscription);
                    
                    $subscription->logHistory('expiration_reminder', 'Отправлено напоминание об истечении');
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Получить активную подписку мастера
     */
    public function getActiveSubscription(MasterProfile $master): ?MasterSubscription
    {
        return $master->subscriptions()
            ->active()
            ->latest()
            ->first();
    }

    /**
     * Проверить лимиты подписки
     */
    public function checkLimit(
        MasterProfile $master,
        string $resource
    ): array {
        $subscription = $this->getActiveSubscription($master);
        
        if (!$subscription) {
            // Используем бесплатный план по умолчанию
            $limit = SubscriptionPlan::FREE->getLimit($resource);
        } else {
            $limit = $subscription->getLimit($resource);
        }

        // Получаем текущее количество
        $currentCount = $this->getCurrentResourceCount($master, $resource);

        return [
            'limit' => $limit,
            'current' => $currentCount,
            'remaining' => $limit === -1 ? -1 : max(0, $limit - $currentCount),
            'reached' => $limit !== -1 && $currentCount >= $limit,
            'percentage' => $limit === -1 ? 0 : min(100, ($currentCount / $limit) * 100),
        ];
    }

    /**
     * Получить статистику подписок
     */
    public function getStatistics(): array
    {
        return [
            'total' => MasterSubscription::count(),
            'active' => MasterSubscription::active()->count(),
            'trial' => MasterSubscription::where('status', SubscriptionStatus::TRIAL)->count(),
            'expired' => MasterSubscription::expired()->count(),
            'revenue' => [
                'total' => MasterSubscription::where('status', SubscriptionStatus::ACTIVE)->sum('price'),
                'monthly' => MasterSubscription::where('status', SubscriptionStatus::ACTIVE)
                    ->where('created_at', '>', now()->subMonth())
                    ->sum('price'),
            ],
            'by_plan' => SubscriptionPlan::getAllPlans()
                ->mapWithKeys(fn($plan) => [
                    $plan->value => MasterSubscription::where('plan', $plan)->count()
                ])
                ->toArray(),
            'churn_rate' => $this->calculateChurnRate(),
            'average_lifetime_value' => $this->calculateAverageLTV(),
        ];
    }

    /**
     * Деактивировать старые подписки
     */
    protected function deactivateOldSubscriptions(MasterProfile $master): void
    {
        $master->subscriptions()
            ->whereIn('status', [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIAL])
            ->update(['status' => SubscriptionStatus::CANCELLED]);
    }

    /**
     * Обновить статус мастера на основе подписки
     */
    protected function updateMasterStatus(MasterProfile $master, ?MasterSubscription $subscription): void
    {
        if (!$subscription || !$subscription->isActive()) {
            $master->update([
                'is_premium' => false,
                'premium_until' => null,
            ]);
            return;
        }

        $isPremium = in_array($subscription->plan, [
            SubscriptionPlan::PREMIUM,
            SubscriptionPlan::VIP,
        ]);

        $master->update([
            'is_premium' => $isPremium,
            'premium_until' => $subscription->getExpiryDate(),
        ]);
    }

    /**
     * Проверить, использовался ли пробный период
     */
    protected function hasUsedTrial(MasterProfile $master): bool
    {
        return $master->subscriptions()
            ->where('status', SubscriptionStatus::TRIAL)
            ->orWhere(function ($query) {
                $query->whereNotNull('trial_ends_at');
            })
            ->exists();
    }

    /**
     * Получить текущее количество ресурса
     */
    protected function getCurrentResourceCount(MasterProfile $master, string $resource): int
    {
        return match($resource) {
            'photos' => $master->photos()->count(),
            'videos' => $master->videos()->count(),
            'services' => $master->services()->count(),
            'work_zones' => $master->workZones()->count(),
            default => 0,
        };
    }

    /**
     * Рассчитать churn rate
     */
    protected function calculateChurnRate(): float
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $startCount = MasterSubscription::where('created_at', '<', $startOfMonth)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->count();

        if ($startCount === 0) {
            return 0;
        }

        $cancelledCount = MasterSubscription::whereBetween('cancelled_at', [$startOfMonth, $endOfMonth])
            ->count();

        return round(($cancelledCount / $startCount) * 100, 2);
    }

    /**
     * Рассчитать средний LTV
     */
    protected function calculateAverageLTV(): float
    {
        $avgMonths = MasterSubscription::whereIn('status', [
                SubscriptionStatus::ACTIVE,
                SubscriptionStatus::EXPIRED,
                SubscriptionStatus::CANCELLED,
            ])
            ->selectRaw('AVG(TIMESTAMPDIFF(MONTH, start_date, COALESCE(cancelled_at, end_date, NOW()))) as avg_months')
            ->value('avg_months') ?? 0;

        $avgPrice = MasterSubscription::whereIn('status', [
                SubscriptionStatus::ACTIVE,
                SubscriptionStatus::EXPIRED,
                SubscriptionStatus::CANCELLED,
            ])
            ->avg('price') ?? 0;

        return round($avgMonths * $avgPrice, 2);
    }
}