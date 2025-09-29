<?php

namespace App\Policies;

use App\Domain\User\Models\User;
use App\Enums\UserRole;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Администраторы и модераторы могут видеть всех пользователей
        return $user->role->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $targetUser): bool
    {
        // Пользователь может просматривать свой профиль
        // Администраторы могут видеть любого пользователя
        return $user->id === $targetUser->id || $user->role->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Только администраторы могут создавать пользователей через админку
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $targetUser): bool
    {
        // Пользователь может редактировать свой профиль
        // Администраторы могут редактировать любого пользователя
        return $user->id === $targetUser->id || $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $targetUser): bool
    {
        // Только администраторы могут удалять пользователей
        // Нельзя удалить самого себя
        return $user->role === UserRole::ADMIN && $user->id !== $targetUser->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $targetUser): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $targetUser): bool
    {
        // Запрещаем полное удаление пользователей
        return false;
    }

    /**
     * Determine whether the user can block/unblock other users.
     */
    public function blockUser(User $user, User $targetUser): bool
    {
        // Администраторы и модераторы могут блокировать пользователей
        // Нельзя заблокировать самого себя
        return $user->role->isAdmin() && $user->id !== $targetUser->id;
    }

    /**
     * Determine whether the user can change user roles.
     */
    public function changeRole(User $user, User $targetUser): bool
    {
        // Только администраторы могут менять роли
        // Нельзя изменить свою роль
        return $user->role === UserRole::ADMIN && $user->id !== $targetUser->id;
    }

    /**
     * Determine whether the user can verify other users.
     */
    public function verifyUser(User $user, User $targetUser): bool
    {
        // Администраторы и модераторы могут верифицировать пользователей
        return $user->role->isAdmin();
    }

    /**
     * Determine whether the user can bulk action users.
     */
    public function bulkAction(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}