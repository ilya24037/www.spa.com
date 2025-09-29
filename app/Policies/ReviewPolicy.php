<?php

namespace App\Policies;

use App\Domain\Review\Models\Review;
use App\Domain\User\Models\User;
use App\Enums\UserRole;

class ReviewPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Администраторы и модераторы могут видеть все отзывы
        return $user->role->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Review $review): bool
    {
        // Автор отзыва может его видеть
        // Получатель отзыва может его видеть
        // Администраторы могут видеть любые отзывы
        // Опубликованные отзывы могут видеть все
        return $user->id === $review->user_id
            || $user->id === $review->master_id
            || $user->role->isAdmin()
            || $review->is_published;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Только клиенты могут оставлять отзывы
        // Администраторы могут создавать отзывы через админку
        return $user->role === UserRole::CLIENT || $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Review $review): bool
    {
        // Автор может редактировать свой отзыв в течение определенного времени
        // Администраторы могут редактировать любые отзывы
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        // Автор может редактировать только свой неопубликованный отзыв
        return $user->id === $review->user_id && !$review->is_published;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Review $review): bool
    {
        // Автор может удалить свой отзыв
        // Администраторы могут удалять любые отзывы
        return $user->id === $review->user_id || $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Review $review): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Review $review): bool
    {
        // Запрещаем полное удаление отзывов
        return false;
    }

    /**
     * Determine whether the user can moderate reviews.
     */
    public function moderate(User $user, Review $review): bool
    {
        // Администраторы и модераторы могут модерировать отзывы
        return $user->hasPermission('moderate_reviews') || $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can publish reviews.
     */
    public function publish(User $user, Review $review): bool
    {
        // Администраторы и модераторы могут публиковать отзывы
        return $user->hasPermission('moderate_reviews') || $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can unpublish reviews.
     */
    public function unpublish(User $user, Review $review): bool
    {
        // Администраторы и модераторы могут снимать отзывы с публикации
        return $user->hasPermission('moderate_reviews') || $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can respond to reviews.
     */
    public function respond(User $user, Review $review): bool
    {
        // Мастер может отвечать на отзывы о себе
        // Администраторы могут отвечать на любые отзывы
        return $user->id === $review->master_id || $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can bulk action reviews.
     */
    public function bulkAction(User $user): bool
    {
        return $user->role->isAdmin();
    }
}