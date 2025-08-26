<?php

namespace App\Domain\Notification\Repositories\Services;

use App\Domain\Notification\Models\Notification;

/**
 * Базовые CRUD операции для уведомлений
 */
class NotificationCrudService
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
}