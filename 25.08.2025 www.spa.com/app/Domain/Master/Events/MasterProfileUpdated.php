<?php

namespace App\Domain\Master\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: Обновление профиля мастера
 * Для синхронизации данных между доменами
 */
class MasterProfileUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int $userId ID пользователя-мастера
     * @param int $masterProfileId ID профиля мастера
     * @param array $oldData Старые данные
     * @param array $newData Новые данные
     * @param array $changedFields Список измененных полей
     */
    public function __construct(
        public readonly int $userId,
        public readonly int $masterProfileId,
        public readonly array $oldData,
        public readonly array $newData,
        public readonly array $changedFields = []
    ) {}

    /**
     * Проверить, изменилось ли имя мастера
     */
    public function nameChanged(): bool
    {
        return in_array('name', $this->changedFields);
    }

    /**
     * Проверить, изменились ли услуги
     */
    public function servicesChanged(): bool
    {
        return in_array('services', $this->changedFields);
    }

    /**
     * Проверить, изменился ли статус
     */
    public function statusChanged(): bool
    {
        return in_array('status', $this->changedFields);
    }

    /**
     * Получить новое имя мастера
     */
    public function getNewName(): ?string
    {
        return $this->newData['name'] ?? null;
    }

    /**
     * Получить новый статус
     */
    public function getNewStatus(): ?string
    {
        return $this->newData['status'] ?? null;
    }

    /**
     * Получить данные для обновления кешей
     */
    public function getCacheUpdateData(): array
    {
        return [
            'user_id' => $this->userId,
            'profile_id' => $this->masterProfileId,
            'changed_fields' => $this->changedFields,
            'new_data' => $this->newData,
        ];
    }
}