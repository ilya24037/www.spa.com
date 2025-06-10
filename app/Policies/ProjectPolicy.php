<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
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
    public function view(User $user, Project $project): bool
    {
        // Пользователь может просматривать проект, если он участник
        return $project->members()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        // Обновлять может владелец или менеджер
        $member = $project->members()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$member) {
            return false;
        }

        return in_array($member->role, ['owner', 'manager']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        // Удалять может только владелец
        return $project->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return $project->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return false; // Никто не может удалять навсегда
    }

    /**
     * Determine whether the user can manage project members.
     */
    public function manageMember(User $user, Project $project): bool
    {
        $member = $project->members()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$member) {
            return false;
        }

        return $member->hasPermission('manage_members');
    }

    /**
     * Determine whether the user can manage project tasks.
     */
    public function manageTask(User $user, Project $project): bool
    {
        $member = $project->members()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$member) {
            return false;
        }

        return $member->hasPermission('manage_tasks');
    }

    /**
     * Determine whether the user can view project reports.
     */
    public function viewReports(User $user, Project $project): bool
    {
        $member = $project->members()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$member) {
            return false;
        }

        return $member->hasPermission('view_reports');
    }
}