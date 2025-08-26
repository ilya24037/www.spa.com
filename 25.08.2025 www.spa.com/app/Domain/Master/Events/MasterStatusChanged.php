<?php

namespace App\Domain\Master\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: Изменение статуса мастера
 * Для обновления видимости в каталоге и уведомлений
 */
class MasterStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int $userId ID пользователя-мастера
     * @param int $masterProfileId ID профиля мастера
     * @param string $oldStatus Старый статус
     * @param string $newStatus Новый статус
     * @param string|null $reason Причина изменения статуса
     * @param int|null $changedBy ID модератора, если статус изменен администрацией
     */
    public function __construct(
        public readonly int $userId,
        public readonly int $masterProfileId,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly ?string $reason = null,
        public readonly ?int $changedBy = null
    ) {}

    /**
     * Проверить, был ли мастер активирован
     */
    public function wasActivated(): bool
    {
        return $this->newStatus === 'active' && $this->oldStatus !== 'active';
    }

    /**
     * Проверить, был ли мастер деактивирован
     */
    public function wasDeactivated(): bool
    {
        return $this->oldStatus === 'active' && $this->newStatus !== 'active';
    }

    /**
     * Проверить, был ли мастер заблокирован
     */
    public function wasBlocked(): bool
    {
        return $this->newStatus === 'blocked';
    }

    /**
     * Проверить, изменение произведено модератором
     */
    public function wasChangedByModerator(): bool
    {
        return $this->changedBy !== null && $this->changedBy !== $this->userId;
    }

    /**
     * Получить данные для уведомлений
     */
    public function getNotificationData(): array
    {
        return [
            'user_id' => $this->userId,
            'profile_id' => $this->masterProfileId,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'reason' => $this->reason,
            'by_moderator' => $this->wasChangedByModerator(),
        ];
    }
}