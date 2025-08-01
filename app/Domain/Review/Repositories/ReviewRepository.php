<?php

namespace App\Domain\Review\Repositories;

use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\ReviewReaction;
use App\Enums\ReviewStatus;
use App\Enums\ReviewType;
use App\Enums\ReviewRating;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Репозиторий для работы с отзывами
 */
class ReviewRepository
{
    protected Review $model;

    public function __construct(Review $model)
    {
        $this->model = $model;
    }

    /**
     * Найти отзыв по ID
     */
    public function find(int $id): ?Review
    {
        return $this->model->find($id);
    }

    /**
     * Найти с проверкой
     */
    public function findOrFail(int $id): Review
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Создать отзыв
     */
    public function create(array $data): Review
    {
        return $this->model->create($data);
    }

    /**
     * Обновить отзыв
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    /**
     * Удалить отзыв
     */
    public function delete(int $id): bool
    {
        return $this->model->where('id', $id)->delete();
    }

    /**
     * Получить публичные отзывы
     */
    public function getPublic(int $limit = 20, int $offset = 0): Collection
    {
        return $this->model->public()
            ->with(['user', 'reviewable', 'replies.user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();
    }

    /**
     * Получить отзывы с пагинацией
     */
    public function getPaginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->with(['user', 'reviewable', 'replies.user']);

        // Применяем фильтры
        if (isset($filters['status'])) {
            $query->where('status', ReviewStatus::from($filters['status']));
        } else {
            $query->public(); // По умолчанию только публичные
        }

        if (isset($filters['type'])) {
            $query->byType(ReviewType::from($filters['type']));
        }

        if (isset($filters['rating'])) {
            $query->byRating(ReviewRating::fromValue($filters['rating']));
        }

        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (isset($filters['reviewable_type']) && isset($filters['reviewable_id'])) {
            $query->forReviewable($filters['reviewable_type'], $filters['reviewable_id']);
        }

        if (isset($filters['verified']) && $filters['verified']) {
            $query->verified();
        }

        if (isset($filters['with_photos']) && $filters['with_photos']) {
            $query->withPhotos();
        }

        if (isset($filters['rating_min']) && isset($filters['rating_max'])) {
            $query->whereBetween('rating', [$filters['rating_min'], $filters['rating_max']]);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('comment', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        if ($sortBy === 'popular') {
            $query->popular();
        } elseif ($sortBy === 'rating') {
            $query->orderBy('rating', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Получить отзывы для сущности
     */
    public function getForReviewable(
        string $type, 
        int $id, 
        int $limit = 20,
        array $filters = []
    ): Collection {
        $query = $this->model->forReviewable($type, $id)
            ->public()
            ->with(['user', 'replies.user']);

        if (isset($filters['rating'])) {
            $query->byRating(ReviewRating::fromValue($filters['rating']));
        }

        if (isset($filters['verified']) && $filters['verified']) {
            $query->verified();
        }

        if (isset($filters['with_photos']) && $filters['with_photos']) {
            $query->withPhotos();
        }

        return $query->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Получить отзывы пользователя
     */
    public function getUserReviews(int $userId, int $limit = 20): Collection
    {
        return $this->model->byUser($userId)
            ->with(['reviewable', 'replies.user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить отзывы на модерации
     */
    public function getPendingModeration(int $limit = 50): Collection
    {
        return $this->model->pending()
            ->with(['user', 'reviewable'])
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить отзывы по жалобам
     */
    public function getFlagged(int $limit = 50): Collection
    {
        return $this->model->where('status', ReviewStatus::FLAGGED)
            ->with(['user', 'reviewable', 'flagger'])
            ->orderBy('flagged_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить популярные отзывы
     */
    public function getPopular(int $days = 30, int $limit = 10): Collection
    {
        return $this->model->public()
            ->recent($days)
            ->popular()
            ->with(['user', 'reviewable'])
            ->limit($limit)
            ->get();
    }

    /**
     * Получить последние отзывы
     */
    public function getRecent(int $days = 7, int $limit = 20): Collection
    {
        return $this->model->public()
            ->recent($days)
            ->with(['user', 'reviewable'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить отзывы с фото
     */
    public function getWithPhotos(int $limit = 20): Collection
    {
        return $this->model->public()
            ->withPhotos()
            ->with(['user', 'reviewable'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Поиск отзывов
     */
    public function search(string $query, array $filters = [], int $limit = 20): Collection
    {
        $searchQuery = $this->model->public()
            ->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('comment', 'like', "%{$query}%");
            })
            ->with(['user', 'reviewable']);

        if (isset($filters['type'])) {
            $searchQuery->byType(ReviewType::from($filters['type']));
        }

        if (isset($filters['rating'])) {
            $searchQuery->byRating(ReviewRating::fromValue($filters['rating']));
        }

        return $searchQuery->orderBy('created_at', 'desc')
                          ->limit($limit)
                          ->get();
    }

    /**
     * Получить статистику отзывов
     */
    public function getStats(int $days = 30): array
    {
        $baseQuery = $this->model->recent($days);

        return [
            'total' => $baseQuery->count(),
            'by_status' => $baseQuery->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'by_rating' => $baseQuery->select('rating', DB::raw('count(*) as count'))
                ->groupBy('rating')
                ->pluck('count', 'rating')
                ->toArray(),
            'by_type' => $baseQuery->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'average_rating' => $baseQuery->avg('rating'),
            'positive_count' => $baseQuery->positive()->count(),
            'negative_count' => $baseQuery->negative()->count(),
            'with_photos' => $baseQuery->withPhotos()->count(),
            'verified' => $baseQuery->verified()->count(),
        ];
    }

    /**
     * Получить статистику для сущности
     */
    public function getReviewableStats(string $type, int $id): array
    {
        $reviews = $this->model->forReviewable($type, $id)->public();

        $ratings = $reviews->pluck('rating')->toArray();
        $averageRating = !empty($ratings) ? array_sum($ratings) / count($ratings) : 0;

        return [
            'total_count' => $reviews->count(),
            'average_rating' => round($averageRating, 1),
            'rating_distribution' => ReviewRating::getDistribution($ratings),
            'positive_count' => $reviews->positive()->count(),
            'negative_count' => $reviews->negative()->count(),
            'with_photos_count' => $reviews->withPhotos()->count(),
            'verified_count' => $reviews->verified()->count(),
            'recent_count' => $reviews->recent(7)->count(),
        ];
    }

    /**
     * Получить топ рецензентов
     */
    public function getTopReviewers(int $days = 30, int $limit = 10): array
    {
        return $this->model->recent($days)
            ->public()
            ->select('user_id', DB::raw('count(*) as review_count'), DB::raw('avg(rating) as avg_rating'))
            ->groupBy('user_id')
            ->orderBy('review_count', 'desc')
            ->with('user')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Создать ответ на отзыв
     */
    public function createReply(int $reviewId, array $data): ReviewReply
    {
        $data['review_id'] = $reviewId;
        return ReviewReply::create($data);
    }

    /**
     * Создать реакцию на отзыв
     */
    public function createReaction(int $reviewId, int $userId, bool $isHelpful): ReviewReaction
    {
        return ReviewReaction::updateOrCreate(
            ['review_id' => $reviewId, 'user_id' => $userId],
            ['is_helpful' => $isHelpful]
        );
    }

    /**
     * Batch операции
     */
    public function batchApprove(array $ids): int
    {
        return $this->model->whereIn('id', $ids)
            ->update([
                'status' => ReviewStatus::APPROVED,
                'moderated_at' => now(),
            ]);
    }

    public function batchReject(array $ids, ?string $reason = null): int
    {
        return $this->model->whereIn('id', $ids)
            ->update([
                'status' => ReviewStatus::REJECTED,
                'moderated_at' => now(),
                'moderation_notes' => $reason,
            ]);
    }

    public function batchDelete(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    /**
     * Очистка старых отзывов
     */
    public function cleanupOld(int $days = 365): int
    {
        return $this->model->where('created_at', '<', now()->subDays($days))
            ->where('status', ReviewStatus::REJECTED)
            ->delete();
    }
}