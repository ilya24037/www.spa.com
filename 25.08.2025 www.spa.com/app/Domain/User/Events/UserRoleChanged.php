<?php

namespace App\Domain\User\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: Изменение роли пользователя
 * Для обновления разрешений и связанных данных
 */
class UserRoleChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int $userId ID пользователя
     * @param string $oldRole Старая роль
     * @param string $newRole Новая роль
     * @param int|null $changedBy ID администратора, который изменил роль
     * @param string|null $reason Причина изменения роли
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $oldRole,
        public readonly string $newRole,
        public readonly ?int $changedBy = null,
        public readonly ?string $reason = null
    ) {}

    /**
     * Проверить, стал ли пользователь мастером
     */
    public function becameMaster(): bool
    {
        return $this->oldRole !== 'master' && $this->newRole === 'master';
    }

    /**
     * Проверить, перестал ли быть мастером
     */
    public function stoppedBeingMaster(): bool
    {
        return $this->oldRole === 'master' && $this->newRole !== 'master';
    }

    /**
     * Проверить, получил ли административные права
     */
    public function gainedAdminRights(): bool
    {
        $adminRoles = ['admin', 'moderator'];
        
        return !in_array($this->oldRole, $adminRoles) && 
               in_array($this->newRole, $adminRoles);
    }

    /**
     * Проверить, потерял ли административные права
     */
    public function lostAdminRights(): bool
    {
        $adminRoles = ['admin', 'moderator'];
        
        return in_array($this->oldRole, $adminRoles) && 
               !in_array($this->newRole, $adminRoles);
    }

    /**
     * Проверить, изменение произведено администратором
     */
    public function wasChangedByAdmin(): bool
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
            'old_role' => $this->oldRole,  
            'new_role' => $this->newRole,
            'reason' => $this->reason,
            'by_admin' => $this->wasChangedByAdmin(),
        ];
    }
}