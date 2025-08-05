<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Subscription;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Repositories\SubscriptionRepository;
use App\Domain\Payment\Enums\SubscriptionStatus;
use App\Domain\Payment\Enums\SubscriptionInterval;
use App\Domain\Payment\DTOs\CreateSubscriptionDTO;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Сервис для работы с подписками
 */
class SubscriptionService
{
    public function __construct(
        private SubscriptionRepository $repository,
        private PaymentService $paymentService,
        private TransactionService $transactionService
    ) {}

    /**
     * Создать новую подписку
     */
    public function createSubscription(CreateSubscriptionDTO $dto): Subscription
    {
        return DB::transaction(function () use ($dto) {
            // Проверить, нет ли активной подписки
            $existingSubscription = $this->repository->findActiveByUser(
                $dto->userId,
                $dto->planName
            );

            if ($existingSubscription) {
                throw new \Exception('У пользователя уже есть активная подписка на этот план');
            }

            // Создать подписку
            $subscription = $this->repository->createSubscription([
                'user_id' => $dto->userId,
                'subscribable_type' => $dto->subscribableType,
                'subscribable_id' => $dto->subscribableId,
                'plan_id' => $dto->planId,
                'plan_name' => $dto->planName,
                'status' => $dto->hasTrialPeriod ? SubscriptionStatus::TRIALING : SubscriptionStatus::PENDING,
                'trial_ends_at' => $dto->trialDays ? now()->addDays($dto->trialDays) : null,
                'starts_at' => $dto->startsAt ?? now(),
                'ends_at' => $dto->endsAt,
                'price' => $dto->price,
                'currency' => $dto->currency ?? 'RUB',
                'interval' => $dto->interval,
                'interval_count' => $dto->intervalCount ?? 1,
                'features' => $dto->features,
                'limits' => $dto->limits,
                'auto_renew' => $dto->autoRenew ?? true,
                'payment_method' => $dto->paymentMethod,
                'gateway' => $dto->gateway,
                'metadata' => $dto->metadata,
            ]);

            // Если нет пробного периода, создать первый платеж
            if (!$dto->hasTrialPeriod && $dto->price > 0) {
                $this->createSubscriptionPayment($subscription, 'Первый платеж по подписке');
            }

            Log::info('Subscription created', [
                'subscription_id' => $subscription->subscription_id,
                'user_id' => $subscription->user_id,
                'plan' => $subscription->plan_name,
            ]);

            return $subscription;
        });
    }

    /**
     * Активировать подписку
     */
    public function activateSubscription(Subscription $subscription): bool
    {
        if (!$subscription->status->canTransitionTo(SubscriptionStatus::ACTIVE)) {
            return false;
        }

        return $this->repository->updateStatus($subscription->id, SubscriptionStatus::ACTIVE, [
            'starts_at' => $subscription->starts_at ?? now(),
        ]);
    }

    /**
     * Отменить подписку
     */
    public function cancelSubscription(Subscription $subscription, string $reason = null, bool $immediate = false): bool
    {
        if ($subscription->isCancelled()) {
            return false;
        }

        $data = [
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ];

        if ($immediate) {
            $data['status'] = SubscriptionStatus::CANCELLED;
            $data['ends_at'] = now();
        }

        $result = $subscription->update($data);

        Log::info('Subscription cancelled', [
            'subscription_id' => $subscription->subscription_id,
            'immediate' => $immediate,
            'reason' => $reason,
        ]);

        return $result;
    }

    /**
     * Приостановить подписку
     */
    public function pauseSubscription(Subscription $subscription): bool
    {
        return $subscription->pause();
    }

    /**
     * Возобновить подписку
     */
    public function resumeSubscription(Subscription $subscription): bool
    {
        return $subscription->resume();
    }

    /**
     * Продлить подписку
     */
    public function renewSubscription(Subscription $subscription, ?Carbon $customEndDate = null): bool
    {
        return DB::transaction(function () use ($subscription, $customEndDate) {
            $result = $this->repository->renew($subscription, $customEndDate);

            if ($result && $subscription->price > 0) {
                $this->createSubscriptionPayment($subscription, 'Продление подписки');
            }

            Log::info('Subscription renewed', [
                'subscription_id' => $subscription->subscription_id,
                'new_end_date' => $subscription->fresh()->ends_at,
            ]);

            return $result;
        });
    }

