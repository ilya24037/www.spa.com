<?php

namespace App\Domain\Notification\Repositories\Services;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Repositories\Queries\NotificationQueryBuilder;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Сервис поиска и получения уведомлений
 */
class NotificationSearchService
{
    protected Notification $model;
    protected NotificationQueryBuilder $queryBuilder;

    public function __construct(Notification $model)
    {
        $this->model = $model;
        $this->queryBuilder = new NotificationQueryBuilder();
    }

    /**
     * Получить уведомления пользователя
     */
    public function getForUser(
        int $userId, 
        int $limit = 20, 
        int $offset = 0,
        bool $unreadOnly = false
    ): Collection {
        $query = $this->queryBuilder->userNotificationsQuery($this->model, $userId, $unreadOnly);
        return $query->limit($limit)->offset($offset)->get();
    }

    /**
     * Получить уведомления пользователя с пагинацией
     */
    public function getPaginatedForUser(
        int $userId, 
        int $perPage = 20,
        array $filters = []
    ): LengthAwarePaginator {
        $query = $this->model->forUser($userId)
            ->with(['deliveries'])
            ->orderBy('created_at', 'desc');

        $query = $this->queryBuilder->applyFilters($query, $filters);
        return $query->paginate($perPage);
    }

    /**
     * Получить непрочитанные уведомления
     */
    public function getUnreadForUser(int $userId, int $limit = 50): Collection
    {
        return $this->model->forUser($userId)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить количество непрочитанных уведомлений
     */
    public function getUnreadCountForUser(int $userId): int
    {
        return $this->model->forUser($userId)->unread()->count();
    }

    /**
     * Получить уведомления готовые к отправке
     */
    public function getReadyToSend(int $limit = 100): Collection
    {
        return $this->model->readyToSend()
            ->with(['user', 'deliveries'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить запланированные уведомления
     */
    public function getScheduled(): Collection
    {
        return $this->model->scheduled()
            ->with(['user'])
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    /**
     * Получить неудачные уведомления для повтора
     */
    public function getFailedForRetry(int $limit = 50): Collection
    {
        return $this->model->failed()
            ->whereColumn('retry_count', '<', 'max_retries')
            ->with(['user', 'deliveries'])
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить уведомления по группе
     */
    public function getByGroup(string $groupKey): Collection
    {
        return $this->model->grouped($groupKey)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Получить последние уведомления по типу
     */
    public function getRecentByType(
        NotificationType $type, 
        int $days = 7, 
        int $limit = 100
    ): Collection {
        return $this->model->byType($type)
            ->recent($days)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Поиск уведомлений
     */
    public function search(array $criteria): Collection
    {
        $query = $this->queryBuilder->buildSearchQuery($this->model, $criteria);
        return $query->orderBy('created_at', 'desc')->get();
    }
}