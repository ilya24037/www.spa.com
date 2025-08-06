<?php

namespace App\Domain\Notification\Repositories\Operations;

use App\Domain\Notification\Models\Notification;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Пакетные операции с уведомлениями
 */
class NotificationBatchOperations
{
    private Notification $model;

    public function __construct(Notification $model)
    {
        $this->model = $model;
    }

    /**
     * Пакетное создание уведомлений
     */
    public function batchCreate(array $notifications): Collection
    {
        $created = collect();

        DB::transaction(function() use ($notifications, $created) {
            foreach ($notifications as $data) {
                $created->push($this->model->create($data));
            }
        });

        return $created;
    }

    /**
     * Пакетная отметка как прочитанные
     */
    public function batchMarkAsRead(array $ids): int
    {
        return $this->model->whereIn('id', $ids)
            ->unread()
            ->update([
                'read_at' => now(),
                'status' => NotificationStatus::READ,
            ]);
    }

    /**
     * Пакетное удаление
     */
    public function batchDelete(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    /**
     * Отметить все как прочитанные для пользователя
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
     * Отметить тип как прочитанный для пользователя
     */
    public function markTypeAsReadForUser(int $userId, $type): int
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
}