<?php

namespace App\Domain\Notification\Repositories\Statistics;

use App\Domain\Notification\Models\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Сервис статистики уведомлений
 */
class NotificationStatisticsService
{
    private Notification $model;

    public function __construct(Notification $model)
    {
        $this->model = $model;
    }

    /**
     * Получить общую статистику
     */
    public function getStats(int $days = 7): array
    {
        $baseQuery = $this->model->recent($days);

        return [
            'total' => $baseQuery->count(),
            'by_type' => $this->getStatsByField($baseQuery, 'type'),
            'by_status' => $this->getStatsByField($baseQuery, 'status'),
            'by_priority' => $this->getStatsByField($baseQuery, 'priority'),
            'unread_count' => $baseQuery->unread()->count(),
            'delivery_rate' => $this->getDeliveryRate($days),
            'avg_processing_time' => $this->getAverageProcessingTime($days),
        ];
    }

    /**
     * Получить статистику пользователя
     */
    public function getUserStats(int $userId, int $days = 30): array
    {
        $baseQuery = $this->model->forUser($userId)->recent($days);

        return [
            'total' => $baseQuery->count(),
            'unread' => $baseQuery->unread()->count(),
            'read' => $baseQuery->read()->count(),
            'by_type' => $this->getStatsByField($baseQuery, 'type'),
            'engagement_rate' => $this->getUserEngagementRate($userId, $days),
        ];
    }

    /**
     * Получить топ типов уведомлений
     */
    public function getTopTypes(int $limit = 10, int $days = 30): array
    {
        return $this->model->recent($days)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Получить активных пользователей
     */
    public function getActiveUsers(int $days = 7)
    {
        return $this->model->recent($days)
            ->select('user_id', DB::raw('count(*) as notification_count'))
            ->groupBy('user_id')
            ->having('notification_count', '>', 0)
            ->orderBy('notification_count', 'desc')
            ->with('user')
            ->get();
    }

    /**
     * Получить статистику по полю
     */
    private function getStatsByField($query, string $field): array
    {
        return $query->select($field, DB::raw('count(*) as count'))
            ->groupBy($field)
            ->pluck('count', $field)
            ->toArray();
    }

    /**
     * Получить коэффициент доставки
     */
    private function getDeliveryRate(int $days): float
    {
        $total = $this->model->recent($days)->count();
        $delivered = $this->model->recent($days)->delivered()->count();

        return $total > 0 ? round(($delivered / $total) * 100, 2) : 0;
    }

    /**
     * Получить среднее время обработки
     */
    private function getAverageProcessingTime(int $days): ?float
    {
        $notifications = $this->model->recent($days)
            ->whereNotNull('sent_at')
            ->get(['created_at', 'sent_at']);

        if ($notifications->isEmpty()) {
            return null;
        }

        $totalTime = $notifications->sum(function($notification) {
            return $notification->sent_at->diffInSeconds($notification->created_at);
        });

        return round($totalTime / $notifications->count(), 2);
    }

    /**
     * Получить уровень вовлеченности пользователя
     */
    private function getUserEngagementRate(int $userId, int $days): float
    {
        $total = $this->model->forUser($userId)->recent($days)->count();
        $read = $this->model->forUser($userId)->recent($days)->read()->count();

        return $total > 0 ? round(($read / $total) * 100, 2) : 0;
    }
}