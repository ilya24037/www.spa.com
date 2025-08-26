<?php

namespace App\Support\Traits;

use App\Enums\UserRole;
use App\Enums\UserStatus;

/**
 * Trait для работы с ролями и статусами пользователя
 */
trait HasUserRoles
{
    /**
     * Проверить роль пользователя
     */
    public function hasRole(UserRole|string $role): bool
    {
        $roleValue = $role instanceof UserRole ? $role->value : $role;
        return $this->role === $roleValue;
    }

    /**
     * Проверить несколько ролей
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Получить объект роли
     */
    public function getRoleEnum(): ?UserRole
    {
        return $this->role ? UserRole::from($this->role) : null;
    }

    /**
     * Получить объект статуса
     */
    public function getStatusEnum(): ?UserStatus
    {
        return $this->status ? UserStatus::from($this->status) : null;
    }

    /**
     * Проверить статус пользователя
     */
    public function hasStatus(UserStatus|string $status): bool
    {
        $statusValue = $status instanceof UserStatus ? $status->value : $status;
        return $this->status === $statusValue;
    }

    /**
     * Проверить, является ли пользователь клиентом
     */
    public function isClient(): bool
    {
        return $this->hasRole(UserRole::CLIENT);
    }

    /**
     * Проверить, является ли пользователь мастером
     */
    public function isMaster(): bool
    {
        return $this->hasRole(UserRole::MASTER);
    }

    /**
     * Проверить, является ли пользователь администратором
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::ADMIN);
    }

    /**
     * Проверить, является ли пользователь модератором
     */
    public function isModerator(): bool
    {
        return $this->hasRole(UserRole::MODERATOR);
    }

    /**
     * Проверить, является ли пользователь администратором или модератором
     */
    public function isStaff(): bool
    {
        return $this->hasAnyRole([UserRole::ADMIN, UserRole::MODERATOR]);
    }

    /**
     * Проверить, активен ли пользователь
     */
    public function isActive(): bool
    {
        return $this->hasStatus(UserStatus::ACTIVE);
    }

    /**
     * Проверить, заблокирован ли пользователь
     */
    public function isBlocked(): bool
    {
        $status = $this->getStatusEnum();
        return $status?->isBlocked() ?? false;
    }

    /**
     * Проверить, может ли пользователь входить в систему
     */
    public function canLogin(): bool
    {
        $status = $this->getStatusEnum();
        return $status?->canLogin() ?? false;
    }

    /**
     * Проверить, может ли пользователь создавать контент
     */
    public function canCreateContent(): bool
    {
        $status = $this->getStatusEnum();
        return $status?->canCreateContent() ?? false;
    }

    /**
     * Проверить разрешение пользователя
     */
    public function hasPermission(string $permission): bool
    {
        $role = $this->getRoleEnum();
        return $role?->hasPermission($permission) ?? false;
    }

    /**
     * Получить все разрешения пользователя
     */
    public function getPermissions(): array
    {
        $role = $this->getRoleEnum();
        return $role?->getPermissions() ?? [];
    }

    /**
     * Получить читаемую роль
     */
    public function getRoleLabelAttribute(): string
    {
        $role = $this->getRoleEnum();
        return $role?->getLabel() ?? 'Неизвестная роль';
    }

    /**
     * Получить читаемый статус
     */
    public function getStatusLabelAttribute(): string
    {
        $status = $this->getStatusEnum();
        return $status?->getLabel() ?? 'Неизвестный статус';
    }

    /**
     * Изменить роль пользователя
     */
    public function changeRole(UserRole $newRole): bool
    {
        $this->role = $newRole->value;
        return $this->save();
    }

    /**
     * Изменить статус пользователя
     */
    public function changeStatus(UserStatus $newStatus): bool
    {
        $currentStatus = $this->getStatusEnum();
        
        // Проверяем, можно ли изменить статус
        if ($currentStatus && !in_array($newStatus, $currentStatus->getNextPossibleStatuses())) {
            return false;
        }

        $this->status = $newStatus->value;
        return $this->save();
    }

    /**
     * Активировать пользователя
     */
    public function activate(): bool
    {
        return $this->changeStatus(UserStatus::ACTIVE);
    }

    /**
     * Деактивировать пользователя
     */
    public function deactivate(): bool
    {
        return $this->changeStatus(UserStatus::INACTIVE);
    }

    /**
     * Заблокировать пользователя
     */
    public function suspend(): bool
    {
        return $this->changeStatus(UserStatus::SUSPENDED);
    }

    /**
     * Забанить пользователя
     */
    public function ban(): bool
    {
        return $this->changeStatus(UserStatus::BANNED);
    }

    /**
     * Удалить пользователя (мягкое удаление)
     */
    public function markAsDeleted(): bool
    {
        return $this->changeStatus(UserStatus::DELETED);
    }
}