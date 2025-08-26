<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Models\MasterSubscription;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;

/**
 * Сервис управления основными операциями с подписками
 */
class SubscriptionManager
{
    /**
     * Создать новую подписку
     */
    public function create(
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
     * Отменить подписку
     */
    public function cancel(
        MasterSubscription $subscription,
        string $reason = null,
        bool $immediate = false
    ): void {
        if ($immediate) {
            // Немедленная отмена
            $subscription->cancel($reason);
        } else {
            // Отмена в конце периода
            $subscription->update(['auto_renew' => false]);
            $subscription->logHistory('cancel_scheduled', 'Запланирована отмена в конце периода');
        }
    }

    /**
     * Проверить и обновить истекшие подписки
     */
    public function processExpirations(): int
    {
        $count = 0;
        
        MasterSubscription::active()
            ->chunk(100, function ($subscriptions) use (&$count) {
                foreach ($subscriptions as $subscription) {
                    $subscription->checkExpiration();
                    
                    if ($subscription->status === SubscriptionStatus::EXPIRED) {
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
     * Деактивировать старые подписки
     */
    public function deactivateOldSubscriptions(MasterProfile $master): void
    {
        $master->subscriptions()
            ->whereIn('status', [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIAL])
            ->update(['status' => SubscriptionStatus::CANCELLED]);
    }
}