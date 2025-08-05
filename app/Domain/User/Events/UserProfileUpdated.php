<?php

namespace App\Domain\User\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: Обновление профиля пользователя
 * Для синхронизации данных в других доменах
 */
class UserProfileUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int $userId ID пользователя
     * @param array $oldData Старые данные профиля
     * @param array $newData Новые данные профиля
     * @param array $changedFields Список измененных полей
     */
    public function __construct(
        public readonly int $userId,
        public readonly array $oldData,
        public readonly array $newData,
        public readonly array $changedFields = []
    ) {}

    /**
     * Проверить, изменилось ли имя
     */
    public function nameChanged(): bool
    {
        return in_array('name', $this->changedFields);
    }

    /**
     * Проверить, изменился ли телефон
     */
    public function phoneChanged(): bool
    {
        return in_array('phone', $this->changedFields);
    }

    /**
     * Проверить, изменился ли город
     */
    public function cityChanged(): bool
    {
        return in_array('city', $this->changedFields);
    }

    /**
     * Проверить, изменился ли аватар
     */
    public function avatarChanged(): bool
    {
        return in_array('avatar', $this->changedFields);
    }

    /**
     * Получить новое имя
     */
    public function getNewName(): ?string
    {
        return $this->newData['name'] ?? null;
    }

    /**
     * Получить новый телефон
     */
    public function getNewPhone(): ?string
    {
        return $this->newData['phone'] ?? null;
    }

    /**
     * Получить новый город
     */
    public function getNewCity(): ?string
    {
        return $this->newData['city'] ?? null;
    }

    /**
     * Получить URL нового аватара
     */
    public function getNewAvatar(): ?string
    {
        return $this->newData['avatar'] ?? null;
    }

    /**
     * Получить данные для обновления кешей
     */
    public function getCacheUpdateData(): array
    {
        return [
            'user_id' => $this->userId,
            'changed_fields' => $this->changedFields,
            'new_data' => array_intersect_key($this->newData, array_flip($this->changedFields)),
        ];
    }
}