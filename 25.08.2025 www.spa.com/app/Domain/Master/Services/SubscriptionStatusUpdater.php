<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Models\MasterSubscription;
use App\Enums\SubscriptionPlan;

/**
 * Сервис обновления статуса мастера на основе подписки
 */
class SubscriptionStatusUpdater
{
    /**
     * Обновить статус мастера на основе подписки
     */
    public function updateMasterStatus(MasterProfile $master, ?MasterSubscription $subscription): void
    {
        if (!$subscription || !$subscription->isActive()) {
            $this->clearPremiumStatus($master);
            return;
        }

        $this->setPremiumStatus($master, $subscription);
    }

    /**
     * Массово обновить статусы мастеров
     */
    public function bulkUpdateStatuses(): int
    {
        $updatedCount = 0;
        
        // Обновляем мастеров с активными подписками
        MasterSubscription::with('masterProfile')
            ->active()
            ->chunk(100, function ($subscriptions) use (&$updatedCount) {
                foreach ($subscriptions as $subscription) {
                    $this->updateMasterStatus($subscription->masterProfile, $subscription);
                    $updatedCount++;
                }
            });

        // Очищаем статус у мастеров без активных подписок
        MasterProfile::where('is_premium', true)
            ->whereDoesntHave('subscriptions', function ($query) {
                $query->active();
            })
            ->chunk(100, function ($masters) use (&$updatedCount) {
                foreach ($masters as $master) {
                    $this->clearPremiumStatus($master);
                    $updatedCount++;
                }
            });

        return $updatedCount;
    }

    /**
     * Получить информацию о статусе мастера
     */
    public function getMasterStatusInfo(MasterProfile $master): array
    {
        $activeSubscription = $master->subscriptions()->active()->first();
        
        return [
            'is_premium' => $master->is_premium,
            'premium_until' => $master->premium_until,
            'has_active_subscription' => (bool) $activeSubscription,
            'subscription' => $activeSubscription ? [
                'id' => $activeSubscription->id,
                'plan' => $activeSubscription->plan->value,
                'status' => $activeSubscription->status->value,
                'expires_at' => $activeSubscription->getExpiryDate(),
                'days_remaining' => $activeSubscription->getRemainingDays(),
                'auto_renew' => $activeSubscription->auto_renew,
            ] : null,
            'benefits' => $this->getMasterBenefits($master, $activeSubscription),
        ];
    }

    /**
     * Проверить соответствие статуса и подписки
     */
    public function validateMasterStatus(MasterProfile $master): array
    {
        $statusInfo = $this->getMasterStatusInfo($master);
        $issues = [];
        
        // Проверяем несоответствия
        if ($master->is_premium && !$statusInfo['has_active_subscription']) {
            $issues[] = 'Мастер помечен как премиум, но нет активной подписки';
        }
        
        if (!$master->is_premium && $statusInfo['has_active_subscription']) {
            $subscription = $statusInfo['subscription'];
            $isPremiumPlan = in_array($subscription['plan'], ['premium', 'vip']);
            
            if ($isPremiumPlan) {
                $issues[] = 'Есть активная премиум подписка, но мастер не помечен как премиум';
            }
        }
        
        if ($master->premium_until && $master->premium_until->isPast() && $master->is_premium) {
            $issues[] = 'Срок премиум статуса истек, но флаг не обновлен';
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'suggested_fix' => empty($issues) ? null : 'Обновить статус мастера',
        ];
    }

    /**
     * Установить премиум статус
     */
    private function setPremiumStatus(MasterProfile $master, MasterSubscription $subscription): void
    {
        $isPremium = in_array($subscription->plan, [
            SubscriptionPlan::PREMIUM,
            SubscriptionPlan::VIP,
        ]);

        $master->update([
            'is_premium' => $isPremium,
            'premium_until' => $subscription->getExpiryDate(),
            'subscription_plan' => $subscription->plan->value,
        ]);
    }

    /**
     * Очистить премиум статус
     */
    private function clearPremiumStatus(MasterProfile $master): void
    {
        $master->update([
            'is_premium' => false,
            'premium_until' => null,
            'subscription_plan' => null,
        ]);
    }

    /**
     * Получить доступные преимущества мастера
     */
    private function getMasterBenefits(MasterProfile $master, ?MasterSubscription $subscription): array
    {
        if (!$subscription) {
            return SubscriptionPlan::FREE->getFeatures();
        }

        $benefits = $subscription->plan->getFeatures();
        
        // Добавляем информацию о лимитах
        $benefits['limits'] = [
            'photos' => $subscription->getLimit('photos'),
            'videos' => $subscription->getLimit('videos'),
            'services' => $subscription->getLimit('services'),
            'work_zones' => $subscription->getLimit('work_zones'),
        ];

        return $benefits;
    }
}