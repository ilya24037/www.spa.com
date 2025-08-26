<?php

namespace App\Infrastructure\Listeners\User\Handlers;

use App\Infrastructure\Services\PermissionService;
use Illuminate\Support\Facades\Log;

/**
 * Менеджер разрешений пользователей
 */
class PermissionManager
{
    private PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Обновить права доступа пользователя
     */
    public function updateUserPermissions($user, string $newRole): void
    {
        // Удаляем старые права
        $this->permissionService->revokeAllPermissions($user);

        // Назначаем права для новой роли
        $permissions = $this->getPermissionsForRole($newRole);
        $this->permissionService->grantPermissions($user, $permissions);

        Log::info('User permissions updated', [
            'user_id' => $user->id,
            'new_role' => $newRole,
            'permissions_count' => count($permissions),
        ]);
    }

    /**
     * Получить права для роли
     */
    public function getPermissionsForRole(string $role): array
    {
        $permissions = [
            'client' => [
                'bookings:create',
                'bookings:view_own',
                'bookings:cancel_own',
                'reviews:create',
                'profile:update_own',
                'favorites:manage',
            ],
            
            'master' => [
                'bookings:create',
                'bookings:view_own',
                'bookings:cancel_own',
                'bookings:manage_assigned',
                'master_profile:manage_own',
                'calendar:manage_own',
                'reviews:view_own',
                'earnings:view_own',
                'services:manage_own',
            ],
            
            'admin' => [
                'users:*',
                'bookings:*',
                'masters:*',
                'reviews:*',
                'analytics:*',
                'system:*',
            ],
            
            'moderator' => [
                'users:view',
                'users:moderate',
                'masters:moderate',
                'reviews:moderate',
                'content:moderate',
            ],
            
            'support' => [
                'users:view',
                'bookings:view',
                'support:handle_tickets',
            ],
        ];

        return $permissions[$role] ?? [];
    }

    /**
     * Проверить, имеет ли пользователь разрешение
     */
    public function hasPermission($user, string $permission): bool
    {
        return $this->permissionService->hasPermission($user, $permission);
    }

    /**
     * Получить все разрешения пользователя
     */
    public function getUserPermissions($user): array
    {
        return $this->permissionService->getUserPermissions($user);
    }

    /**
     * Получить описания прав для роли
     */
    public function getPermissionDescriptions(string $role): array
    {
        $descriptions = [
            'client' => [
                'Создание и управление бронированиями',
                'Написание отзывов',
                'Управление избранными мастерами',
            ],
            'master' => [
                'Создание и управление профилем мастера',
                'Управление календарем и услугами',
                'Просмотр заработка и статистики',
                'Работа с клиентскими бронированиями',
            ],
            'admin' => [
                'Полный доступ к системе',
                'Управление пользователями',
                'Просмотр аналитики и отчетов',
            ],
            'moderator' => [
                'Модерация пользователей и контента',
                'Обработка жалоб и отчетов',
                'Управление содержимым платформы',
            ],
            'support' => [
                'Просмотр данных пользователей',
                'Обработка тикетов поддержки',
                'Помощь клиентам',
            ],
        ];

        return $descriptions[$role] ?? [];
    }
}