<?php

namespace App\Domain\Notification\Repositories\Queries;

use App\Domain\Notification\Models\Notification;
use App\Enums\NotificationType;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Построитель запросов для уведомлений
 */
class NotificationQueryBuilder
{
    /**
     * Применить фильтры к запросу
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['type'])) {
            $query->byType(NotificationType::from($filters['type']));
        }

        if (isset($filters['status'])) {
            $query->where('status', NotificationStatus::from($filters['status']));
        }

        if (isset($filters['unread_only']) && $filters['unread_only']) {
            $query->unread();
        }

        if (isset($filters['priority'])) {
            $query->byPriority($filters['priority']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        if (isset($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        if (isset($filters['title'])) {
            $query->where('title', 'like', '%' . $filters['title'] . '%');
        }

        if (isset($filters['message'])) {
            $query->where('message', 'like', '%' . $filters['message'] . '%');
        }

        if (isset($filters['group_key'])) {
            $query->grouped($filters['group_key']);
        }

        return $query;
    }

    /**
     * Построить запрос для поиска
     */
    public function buildSearchQuery(Notification $model, array $criteria): Builder
    {
        $query = $model->with(['user', 'deliveries']);
        return $this->applyFilters($query, $criteria);
    }

    /**
     * Запрос для пользовательских уведомлений
     */
    public function userNotificationsQuery(
        Notification $model, 
        int $userId, 
        bool $unreadOnly = false
    ): Builder {
        $query = $model->forUser($userId)
            ->with(['deliveries'])
            ->orderBy('created_at', 'desc');

        if ($unreadOnly) {
            $query->unread();
        }

        return $query;
    }
}