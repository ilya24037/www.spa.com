<?php

namespace App\Policies;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Models\User;
use App\Enums\UserRole;

class MasterProfilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Администраторы и модераторы могут видеть все профили мастеров
        return $user->role->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MasterProfile $masterProfile): bool
    {
        // Мастер может видеть свой профиль
        // Администраторы могут видеть любой профиль
        // Клиенты могут видеть активные профили
        return $user->id === $masterProfile->user_id
            || $user->role->isAdmin()
            || ($masterProfile->is_active && $masterProfile->is_verified);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Только мастера могут создавать свои профили
        // Администраторы могут создавать профили для других
        return $user->role === UserRole::MASTER || $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MasterProfile $masterProfile): bool
    {
        // Мастер может редактировать свой профиль
        // Администраторы могут редактировать любой профиль
        return $user->id === $masterProfile->user_id || $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MasterProfile $masterProfile): bool
    {
        // Мастер может удалить свой профиль
        // Администраторы могут удалять любые профили
        return $user->id === $masterProfile->user_id || $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MasterProfile $masterProfile): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MasterProfile $masterProfile): bool
    {
        // Запрещаем полное удаление профилей
        return false;
    }

    /**
     * Determine whether the user can verify master profiles.
     */
    public function verify(User $user, MasterProfile $masterProfile): bool
    {
        // Администраторы и модераторы могут верифицировать профили
        return $user->role->isAdmin();
    }

    /**
     * Determine whether the user can moderate master profiles.
     */
    public function moderate(User $user, MasterProfile $masterProfile): bool
    {
        // Администраторы и модераторы могут модерировать
        return $user->hasPermission('moderate_content') || $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can change premium status.
     */
    public function changePremiumStatus(User $user, MasterProfile $masterProfile): bool
    {
        // Только администраторы могут менять премиум статус
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can bulk action profiles.
     */
    public function bulkAction(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}