<?php

namespace App\Domain\Review\Services;

use App\Domain\Review\Repositories\ReviewRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Обработчик запросов и поиска отзывов
 */
class ReviewQueryHandler
{
    public function __construct(
        private ReviewRepository $repository
    ) {}

    /**
     * Получить отзывы для сущности
     */
    public function getForReviewable(
        string $type, 
        int $id, 
        array $filters = [], 
        int $limit = 20
    ): Collection {
        return $this->repository->getForReviewable($type, $id, $limit, $filters);
    }

    /**
     * Получить отзывы пользователя
     */
    public function getUserReviews(int $userId, int $limit = 20): Collection
    {
        return $this->repository->getUserReviews($userId, $limit);
    }

    /**
     * Поиск отзывов
     */
    public function search(string $query, array $filters = [], int $limit = 20): Collection
    {
        return $this->repository->search($query, $filters, $limit);
    }

    /**
     * Получить популярные отзывы
     */
    public function getPopular(int $days = 30, int $limit = 10): Collection
    {
        return $this->repository->getPopular($days, $limit);
    }

    /**
     * Получить последние отзывы
     */
    public function getRecent(int $limit = 20): Collection
    {
        return $this->repository->getRecent($limit);
    }

    /**
     * Получить отзывы с высоким рейтингом
     */
    public function getHighRated(int $minRating = 4, int $limit = 20): Collection
    {
        return $this->repository->getHighRated($minRating, $limit);
    }

    /**
     * Получить отзывы с низким рейтингом
     */
    public function getLowRated(int $maxRating = 2, int $limit = 20): Collection
    {
        return $this->repository->getLowRated($maxRating, $limit);
    }

    /**
     * Получить верифицированные отзывы
     */
    public function getVerified(int $limit = 20): Collection
    {
        return $this->repository->getVerified($limit);
    }

    /**
     * Получить отзывы с фотографиями
     */
    public function getWithPhotos(int $limit = 20): Collection
    {
        return $this->repository->getWithPhotos($limit);
    }

    /**
     * Получить отзывы по категории
     */
    public function getByCategory(string $category, int $limit = 20): Collection
    {
        return $this->repository->getByCategory($category, $limit);
    }

    /**
     * Получить отзывы по тегам
     */
    public function getByTags(array $tags, int $limit = 20): Collection
    {
        return $this->repository->getByTags($tags, $limit);
    }

    /**
     * Получить отзывы за период
     */
    public function getForPeriod(\DateTime $start, \DateTime $end, int $limit = 50): Collection
    {
        return $this->repository->getForPeriod($start, $end, $limit);
    }

    /**
     * Получить избранные отзывы пользователя
     */
    public function getUserFavorites(int $userId, int $limit = 20): Collection
    {
        return $this->repository->getUserFavorites($userId, $limit);
    }

    /**
     * Получить отзывы с ответами
     */
    public function getWithReplies(int $limit = 20): Collection
    {
        return $this->repository->getWithReplies($limit);
    }

    /**
     * Получить похожие отзывы
     */
    public function getSimilar(int $reviewId, int $limit = 5): Collection
    {
        $review = $this->repository->findOrFail($reviewId);
        
        return $this->repository->getSimilar(
            $review->reviewable_type,
            $review->reviewable_id,
            $review->rating?->value,
            $reviewId,
            $limit
        );
    }

    /**
     * Получить статистику отзывов для сущности
     */
    public function getReviewableStats(string $type, int $id): array
    {
        return $this->repository->getReviewableStats($type, $id);
    }

    /**
     * Получить общую статистику
     */
    public function getGeneralStats(int $days = 30): array
    {
        return $this->repository->getStats($days);
    }

    /**
     * Получить статистику по рейтингам
     */
    public function getRatingDistribution(string $type, int $id): array
    {
        return $this->repository->getRatingDistribution($type, $id);
    }

    /**
     * Получить топ-отзывчиков
     */
    public function getTopReviewers(int $limit = 10, int $days = 30): array
    {
        return $this->repository->getTopReviewers($limit, $days);
    }

    /**
     * Получить отзывы требующие внимания
     */
    public function getNeedingAttention(): Collection
    {
        return $this->repository->getNeedingAttention();
    }

    /**
     * Получить трендовые отзывы
     */
    public function getTrending(int $limit = 10): Collection
    {
        return $this->repository->getTrending($limit);
    }

    /**
     * Построить фильтры для поиска
     */
    public function buildSearchFilters(array $params): array
    {
        $filters = [];

        // Фильтр по рейтингу
        if (!empty($params['rating'])) {
            $filters['rating'] = $params['rating'];
        }

        // Фильтр по минимальному рейтингу
        if (!empty($params['min_rating'])) {
            $filters['min_rating'] = $params['min_rating'];
        }

        // Фильтр по максимальному рейтингу
        if (!empty($params['max_rating'])) {
            $filters['max_rating'] = $params['max_rating'];
        }

        // Фильтр по периоду
        if (!empty($params['date_from'])) {
            $filters['date_from'] = $params['date_from'];
        }

        if (!empty($params['date_to'])) {
            $filters['date_to'] = $params['date_to'];
        }

        // Фильтр по верификации
        if (isset($params['verified'])) {
            $filters['verified'] = (bool) $params['verified'];
        }

        // Фильтр по наличию фото
        if (isset($params['with_photos'])) {
            $filters['with_photos'] = (bool) $params['with_photos'];
        }

        // Фильтр по статусу
        if (!empty($params['status'])) {
            $filters['status'] = $params['status'];
        }

        // Фильтр по типу
        if (!empty($params['type'])) {
            $filters['type'] = $params['type'];
        }

        // Сортировка
        if (!empty($params['sort'])) {
            $filters['sort'] = $params['sort'];
        }

        return $filters;
    }

    /**
     * Получить агрегированные данные для дашборда
     */
    public function getDashboardData(): array
    {
        return [
            'total_reviews' => $this->repository->getTotalCount(),
            'pending_reviews' => $this->repository->getPendingCount(),
            'today_reviews' => $this->repository->getTodayCount(),
            'average_rating' => $this->repository->getAverageRating(),
            'top_rated_items' => $this->repository->getTopRatedItems(5),
            'recent_reviews' => $this->getRecent(5),
            'flagged_count' => $this->repository->getFlaggedCount(),
        ];
    }

    /**
     * Экспорт отзывов в различных форматах
     */
    public function export(array $filters = [], string $format = 'csv'): array
    {
        $reviews = $this->repository->getForExport($filters);
        
        return [
            'data' => $reviews,
            'format' => $format,
            'count' => $reviews->count(),
            'exported_at' => now(),
        ];
    }
}