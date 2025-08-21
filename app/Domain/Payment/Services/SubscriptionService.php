<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Subscription;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Repositories\SubscriptionRepository;
use App\Domain\Payment\Enums\SubscriptionStatus;
use App\Domain\Payment\Enums\SubscriptionInterval;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\DTOs\CreateSubscriptionDTO;
use App\Domain\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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
    public function cancelSubscription(Subscription $subscription, ?string $reason = null, bool $immediate = false): bool
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
    public function checkFeatureAccess(int $userId, string $feature, ?string $planName = null): bool
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

    // ========== ВАЛИДАЦИЯ И ПРОВЕРКИ ==========

    /**
     * Валидировать создание подписки
     */
    public function validateSubscription(CreateSubscriptionDTO $dto): void
    {
        $validator = Validator::make($dto->toArray(), [
            'userId' => 'required|exists:users,id',
            'planName' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'interval' => 'required|string',
            'intervalCount' => 'sometimes|integer|min:1|max:12'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Дополнительные бизнес-проверки
        $this->validateUserEligibility($dto->userId, $dto->planName);
        $this->validatePlanLimits($dto);
    }

    /**
     * Проверить право пользователя на подписку
     */
    private function validateUserEligibility(int $userId, string $planName): void
    {
        $user = User::find($userId);
        
        if (!$user) {
            throw new \InvalidArgumentException('Пользователь не найден');
        }

        if ($user->is_blocked ?? false) {
            throw new \InvalidArgumentException('Заблокированный пользователь не может оформить подписку');
        }

        // Проверка максимального количества подписок
        $activeSubscriptions = $this->repository->getUserActiveSubscriptions($userId);
        if ($activeSubscriptions->count() >= 3) {
            throw new \InvalidArgumentException('Превышено максимальное количество активных подписок');
        }
    }

    /**
     * Валидировать лимиты плана
     */
    private function validatePlanLimits(CreateSubscriptionDTO $dto): void
    {
        // Проверка максимальной цены
        if ($dto->price > 100000) {
            throw new \InvalidArgumentException('Цена подписки превышает максимально допустимую');
        }

        // Проверка интервала
        if ($dto->intervalCount && $dto->intervalCount > 12) {
            throw new \InvalidArgumentException('Максимальный интервал подписки - 12 периодов');
        }
    }

    // ========== УПРАВЛЕНИЕ СКИДКАМИ И КУПОНАМИ ==========

    /**
     * Применить купон к подписке
     */
    public function applyCoupon(Subscription $subscription, string $couponCode): float
    {
        $coupon = $this->validateCoupon($couponCode);
        
        $discount = $this->calculateDiscount($subscription->price, $coupon);
        
        $subscription->update([
            'metadata' => array_merge($subscription->metadata ?? [], [
                'coupon_code' => $couponCode,
                'original_price' => $subscription->price,
                'discount_amount' => $discount
            ]),
            'price' => $subscription->price - $discount
        ]);

        Log::info('Coupon applied to subscription', [
            'subscription_id' => $subscription->id,
            'coupon_code' => $couponCode,
            'discount' => $discount
        ]);

        return $discount;
    }

    /**
     * Валидировать купон
     */
    private function validateCoupon(string $couponCode): array
    {
        // Простая валидация купона
        $validCoupons = [
            'TRIAL30' => ['type' => 'percent', 'value' => 30, 'max_uses' => 100],
            'SAVE500' => ['type' => 'fixed', 'value' => 500, 'max_uses' => 50],
            'PREMIUM25' => ['type' => 'percent', 'value' => 25, 'max_uses' => 200]
        ];

        if (!isset($validCoupons[$couponCode])) {
            throw new \InvalidArgumentException('Недействительный купон');
        }

        return $validCoupons[$couponCode];
    }

    /**
     * Рассчитать скидку
     */
    private function calculateDiscount(float $price, array $coupon): float
    {
        if ($coupon['type'] === 'percent') {
            return $price * ($coupon['value'] / 100);
        }

        return min($coupon['value'], $price);
    }

    // ========== АНАЛИТИКА И ОТЧЁТНОСТЬ ==========

    /**
     * Получить детальную статистику подписок
     */
    public function getDetailedStatistics(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->subMonth();
        $to = $to ?? now();

        $subscriptions = Subscription::whereBetween('created_at', [$from, $to])->get();

        $stats = [
            'total_subscriptions' => $subscriptions->count(),
            'active_subscriptions' => $subscriptions->where('status', SubscriptionStatus::ACTIVE)->count(),
            'cancelled_subscriptions' => $subscriptions->where('status', SubscriptionStatus::CANCELLED)->count(),
            'expired_subscriptions' => $subscriptions->where('status', SubscriptionStatus::EXPIRED)->count(),
            'trial_subscriptions' => $subscriptions->where('status', SubscriptionStatus::TRIALING)->count(),
            'total_revenue' => $subscriptions->sum('price'),
            'average_price' => $subscriptions->avg('price') ?? 0,
            'by_plan' => [],
            'by_interval' => [],
            'churn_rate' => 0,
            'retention_rate' => 0
        ];

        // Группировка по планам
        foreach ($subscriptions->groupBy('plan_name') as $plan => $planSubscriptions) {
            $stats['by_plan'][$plan] = [
                'count' => $planSubscriptions->count(),
                'revenue' => $planSubscriptions->sum('price'),
                'active' => $planSubscriptions->where('status', SubscriptionStatus::ACTIVE)->count()
            ];
        }

        // Группировка по интервалам
        foreach ($subscriptions->groupBy('interval') as $interval => $intervalSubscriptions) {
            $stats['by_interval'][$interval] = [
                'count' => $intervalSubscriptions->count(),
                'revenue' => $intervalSubscriptions->sum('price')
            ];
        }

        // Расчёт показателей
        $stats['churn_rate'] = $this->calculateChurnRate($from, $to);
        $stats['retention_rate'] = 100 - $stats['churn_rate'];

        return $stats;
    }

    /**
     * Рассчитать показатель оттока (churn rate)
     */
    private function calculateChurnRate(Carbon $from, Carbon $to): float
    {
        $startActive = Subscription::where('status', SubscriptionStatus::ACTIVE)
            ->where('created_at', '<', $from)
            ->count();

        $cancelled = Subscription::where('status', SubscriptionStatus::CANCELLED)
            ->whereBetween('cancelled_at', [$from, $to])
            ->count();

        return $startActive > 0 ? round(($cancelled / $startActive) * 100, 2) : 0;
    }

    /**
     * Получить прогноз доходов
     */
    public function getRevenueForecasting(int $months = 12): array
    {
        $activeSubscriptions = Subscription::where('status', SubscriptionStatus::ACTIVE)
            ->where('auto_renew', true)
            ->get();

        $forecast = [];
        $currentDate = now();

        for ($i = 0; $i < $months; $i++) {
            $month = $currentDate->copy()->addMonths($i);
            $monthlyRevenue = 0;

            foreach ($activeSubscriptions as $subscription) {
                $monthlyRevenue += $this->calculateMonthlyRevenue($subscription, $month);
            }

            $forecast[] = [
                'month' => $month->format('Y-m'),
                'revenue' => round($monthlyRevenue, 2),
                'subscriptions_count' => $activeSubscriptions->count()
            ];
        }

        return $forecast;
    }

    /**
     * Рассчитать месячный доход от подписки
     */
    private function calculateMonthlyRevenue(Subscription $subscription, Carbon $month): float
    {
        if ($subscription->ends_at && $subscription->ends_at->lt($month)) {
            return 0;
        }

        // Приведение к месячному доходу
        return match($subscription->interval) {
            SubscriptionInterval::MONTH => $subscription->price,
            SubscriptionInterval::YEAR => $subscription->price / 12,
            SubscriptionInterval::WEEK => $subscription->price * 4.33,
            SubscriptionInterval::DAY => $subscription->price * 30,
            default => $subscription->price
        };
    }

    // ========== УВЕДОМЛЕНИЯ И НАПОМИНАНИЯ ==========

    /**
     * Отправить напоминания об истекающих подписках
     */
    public function sendExpirationReminders(): int
    {
        $expiringSoon = Subscription::where('status', SubscriptionStatus::ACTIVE)
            ->where('auto_renew', false)
            ->whereBetween('ends_at', [now()->addDays(3), now()->addDays(7)])
            ->get();

        $sent = 0;
        foreach ($expiringSoon as $subscription) {
            try {
                $this->sendExpirationNotification($subscription);
                $sent++;
            } catch (\Exception $e) {
                Log::error('Failed to send expiration reminder', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $sent;
    }

    /**
     * Отправить уведомление об истечении подписки
     */
    private function sendExpirationNotification(Subscription $subscription): void
    {
        Log::info('Subscription expiration reminder sent', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'expires_at' => $subscription->ends_at
        ]);
    }

    // ========== МИГРАЦИЯ И МАССОВЫЕ ОПЕРАЦИИ ==========

    /**
     * Массовая смена плана
     */
    public function bulkChangePlan(Collection $subscriptions, string $newPlan, array $planDetails): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($subscriptions as $subscription) {
            try {
                $this->changePlan($subscription, $newPlan, $planDetails);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Массовое продление подписок
     */
    public function bulkRenewal(Collection $subscriptions, ?Carbon $customEndDate = null): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($subscriptions as $subscription) {
            try {
                $this->renewSubscription($subscription, $customEndDate);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    // ========== ИНТЕГРАЦИЯ С ДРУГИМИ СЕРВИСАМИ ==========

    /**
     * Создать подписку с полной интеграцией
     */
    public function createSubscriptionWithIntegration(CreateSubscriptionDTO $dto): Subscription
    {
        // Валидация
        $this->validateSubscription($dto);

        // Создание подписки
        $subscription = $this->createSubscription($dto);

        // Интеграция с другими сервисами
        $this->activateSubscriptionFeatures($subscription);

        return $subscription;
    }

    /**
     * Активировать функции подписки
     */
    private function activateSubscriptionFeatures(Subscription $subscription): void
    {
        // Активация специфичных для плана возможностей
        $features = $subscription->features ?? [];
        
        foreach ($features as $feature => $enabled) {
            if ($enabled) {
                Log::info('Subscription feature activated', [
                    'subscription_id' => $subscription->id,
                    'feature' => $feature
                ]);
            }
        }
    }

    /**
     * Получить рекомендации по улучшению подписки
     */
    public function getUpgradeRecommendations(int $userId): array
    {
        $currentSubscription = $this->repository->findActiveByUser($userId);
        
        if (!$currentSubscription) {
            return [
                'has_subscription' => false,
                'recommendations' => [
                    'plan' => 'basic',
                    'reason' => 'Начните с базового плана для доступа к основным функциям'
                ]
            ];
        }

        $usageStats = $this->getSubscriptionUsageStats($currentSubscription);
        
        return [
            'has_subscription' => true,
            'current_plan' => $currentSubscription->plan_name,
            'usage_stats' => $usageStats,
            'recommendations' => $this->analyzeUpgradeNeeds($currentSubscription, $usageStats)
        ];
    }

    /**
     * Получить статистику использования подписки
     */
    private function getSubscriptionUsageStats(Subscription $subscription): array
    {
        return [
            'features_used' => count(array_filter($subscription->features ?? [])),
            'limits_reached' => $this->checkLimitsUsage($subscription),
            'usage_percentage' => $this->calculateUsagePercentage($subscription)
        ];
    }

    /**
     * Проверить использование лимитов
     */
    private function checkLimitsUsage(Subscription $subscription): array
    {
        $limits = $subscription->limits ?? [];
        $reached = [];

        foreach ($limits as $limit => $value) {
            // Здесь была бы реальная проверка использования лимитов
            // Пока возвращаем заглушку
            $reached[$limit] = rand(0, 100) > 80; // 20% шанс достижения лимита
        }

        return $reached;
    }

    /**
     * Рассчитать процент использования подписки
     */
    private function calculateUsagePercentage(Subscription $subscription): float
    {
        // Заглушка для расчёта процента использования
        return rand(30, 90);
    }

    /**
     * Анализировать потребности в улучшении
     */
    private function analyzeUpgradeNeeds(Subscription $subscription, array $usageStats): array
    {
        $recommendations = [];

        if ($usageStats['usage_percentage'] > 80) {
            $recommendations[] = [
                'type' => 'upgrade',
                'reason' => 'Высокое использование текущего плана',
                'suggested_plan' => 'premium'
            ];
        }

        if (count(array_filter($usageStats['limits_reached'])) > 0) {
            $recommendations[] = [
                'type' => 'upgrade',
                'reason' => 'Достигнуты лимиты использования',
                'suggested_plan' => 'unlimited'
            ];
        }

        return $recommendations;
    }
}