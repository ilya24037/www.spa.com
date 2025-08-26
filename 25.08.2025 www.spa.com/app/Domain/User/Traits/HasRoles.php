<?php

namespace App\Domain\User\Traits;

use App\Enums\UserRole;
use App\Enums\UserStatus;

/**
 * Трейт для работы с ролями пользователя
 */
trait HasRoles
{
    /**
     * Проверка роли
     */
    public function hasRole(string $role): bool
    {
        return $this->role->value === $role;
    }

    /**
     * Проверка множественных ролей
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role->value, $roles);
    }

    /**
     * Проверка разрешений на основе роли
     * Делегирует логику в UserRole enum для избежания дублирования
     */
    public function hasPermission(string $permission): bool
    {
        return $this->role->hasPermission($permission);
    }

    /**
     * Изменить роль пользователя
     */
    public function changeRole(UserRole $newRole): bool
    {
        $this->role = $newRole;
        return $this->save();
    }

    /**
     * Проверка, является ли пользователь мастером
     */
    public function isMaster(): bool
    {
        return $this->role === UserRole::MASTER;
    }

    /**
     * Проверка, является ли пользователь клиентом
     */
    public function isClient(): bool
    {
        return $this->role === UserRole::CLIENT;
    }

    /**
     * Проверка, является ли пользователь администратором
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Проверка, является ли пользователь модератором
     */
    public function isModerator(): bool
    {
        return $this->role === UserRole::MODERATOR;
    }

    /**
     * Проверка, является ли пользователь администратором или модератором (staff)
     */
    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isModerator();
    }

    /**
     * Проверка, активен ли пользователь
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    /**
     * Получить разрешения пользователя на основе роли
     */
    public function getPermissions(): array
    {
        return $this->role->getPermissions();
    }

    /**
     * Проверить может ли пользователь создавать объявления
     */
    public function canCreateAds(): bool
    {
        return $this->role->canCreateAds();
    }

    /**
     * Проверить может ли пользователь создавать бронирования
     */
    public function canCreateBookings(): bool
    {
        return $this->role->canCreateBookings();
    }

    /**
     * Получить цвет роли для UI
     */
    public function getRoleColor(): string
    {
        return $this->role->getColor();
    }

    /**
     * Получить иконку роли для UI
     */
    public function getRoleIcon(): string
    {
        return $this->role->getIcon();
    }

    /**
     * Получить читаемое название роли
     */
    public function getRoleLabel(): string
    {
        return $this->role->getLabel();
    }

    /**
     * Проверить может ли пользователь модерировать контент
     */
    public function canModerateContent(): bool
    {
        return $this->hasPermission('moderate_ads') || $this->hasPermission('moderate_reviews');
    }

    /**
     * Проверить может ли пользователь управлять системой
     */
    public function canManageSystem(): bool
    {
        return $this->hasPermission('manage_system');
    }

    /**
     * Проверить может ли пользователь просматривать аналитику
     */
    public function canViewAnalytics(): bool
    {
        return $this->hasPermission('view_analytics');
    }
}