    /**
     * Обработать истекшие подписки
     */
    public function processExpiredSubscriptions(): int
    {
        $expired = $this->repository->model()
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where('ends_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($expired as $subscription) {
            if ($subscription->auto_renew) {
                // Попытаться продлить
                try {
                    $this->renewSubscription($subscription);
                } catch (\Exception $e) {
                    // Если не удалось продлить, пометить как истекшую
                    $this->repository->updateStatus($subscription->id, SubscriptionStatus::EXPIRED);
                    Log::error('Failed to renew subscription', [
                        'subscription_id' => $subscription->subscription_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                // Пометить как истекшую
                $this->repository->updateStatus($subscription->id, SubscriptionStatus::EXPIRED);
            }
            $count++;
        }

        return $count;
    }

    /**
     * Обработать подписки с истекающим пробным периодом
     */
    public function processTrialEnding(): int
    {
        $ending = $this->repository->model()
            ->where('status', SubscriptionStatus::TRIALING)
            ->where('trial_ends_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($ending as $subscription) {
            // Создать платеж для активации
            if ($subscription->price > 0) {
                try {
                    $this->createSubscriptionPayment($subscription, 'Активация после пробного периода');
                    $this->activateSubscription($subscription);
                } catch (\Exception $e) {
                    // Если не удалось создать платеж, отменить подписку
                    $this->cancelSubscription($subscription, 'Не удалось оплатить после пробного периода', true);
                    Log::error('Failed to charge after trial', [
                        'subscription_id' => $subscription->subscription_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                // Бесплатная подписка - просто активировать
                $this->activateSubscription($subscription);
            }
            $count++;
        }

        return $count;
    }

    /**
     * Проверить доступ к функции
     */
    public function checkFeatureAccess(int $userId, string $feature, string $planName = null): bool
    {
        $subscription = $this->repository->findActiveByUser($userId, $planName);
        
        if (!$subscription) {
            return false;
        }

        return $this->repository->checkFeatureAccess($subscription, $feature);
    }

    /**
     * Использовать функцию (увеличить счетчик)
     */
    public function useFeature(int $userId, string $feature, int $amount = 1): bool
    {
        $subscription = $this->repository->findActiveByUser($userId);
        
        if (!$subscription) {
            return false;
        }

        return $this->repository->updateFeatureUsage($subscription, $feature, $amount);
    }

    /**
     * Сменить план подписки
     */
    public function changePlan(Subscription $subscription, string $newPlanName, array $planDetails): Subscription
    {
        return DB::transaction(function () use ($subscription, $newPlanName, $planDetails) {
            $oldPlan = $subscription->plan_name;
            
            // Рассчитать пропорциональную оплату
            $prorationAmount = $this->calculateProration($subscription, $planDetails['price']);
            
            // Обновить подписку
            $subscription->update([
                'plan_name' => $newPlanName,
                'price' => $planDetails['price'],
                'features' => $planDetails['features'] ?? [],
                'limits' => $planDetails['limits'] ?? [],
            ]);

            // Создать платеж/возврат при необходимости
            if ($prorationAmount > 0) {
                $this->createSubscriptionPayment($subscription, "Доплата при смене плана с $oldPlan на $newPlanName");
            } elseif ($prorationAmount < 0) {
                $this->paymentService->createRefund($subscription->last_payment_id, abs($prorationAmount), "Возврат при смене плана с $oldPlan на $newPlanName");
            }

            Log::info('Subscription plan changed', [
                'subscription_id' => $subscription->subscription_id,
                'old_plan' => $oldPlan,
                'new_plan' => $newPlanName,
                'proration' => $prorationAmount,
            ]);

            return $subscription->fresh();
        });
    }

    /**
     * Получить статистику подписок
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * Создать платеж для подписки
     */
    protected function createSubscriptionPayment(Subscription $subscription, string $description): Payment
    {
        $payment = $this->paymentService->createPayment([
            'user_id' => $subscription->user_id,
            'payable_type' => Subscription::class,
            'payable_id' => $subscription->id,
            'type' => \App\Enums\PaymentType::SUBSCRIPTION,
            'method' => $subscription->payment_method,
            'amount' => $subscription->price,
            'currency' => $subscription->currency,
            'description' => $description,
            'gateway' => $subscription->gateway,
            'metadata' => [
                'subscription_id' => $subscription->subscription_id,
                'plan_name' => $subscription->plan_name,
                'interval' => $subscription->interval->value,
            ],
        ]);

        // Создать транзакцию
        $this->transactionService->createSubscriptionTransaction(
            $subscription,
            $subscription->price,
            $description
        );

        return $payment;
    }

    /**
     * Рассчитать пропорциональную оплату при смене плана
     */
    protected function calculateProration(Subscription $subscription, float $newPrice): float
    {
        if (!$subscription->ends_at || !$subscription->starts_at) {
            return $newPrice - $subscription->price;
        }

        $totalDays = $subscription->starts_at->diffInDays($subscription->ends_at);
        $remainingDays = now()->diffInDays($subscription->ends_at, false);
        
        if ($remainingDays <= 0) {
            return 0;
        }

        $remainingPercent = $remainingDays / $totalDays;
        $unusedAmount = $subscription->price * $remainingPercent;
        $newAmount = $newPrice * $remainingPercent;

        return $newAmount - $unusedAmount;
    }
}