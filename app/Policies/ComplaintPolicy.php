<?php

namespace App\Policies;

use App\Domain\Ad\Models\Complaint;
use App\Domain\User\Models\User;
use App\Enums\UserRole;

class ComplaintPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Администраторы и модераторы могут видеть все жалобы
        return $user->role->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Complaint $complaint): bool
    {
        // Создатель жалобы может её видеть
        // Администраторы и модераторы могут видеть все жалобы
        return $user->id === $complaint->user_id || $user->role->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Любой авторизованный пользователь может создать жалобу
        return $user->isActive();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Complaint $complaint): bool
    {
        // Создатель может редактировать жалобу только если она ещё не рассмотрена
        // Администраторы могут редактировать любые жалобы
        if ($user->role->isAdmin()) {
            return true;
        }

        return $user->id === $complaint->user_id && $complaint->status === 'pending';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Complaint $complaint): bool
    {
        // Создатель может удалить жалобу только если она ещё не рассмотрена
        // Администраторы могут удалять любые жалобы
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return $user->id === $complaint->user_id && $complaint->status === 'pending';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Complaint $complaint): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Complaint $complaint): bool
    {
        // Запрещаем полное удаление жалоб
        return false;
    }

    /**
     * Determine whether the user can resolve complaints.
     */
    public function resolve(User $user, Complaint $complaint): bool
    {
        // Администраторы и модераторы могут разрешать жалобы
        return $user->hasPermission('manage_complaints') || $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can reject complaints.
     */
    public function reject(User $user, Complaint $complaint): bool
    {
        // Администраторы и модераторы могут отклонять жалобы
        return $user->hasPermission('manage_complaints') || $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can assign complaints to moderators.
     */
    public function assign(User $user, Complaint $complaint): bool
    {
        // Только администраторы могут назначать жалобы модераторам
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can bulk action complaints.
     */
    public function bulkAction(User $user): bool
    {
        return $user->role->isAdmin();
    }
}