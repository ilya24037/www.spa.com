<?php

namespace App\Policies;

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use Illuminate\Auth\Access\Response;

class AdPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Ad $ad): bool
    {
        return $user->id === $ad->user_id || $ad->isActive();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ad $ad): bool
    {
        return $user->id === $ad->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Ad $ad): bool
    {
        return $user->id === $ad->user_id;
    }

    /**
     * Determine whether the user can update any ad as admin.
     */
    public function updateAsAdmin(User $user, Ad $ad): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can perform bulk actions.
     */
    public function bulkAction(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can approve ads.
     */
    public function approve(User $user, Ad $ad): bool
    {
        return $user->hasPermission('moderate_ads') || $user->hasPermission('moderate_content');
    }

    /**
     * Determine whether the user can reject ads.
     */
    public function reject(User $user, Ad $ad): bool
    {
        return $user->hasPermission('moderate_ads') || $user->hasPermission('moderate_content');
    }

    /**
     * Determine whether the user can view all ads.
     */
    public function viewAllAds(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Ad $ad): bool
    {
        return $user->id === $ad->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Ad $ad): bool
    {
        return false; // Запрещаем полное удаление
    }
}
