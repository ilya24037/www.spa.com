<?php

namespace App\Domain\User\Traits;

use App\Enums\UserRole;

/**
 * Трейт для работы с ролями пользователя
 */
trait HasRoles
{
    /**
     * Проверка множественных ролей
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role->value, $roles);
    }

    /**
     * Проверка разрешений на основе роли
     * Переименован в hasPermission чтобы избежать конфликта с Laravel User::can()
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = [
            UserRole::ADMIN->value => [
                'manage-users',
                'manage-masters',
                'manage-bookings',
                'manage-payments',
                'view-analytics',
                'moderate-content',
            ],
            UserRole::MASTER->value => [
                'manage-profile',
                'manage-schedule',
                'view-bookings',
                'manage-services',
                'view-earnings',
            ],
            UserRole::CLIENT->value => [
                'book-services',
                'leave-reviews',
                'manage-favorites',
                'view-history',
            ],
        ];

        $rolePermissions = $permissions[$this->role->value] ?? [];
        return in_array($permission, $rolePermissions);
    }

    /**
     * Изменить роль пользователя
     */
    public function changeRole(UserRole $newRole): bool
    {
        $this->role = $newRole;
        return $this->save();
    }
}