<?php

namespace App\Domain\Payment\Repositories;

use App\Domain\Payment\Models\Subscription;
use App\Domain\Payment\Enums\SubscriptionStatus;
use App\Domain\Payment\Enums\SubscriptionInterval;
use App\Support\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

/**
 * Репозиторий для работы с подписками
 */
class SubscriptionRepository extends BaseRepository
{
    /**
     * Получить модель репозитория
     */
    public function model(): string
    {
        return Subscription::class;
    }

    /**
     * Создать новую подписку
     */
    public function createSubscription(array $data): Subscription
    {
        $data['subscription_id'] = $data['subscription_id'] ?? Subscription::generateSubscriptionId();
        
        // Установить даты по умолчанию
        if (!isset($data['starts_at'])) {
            $data['starts_at'] = now();
        }
        
        // Рассчитать дату окончания если не указана
        if (!isset($data['ends_at']) && isset($data['interval']) && isset($data['interval_count'])) {
            $data['ends_at'] = $this->calculateEndDate(
                Carbon::parse($data['starts_at']),
                $data['interval'],
                $data['interval_count']
            );
        }
        
        return $this->create($data);
    }

    /**
     * Найти активную подписку пользователя
     */
    public function findActiveByUser(int $userId, string $planName = null): ?Subscription
    {
        $query = $this->model->byUser($userId)->active();
        
        if ($planName) {
            $query->byPlan($planName);
        }
        
        return $query->first();
    }

    /**
     * Получить все подписки пользователя
     */
    public function getUserSubscriptions(
        int $userId, 
        array $filters = [], 
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = $this->model->byUser($userId);
        
        $this->applyFilters($query, $filters);
        
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Получить истекающие подписки
     */
    public function getExpiring(int $days = 7): Collection
    {
        return $this->model->expiring($days)->get();
    }

    /**
     * Получить подписки, требующие оплаты
     */
    public function getRequiringPayment(): Collection
    {
        return $this->model->requiresPayment()->get();
    }

    /**
     * Обновить статус подписки
     */
    public function updateStatus(int $subscriptionId, SubscriptionStatus $status, array $additionalData = []): bool
    {
        $data = array_merge(['status' => $status], $additionalData);
        
        return $this->update($subscriptionId, $data);
    }

    /**
     * Продлить подписку
     */
    public function renew(Subscription $subscription, ?Carbon $newEndDate = null): bool
    {
        if (!$newEndDate) {
            $newEndDate = $this->calculateEndDate(
                $subscription->ends_at ?? now(),
                $subscription->interval,
                $subscription->interval_count
            );
        }
        
        return $subscription->update([
            'ends_at' => $newEndDate,
            'status' => SubscriptionStatus::ACTIVE,
            'next_payment_at' => $newEndDate,
        ]);
    }

    /**
     * Отменить подписку
     */
    public function cancel(Subscription $subscription, ?string $reason = null): bool
    {
        return $subscription->cancel($reason);
    }

    /**
     * Получить статистику подписок
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->model->count(),
            'active' => $this->model->active()->count(),
            'cancelled' => $this->model->cancelled()->count(),
            'by_status' => $this->getStatisticsByStatus(),
            'by_plan' => $this->getStatisticsByPlan(),
            'revenue' => $this->getRevenueStatistics(),
        ];
    }

    /**
     * Получить статистику по статусам
     */
    public function getStatisticsByStatus(): array
    {
        return $this->model
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Получить статистику по планам
     */
    public function getStatisticsByPlan(): array
    {
        return $this->model
            ->active()
            ->selectRaw('plan_name, COUNT(*) as count, SUM(price) as revenue')
            ->groupBy('plan_name')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->plan_name => [
                    'count' => $item->count,
                    'revenue' => $item->revenue,
                ]];
            })
            ->toArray();
    }

    /**
     * Получить статистику доходов
     */
    public function getRevenueStatistics(): array
    {
        $monthly = $this->model
            ->active()
            ->where('interval', SubscriptionInterval::MONTH)
            ->sum('price');
            
        $yearly = $this->model
            ->active()
            ->where('interval', SubscriptionInterval::YEAR)
            ->sum('price');
            
        return [
            'mrr' => $monthly + ($yearly / 12), // Monthly Recurring Revenue
            'arr' => ($monthly * 12) + $yearly, // Annual Recurring Revenue
        ];
    }

    /**
     * Проверить доступность функции в подписке
     */
    public function checkFeatureAccess(Subscription $subscription, string $feature): bool
    {
        $features = $subscription->features ?? [];
        
        return in_array($feature, $features) || isset($features[$feature]);
    }

    /**
     * Обновить использование функции
     */
    public function updateFeatureUsage(Subscription $subscription, string $feature, int $amount = 1): bool
    {
        if (!$subscription->checkFeatureLimit($feature, $amount)) {
            return false;
        }
        
        $subscription->incrementFeatureUsage($feature, $amount);
        
        return true;
    }

    /**
     * Рассчитать дату окончания подписки
     */
    protected function calculateEndDate(Carbon $startDate, $interval, int $count = 1): Carbon
    {
        if ($interval instanceof SubscriptionInterval) {
            return match($interval) {
                SubscriptionInterval::DAY => $startDate->copy()->addDays($count),
                SubscriptionInterval::WEEK => $startDate->copy()->addWeeks($count),
                SubscriptionInterval::MONTH => $startDate->copy()->addMonths($count),
                SubscriptionInterval::QUARTER => $startDate->copy()->addMonths($count * 3),
                SubscriptionInterval::YEAR => $startDate->copy()->addYears($count),
                SubscriptionInterval::LIFETIME => Carbon::parse('2100-01-01'),
            };
        }
        
        // Для обратной совместимости со строковыми значениями
        return match($interval) {
            'day' => $startDate->copy()->addDays($count),
            'week' => $startDate->copy()->addWeeks($count),
            'month' => $startDate->copy()->addMonths($count),
            'quarter' => $startDate->copy()->addMonths($count * 3),
            'year' => $startDate->copy()->addYears($count),
            default => $startDate->copy()->addMonths($count),
        };
    }

    /**
     * Применить фильтры к запросу
     */
    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['plan'])) {
            $query->byPlan($filters['plan']);
        }
        
        if (!empty($filters['active_only'])) {
            $query->active();
        }
        
        if (!empty($filters['expiring_days'])) {
            $query->expiring($filters['expiring_days']);
        }
    }
}