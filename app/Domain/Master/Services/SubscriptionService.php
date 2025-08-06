<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Models\MasterSubscription;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;

/**
 * Сервис управления подписками мастеров - координатор
 */
class SubscriptionService
{
    private SubscriptionManager $subscriptionManager;
    private SubscriptionTrialService $trialService;
    private SubscriptionRenewalService $renewalService;
    private SubscriptionPlanHandler $planHandler;
    private SubscriptionLimitChecker $limitChecker;
    private SubscriptionAnalytics $analytics;
    private SubscriptionStatusUpdater $statusUpdater;

    public function __construct(
        SubscriptionManager $subscriptionManager,
        SubscriptionTrialService $trialService,
        SubscriptionRenewalService $renewalService,
        SubscriptionPlanHandler $planHandler,
        SubscriptionLimitChecker $limitChecker,
        SubscriptionAnalytics $analytics,
        SubscriptionStatusUpdater $statusUpdater
    ) {
        $this->subscriptionManager = $subscriptionManager;
        $this->trialService = $trialService;
        $this->renewalService = $renewalService;
        $this->planHandler = $planHandler;
        $this->limitChecker = $limitChecker;
        $this->analytics = $analytics;
        $this->statusUpdater = $statusUpdater;
    }
    /**
     * Создать новую подписку
     */
    public function createSubscription(
        MasterProfile $master,
        SubscriptionPlan $plan,
        int $periodMonths = 1,
        array $paymentData = []
    ): MasterSubscription {
        $subscription = $this->subscriptionManager->create($master, $plan, $periodMonths, $paymentData);
        
        // Обновляем статус мастера
        $this->statusUpdater->updateMasterStatus($master, $subscription);
        
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
        $subscription = $this->trialService->startTrial($master, $plan, $days);
        
        // Обновляем статус мастера
        $this->statusUpdater->updateMasterStatus($master, $subscription);

        return $subscription;
    }

    /**
     * Активировать подписку после оплаты
     */
    public function activateSubscription(
        MasterSubscription $subscription,
        array $paymentData = []
    ): void {
        $this->renewalService->activate($subscription, $paymentData);
        
        // Обновляем статус мастера
        $this->statusUpdater->updateMasterStatus($subscription->masterProfile, $subscription);
    }

    /**
     * Продлить подписку
     */
    public function renewSubscription(
        MasterSubscription $subscription,
        ?int $periodMonths = null,
        array $paymentData = []
    ): void {
        $this->renewalService->renew($subscription, $periodMonths, $paymentData);
        
        // Обновляем статус мастера
        $this->statusUpdater->updateMasterStatus($subscription->masterProfile, $subscription);
    }

    /**
     * Изменить план подписки
     */
    public function changePlan(
        MasterSubscription $subscription,
        SubscriptionPlan $newPlan
    ): array {
        $changeInfo = $this->planHandler->changePlan($subscription, $newPlan);
        
        // Обновляем статус мастера
        $this->statusUpdater->updateMasterStatus($subscription->masterProfile, $subscription);
        
        return $changeInfo;
    }

    /**
     * Отменить подписку
     */
    public function cancelSubscription(
        MasterSubscription $subscription,
        ?string $reason = null,
        bool $immediate = false
    ): void {
        $this->subscriptionManager->cancel($subscription, $reason, $immediate);
        
        if ($immediate) {
            $this->statusUpdater->updateMasterStatus($subscription->masterProfile, null);
        }
    }

    /**
     * Проверить и обновить истекшие подписки
     */
    public function checkExpirations(): int
    {
        return $this->renewalService->checkExpirations();
    }

    /**
     * Отправить напоминания об истекающих подписках
     */
    public function sendExpirationReminders(): int
    {
        return $this->renewalService->sendExpirationReminders();
    }

    /**
     * Получить активную подписку мастера
     */
    public function getActiveSubscription(MasterProfile $master): ?MasterSubscription
    {
        return $this->subscriptionManager->getActiveSubscription($master);
    }

    /**
     * Проверить лимиты подписки
     */
    public function checkLimit(
        MasterProfile $master,
        string $resource
    ): array {
        return $this->limitChecker->checkLimit($master, $resource);
    }

    /**
     * Получить статистику подписок
     */
    public function getStatistics(): array
    {
        return $this->analytics->getOverallStatistics();
    }

}