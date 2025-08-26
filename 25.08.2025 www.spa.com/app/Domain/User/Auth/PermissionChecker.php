<?php

namespace App\Domain\User\Auth;

use App\Domain\User\Models\User;

/**
 * Проверщик прав доступа и разрешений
 */
class PermissionChecker
{
    /**
     * Проверка прав доступа
     */
    public function checkPermission(User $user, string $permission): bool
    {
        return $user->hasPermission($permission);
    }

    /**
     * Проверка может ли пользователь выполнить действие
     */
    public function canPerformAction(User $user, string $action, $resource = null): bool
    {
        // Основные проверки
        if (!$user->isActive()) {
            return false;
        }

        if (!$user->hasVerifiedEmail()) {
            return false;
        }

        // Проверяем специфические права
        switch ($action) {
            case 'create_ad':
                return $user->isMaster() && $user->hasPermission('create_ads');
                
            case 'create_booking':
                return $user->isClient() && $user->hasPermission('create_bookings');
                
            case 'moderate_content':
                return $user->isStaff() && $user->hasPermission('moderate_content');
                
            case 'admin_access':
                return $user->isAdmin();
                
            default:
                return $user->hasPermission($action);
        }
    }

    /**
     * Проверить может ли пользователь редактировать ресурс
     */
    public function canEdit(User $user, $resource): bool
    {
        // Админы могут редактировать всё
        if ($user->isAdmin()) {
            return true;
        }

        // Пользователь может редактировать свои ресурсы
        if (method_exists($resource, 'user_id')) {
            return $resource->user_id === $user->id;
        }

        if (method_exists($resource, 'owner_id')) {
            return $resource->owner_id === $user->id;
        }

        return false;
    }

    /**
     * Проверить может ли пользователь просматривать ресурс
     */
    public function canView(User $user, $resource): bool
    {
        // Админы и модераторы могут просматривать всё
        if ($user->isAdmin() || $user->isStaff()) {
            return true;
        }

        // Проверяем публичность ресурса
        if (method_exists($resource, 'is_public')) {
            return $resource->is_public;
        }

        // Владелец может просматривать свой ресурс
        return $this->canEdit($user, $resource);
    }

    /**
     * Проверить может ли пользователь удалить ресурс
     */
    public function canDelete(User $user, $resource): bool
    {
        // Только админы и владельцы могут удалять
        return $user->isAdmin() || $this->canEdit($user, $resource);
    }
}