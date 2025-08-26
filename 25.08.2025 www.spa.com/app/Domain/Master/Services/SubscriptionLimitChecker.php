<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Models\MasterSubscription;
use App\Enums\SubscriptionPlan;

/**
 * Сервис проверки лимитов подписок
 */
class SubscriptionLimitChecker
{
    private SubscriptionManager $subscriptionManager;

    public function __construct(SubscriptionManager $subscriptionManager)
    {
        $this->subscriptionManager = $subscriptionManager;
    }

    /**
     * Проверить лимиты подписки
     */
    public function checkLimit(MasterProfile $master, string $resource): array
    {
        $subscription = $this->subscriptionManager->getActiveSubscription($master);
        
        if (!$subscription) {
            // Используем бесплатный план по умолчанию
            $limit = SubscriptionPlan::FREE->getLimit($resource);
        } else {
            $limit = $subscription->getLimit($resource);
        }

        // Получаем текущее количество
        $currentCount = $this->getCurrentResourceCount($master, $resource);

        return [
            'resource' => $resource,
            'limit' => $limit,
            'current' => $currentCount,
            'remaining' => $limit === -1 ? -1 : max(0, $limit - $currentCount),
            'reached' => $limit !== -1 && $currentCount >= $limit,
            'percentage' => $limit === -1 ? 0 : min(100, ($currentCount / $limit) * 100),
            'plan' => $subscription?->plan->value ?? SubscriptionPlan::FREE->value,
        ];
    }

    /**
     * Проверить несколько ресурсов одновременно
     */
    public function checkMultipleLimits(MasterProfile $master, array $resources): array
    {
        $results = [];
        
        foreach ($resources as $resource) {
            $results[$resource] = $this->checkLimit($master, $resource);
        }
        
        return $results;
    }

    /**
     * Проверить все доступные лимиты
     */
    public function checkAllLimits(MasterProfile $master): array
    {
        $resources = ['photos', 'videos', 'services', 'work_zones', 'gallery_videos'];
        return $this->checkMultipleLimits($master, $resources);
    }

    /**
     * Проверить, можно ли добавить ресурс
     */
    public function canAddResource(MasterProfile $master, string $resource, int $count = 1): bool
    {
        $limitInfo = $this->checkLimit($master, $resource);
        
        if ($limitInfo['limit'] === -1) {
            return true; // Безлимитный ресурс
        }
        
        return ($limitInfo['current'] + $count) <= $limitInfo['limit'];
    }

    /**
     * Получить рекомендации по улучшению плана
     */
    public function getPlanRecommendations(MasterProfile $master): array
    {
        $currentLimits = $this->checkAllLimits($master);
        $recommendations = [];
        
        // Анализируем использование ресурсов
        foreach ($currentLimits as $resource => $info) {
            if ($info['percentage'] > 80 && $info['limit'] !== -1) {
                $recommendations[] = [
                    'reason' => "Использование ресурса '{$resource}' превышает 80%",
                    'current_usage' => $info['percentage'],
                    'resource' => $resource,
                    'suggested_action' => 'upgrade',
                ];
            }
        }
        
        // Рекомендуем подходящие планы
        if (!empty($recommendations)) {
            $currentPlan = $currentLimits[array_key_first($currentLimits)]['plan'];
            $suggestedPlans = $this->getSuggestedPlans($currentPlan);
            
            return [
                'needs_upgrade' => true,
                'issues' => $recommendations,
                'suggested_plans' => $suggestedPlans,
                'upgrade_benefits' => $this->getUpgradeBenefits($currentPlan),
            ];
        }
        
        return [
            'needs_upgrade' => false,
            'current_plan_sufficient' => true,
        ];
    }

    /**
     * Получить подробную информацию об использовании
     */
    public function getUsageBreakdown(MasterProfile $master): array
    {
        $limits = $this->checkAllLimits($master);
        $subscription = $this->subscriptionManager->getActiveSubscription($master);
        
        return [
            'subscription' => $subscription ? [
                'plan' => $subscription->plan->value,
                'status' => $subscription->status->value,
                'expires_at' => $subscription->getExpiryDate(),
                'days_remaining' => $subscription->getRemainingDays(),
            ] : null,
            'limits' => $limits,
            'summary' => [
                'total_resources' => count($limits),
                'resources_at_limit' => count(array_filter($limits, fn($l) => $l['reached'])),
                'resources_near_limit' => count(array_filter($limits, fn($l) => $l['percentage'] > 80 && !$l['reached'])),
                'average_usage' => round(array_sum(array_column($limits, 'percentage')) / count($limits), 1),
            ],
        ];
    }

    /**
     * Получить текущее количество ресурса
     */
    private function getCurrentResourceCount(MasterProfile $master, string $resource): int
    {
        return match($resource) {
            'photos' => $master->photos()->count(),
            'videos' => $master->videos()->count(),
            'services' => $master->services()->count(),
            'work_zones' => $master->workZones()->count(),
            'gallery_videos' => $master->galleryVideos()->count(),
            default => 0,
        };
    }

    /**
     * Получить рекомендуемые планы
     */
    private function getSuggestedPlans(string $currentPlan): array
    {
        $allPlans = SubscriptionPlan::getAllPlans();
        $currentPlanEnum = SubscriptionPlan::from($currentPlan);
        
        return $allPlans->filter(function($plan) use ($currentPlanEnum) {
            return $plan->getMonthlyPrice() > $currentPlanEnum->getMonthlyPrice();
        })->map(function($plan) {
            return [
                'plan' => $plan->value,
                'name' => $plan->getName(),
                'price' => $plan->getMonthlyPrice(),
                'features' => $plan->getFeatures(),
            ];
        })->values()->toArray();
    }

    /**
     * Получить преимущества улучшения плана
     */
    private function getUpgradeBenefits(string $currentPlan): array
    {
        // Здесь можно определить конкретные преимущества для каждого типа улучшения
        return [
            'higher_limits' => 'Увеличенные лимиты на все ресурсы',
            'priority_support' => 'Приоритетная поддержка',
            'advanced_features' => 'Доступ к расширенным функциям',
            'analytics' => 'Подробная аналитика',
        ];
    }
}