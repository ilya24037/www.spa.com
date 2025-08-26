<?php

namespace App\Domain\Notification\Handlers;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Repositories\NotificationRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик обслуживания уведомлений (очистка, статистика)
 */
class NotificationMaintenanceHandler
{
    public function __construct(
        protected NotificationRepository $repository
    ) {}

    /**
     * Очистить старые уведомления
     */
    public function cleanup(): array
    {
        $stats = [
            'deleted_read' => 0,
            'deleted_expired' => 0,
            'deleted_failed' => 0,
        ];

        DB::transaction(function () use (&$stats) {
            // Удалить прочитанные уведомления старше 30 дней
            $stats['deleted_read'] = Notification::read()
                ->where('read_at', '<', now()->subDays(30))
                ->delete();

            // Удалить истекшие уведомления
            $stats['deleted_expired'] = Notification::expired()->delete();

            // Удалить неудачные уведомления старше 7 дней
            $stats['deleted_failed'] = Notification::failed()
                ->where('created_at', '<', now()->subDays(7))
                ->delete();
        });

        Log::info('Notifications cleanup completed', $stats);
        
        return $stats;
    }

    /**
     * Получить статистику уведомлений
     */
    public function getStats(array $filters = []): array
    {
        return $this->repository->getStats($filters);
    }

    /**
     * Получить детализированную статистику
     */
    public function getDetailedStats(array $filters = []): array
    {
        $baseQuery = Notification::query()
            ->when(!empty($filters['from_date']), function ($query) use ($filters) {
                $query->where('created_at', '>=', $filters['from_date']);
            })
            ->when(!empty($filters['to_date']), function ($query) use ($filters) {
                $query->where('created_at', '<=', $filters['to_date']);
            })
            ->when(!empty($filters['type']), function ($query) use ($filters) {
                $query->where('type', $filters['type']);
            })
            ->when(!empty($filters['user_id']), function ($query) use ($filters) {
                $query->where('user_id', $filters['user_id']);
            });

        return [
            'total_notifications' => (clone $baseQuery)->count(),
            'by_status' => (clone $baseQuery)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'by_type' => (clone $baseQuery)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'by_channel' => $this->getChannelStats($baseQuery),
            'delivery_success_rate' => $this->calculateDeliveryRate($baseQuery),
            'avg_read_time' => $this->calculateAverageReadTime($baseQuery),
            'most_active_users' => $this->getMostActiveUsers($baseQuery),
        ];
    }

    /**
     * Получить статистику по каналам
     */
    protected function getChannelStats($query): array
    {
        $notifications = (clone $query)
            ->whereNotNull('channels')
            ->pluck('channels')
            ->flatten()
            ->groupBy(function ($channel) {
                return $channel;
            })
            ->map(function ($group) {
                return $group->count();
            })
            ->toArray();

        return $notifications;
    }

    /**
     * Рассчитать коэффициент доставки
     */
    protected function calculateDeliveryRate($query): float
    {
        $total = (clone $query)->count();
        
        if ($total === 0) {
            return 0.0;
        }

        $delivered = (clone $query)
            ->whereIn('status', ['sent', 'delivered', 'read'])
            ->count();

        return round(($delivered / $total) * 100, 2);
    }

    /**
     * Рассчитать среднее время прочтения
     */
    protected function calculateAverageReadTime($query): ?int
    {
        $readNotifications = (clone $query)
            ->whereNotNull('read_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, read_at)) as avg_minutes')
            ->first();

        return $readNotifications ? (int) $readNotifications->avg_minutes : null;
    }

    /**
     * Получить наиболее активных пользователей
     */
    protected function getMostActiveUsers($query, int $limit = 10): array
    {
        return (clone $query)
            ->selectRaw('user_id, COUNT(*) as notification_count')
            ->groupBy('user_id')
            ->orderByDesc('notification_count')
            ->limit($limit)
            ->with('user:id,name,email')
            ->get()
            ->toArray();
    }

    /**
     * Анализ производительности уведомлений
     */
    public function performanceAnalysis(array $filters = []): array
    {
        $fromDate = $filters['from_date'] ?? now()->subDays(7);
        $toDate = $filters['to_date'] ?? now();

        return [
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'volume_trend' => $this->getVolumeTrend($fromDate, $toDate),
            'failure_analysis' => $this->getFailureAnalysis($fromDate, $toDate),
            'channel_performance' => $this->getChannelPerformance($fromDate, $toDate),
            'user_engagement' => $this->getUserEngagement($fromDate, $toDate),
        ];
    }

    /**
     * Получить тренд объема уведомлений
     */
    protected function getVolumeTrend($fromDate, $toDate): array
    {
        return Notification::whereBetween('created_at', [$fromDate, $toDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }

    /**
     * Анализ неудачных уведомлений
     */
    protected function getFailureAnalysis($fromDate, $toDate): array
    {
        $failedNotifications = Notification::failed()
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        $failureReasons = [];
        $failuresByType = [];

        foreach ($failedNotifications as $notification) {
            $reason = $notification->failure_reason ?? 'unknown';
            $type = $notification->type->value ?? 'unknown';

            $failureReasons[$reason] = ($failureReasons[$reason] ?? 0) + 1;
            $failuresByType[$type] = ($failuresByType[$type] ?? 0) + 1;
        }

        return [
            'total_failed' => $failedNotifications->count(),
            'failure_reasons' => $failureReasons,
            'failures_by_type' => $failuresByType,
            'failure_rate' => $this->calculateFailureRate($fromDate, $toDate),
        ];
    }

    /**
     * Рассчитать коэффициент неудач
     */
    protected function calculateFailureRate($fromDate, $toDate): float
    {
        $total = Notification::whereBetween('created_at', [$fromDate, $toDate])->count();
        $failed = Notification::failed()->whereBetween('created_at', [$fromDate, $toDate])->count();

        return $total > 0 ? round(($failed / $total) * 100, 2) : 0.0;
    }

    /**
     * Получить производительность каналов
     */
    protected function getChannelPerformance($fromDate, $toDate): array
    {
        // Этот метод требует дополнительной информации о доставках
        // В реальном проекте здесь была бы логика анализа метрик каналов
        return [];
    }

    /**
     * Получить данные о вовлеченности пользователей
     */
    protected function getUserEngagement($fromDate, $toDate): array
    {
        $totalUsers = Notification::whereBetween('created_at', [$fromDate, $toDate])
            ->distinct('user_id')
            ->count();

        $readUsers = Notification::whereBetween('created_at', [$fromDate, $toDate])
            ->whereNotNull('read_at')
            ->distinct('user_id')
            ->count();

        return [
            'total_users' => $totalUsers,
            'engaged_users' => $readUsers,
            'engagement_rate' => $totalUsers > 0 ? round(($readUsers / $totalUsers) * 100, 2) : 0.0,
        ];
    }
}