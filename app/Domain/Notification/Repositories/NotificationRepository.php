<?php

namespace App\Domain\Notification\Repositories;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Models\NotificationDelivery;
use App\Enums\NotificationType;
use App\Enums\NotificationStatus;
use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Репозиторий для работы с уведомлениями
 */
class NotificationRepository
{
    protected Notification $model;

    public function __construct(Notification $model)
    {
        $this->model = $model;
    }

    /**
     * Найти уведомление по ID
     */
    public function find(int $id): ?Notification
    {
        return $this->model->find($id);
    }

    /**
     * Найти уведомление по ID с проверкой
     */
    public function findOrFail(int $id): Notification
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Создать уведомление
     */
    public function create(array $data): Notification
    {
        return $this->model->create($data);
    }

    /**
     * Обновить уведомление
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    /**
     * Удалить уведомление
     */
    public function delete(int $id): bool
    {
        return $this->model->where('id', $id)->delete();
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
        $query = $this->model->forUser($userId)
            ->with(['deliveries'])
            ->orderBy('created_at', 'desc');

        if ($unreadOnly) {
            $query->unread();
        }

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

        // Применяем фильтры
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
     * Пометить как прочитанное
     */
    public function markAsRead(int $id): bool
    {
        $notification = $this->find($id);
        if ($notification) {
            $notification->markAsRead();
            return true;
        }
        return false;
    }

    /**
     * Пометить все как прочитанные для пользователя
     */
    public function markAllAsReadForUser(int $userId): int
    {
        return $this->model->forUser($userId)
            ->unread()
            ->update([
                'read_at' => now(),
                'status' => NotificationStatus::READ,
            ]);
    }

    /**
     * Пометить уведомления определенного типа как прочитанные
     */
    public function markTypeAsReadForUser(int $userId, NotificationType $type): int
    {
        return $this->model->forUser($userId)
            ->byType($type)
            ->unread()
            ->update([
                'read_at' => now(),
                'status' => NotificationStatus::READ,
            ]);
    }

    /**
     * Удалить старые уведомления
     */
    public function deleteOld(int $days = 30): int
    {
        return $this->model->where('created_at', '<', now()->subDays($days))
            ->where('status', NotificationStatus::READ)
            ->delete();
    }

    /**
     * Удалить истекшие уведомления
     */
    public function deleteExpired(): int
    {
        return $this->model->expired()->delete();
    }

    /**
     * Получить статистику уведомлений
     */
    public function getStats(int $days = 7): array
    {
        $baseQuery = $this->model->recent($days);

        return [
            'total' => $baseQuery->count(),
            'by_type' => $baseQuery->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'by_status' => $baseQuery->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'by_priority' => $baseQuery->select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->pluck('count', 'priority')
                ->toArray(),
            'unread_count' => $baseQuery->unread()->count(),
            'delivery_rate' => $this->getDeliveryRate($days),
            'avg_processing_time' => $this->getAverageProcessingTime($days),
        ];
    }

    /**
     * Получить статистику по пользователю
     */
    public function getUserStats(int $userId, int $days = 30): array
    {
        $baseQuery = $this->model->forUser($userId)->recent($days);

        return [
            'total' => $baseQuery->count(),
            'unread' => $baseQuery->unread()->count(),
            'read' => $baseQuery->read()->count(),
            'by_type' => $baseQuery->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'engagement_rate' => $this->getUserEngagementRate($userId, $days),
        ];
    }

    /**
     * Получить коэффициент доставки
     */
    protected function getDeliveryRate(int $days): float
    {
        $total = $this->model->recent($days)->count();
        $delivered = $this->model->recent($days)->delivered()->count();

        return $total > 0 ? round(($delivered / $total) * 100, 2) : 0;
    }

    /**
     * Получить среднее время обработки
     */
    protected function getAverageProcessingTime(int $days): ?float
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
    protected function getUserEngagementRate(int $userId, int $days): float
    {
        $total = $this->model->forUser($userId)->recent($days)->count();
        $read = $this->model->forUser($userId)->recent($days)->read()->count();

        return $total > 0 ? round(($read / $total) * 100, 2) : 0;
    }

    /**
     * Поиск уведомлений
     */
    public function search(array $criteria): Collection
    {
        $query = $this->model->with(['user', 'deliveries']);

        if (isset($criteria['user_id'])) {
            $query->forUser($criteria['user_id']);
        }

        if (isset($criteria['type'])) {
            $query->byType(NotificationType::from($criteria['type']));
        }

        if (isset($criteria['status'])) {
            $query->where('status', NotificationStatus::from($criteria['status']));
        }

        if (isset($criteria['priority'])) {
            $query->byPriority($criteria['priority']);
        }

        if (isset($criteria['title'])) {
            $query->where('title', 'like', '%' . $criteria['title'] . '%');
        }

        if (isset($criteria['message'])) {
            $query->where('message', 'like', '%' . $criteria['message'] . '%');
        }

        if (isset($criteria['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($criteria['date_from']));
        }

        if (isset($criteria['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($criteria['date_to']));
        }

        if (isset($criteria['group_key'])) {
            $query->grouped($criteria['group_key']);
        }

        return $query->orderBy('created_at', 'desc')->get();
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
     * Получить активных пользователей по уведомлениям
     */
    public function getActiveUsers(int $days = 7): Collection
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
     * Batch операции
     */
    public function batchCreate(array $notifications): Collection
    {
        $created = collect();

        DB::transaction(function() use ($notifications, $created) {
            foreach ($notifications as $data) {
                $created->push($this->create($data));
            }
        });

        return $created;
    }

    public function batchMarkAsRead(array $ids): int
    {
        return $this->model->whereIn('id', $ids)
            ->unread()
            ->update([
                'read_at' => now(),
                'status' => NotificationStatus::READ,
            ]);
    }

    public function batchDelete(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }
}