<?php

declare(strict_types=1);

namespace App\Domain\Review\Repositories;

use App\Domain\Review\Models\ReviewAdapted as Review;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Репозиторий для работы с отзывами (адаптированный под текущую БД)
 */
class ReviewRepositoryNew
{
    public function __construct(
        private readonly Review $model
    ) {}

    /**
     * Найти отзыв по ID
     */
    public function findById(int $id): ?Review
    {
        return $this->model->find($id);
    }

    /**
     * Создать отзыв
     */
    public function create(array $data): Review
    {
        // Адаптируем данные под текущую структуру БД
        $adaptedData = [
            'reviewer_id' => $data['user_id'] ?? null,
            'reviewable_type' => 'App\\Domain\\User\\Models\\User',
            'reviewable_id' => $data['reviewable_user_id'] ?? null,
            'rating' => $data['rating'] ?? 5,
            'comment' => $data['comment'] ?? null,
            'booking_id' => $data['ad_id'] ?? null, // используем booking_id для ad_id
            'is_visible' => $data['is_visible'] ?? true,
            'is_verified' => $data['is_verified'] ?? false,
            'status' => 'approved', // по умолчанию одобрен
        ];
        
        return $this->model->create($adaptedData);
    }

    /**
     * Обновить отзыв
     */
    public function update(int $id, array $data): Review
    {
        $review = $this->findById($id);
        if ($review) {
            // Адаптируем данные
            $adaptedData = [];
            if (isset($data['rating'])) $adaptedData['rating'] = $data['rating'];
            if (isset($data['comment'])) $adaptedData['comment'] = $data['comment'];
            if (isset($data['is_visible'])) $adaptedData['is_visible'] = $data['is_visible'];
            
            $review->update($adaptedData);
        }
        return $review;
    }

    /**
     * Удалить отзыв
     */
    public function delete(int $id): bool
    {
        return (bool) $this->model->where('id', $id)->delete();
    }

    /**
     * Получить отзывы пользователя с пагинацией
     */
    public function getUserReviews(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->where('reviewable_type', 'App\\Domain\\User\\Models\\User')
            ->where('reviewable_id', $userId)
            ->where('is_visible', true)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Получить коллекцию отзывов пользователя
     */
    public function getUserReviewsCollection(int $userId): Collection
    {
        return $this->model
            ->where('reviewable_type', 'App\\Domain\\User\\Models\\User')
            ->where('reviewable_id', $userId)
            ->where('is_visible', true)
            ->get();
    }

    /**
     * Найти отзыв по автору и получателю
     */
    public function findByUserAndReviewable(int $userId, int $reviewableUserId, ?int $adId = null): ?Review
    {
        $query = $this->model
            ->where('reviewer_id', $userId)
            ->where('reviewable_type', 'App\\Domain\\User\\Models\\User')
            ->where('reviewable_id', $reviewableUserId);
        
        if ($adId) {
            $query->where('booking_id', $adId); // используем booking_id для ad_id
        }
        
        return $query->first();
    }

    /**
     * Получить все отзывы для пользователя
     */
    public function getAllReviewsForUser(int $userId): Collection
    {
        return $this->model
            ->where(function($query) use ($userId) {
                $query->where(function($q) use ($userId) {
                    $q->where('reviewable_type', 'App\\Domain\\User\\Models\\User')
                      ->where('reviewable_id', $userId);
                })->orWhere('reviewer_id', $userId);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Получить видимые отзывы
     */
    public function getVisibleReviews(int $limit = 20): Collection
    {
        return $this->model
            ->where('is_visible', true)
            ->where('is_verified', true)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}