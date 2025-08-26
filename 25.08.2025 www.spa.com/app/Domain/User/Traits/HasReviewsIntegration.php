<?php

namespace App\Domain\User\Traits;

use App\Application\Services\Integration\UserReviewsIntegrationService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Трейт для интеграции с отзывами через сервисы
 * Заменяет HasReviews трейт с прямыми связями
 * СОБЛЮДАЕТ DDD ПРИНЦИПЫ - нет прямых импортов моделей других доменов
 */
trait HasReviewsIntegration
{
    /**
     * Получить все отзывы о пользователе (для мастеров)
     * Заменяет: $this->receivedReviews()
     */
    public function getReceivedReviews(): Collection
    {
        return app(UserReviewsIntegrationService::class)->getUserReceivedReviews($this->id);
    }

    /**
     * Получить все отзывы написанные пользователем (для клиентов)
     * Заменяет: $this->writtenReviews()
     */
    public function getWrittenReviews(): Collection
    {
        return app(UserReviewsIntegrationService::class)->getUserWrittenReviews($this->id);
    }

    /**
     * Получить количество полученных отзывов
     * Заменяет: $this->receivedReviews()->count()
     */
    public function getReceivedReviewsCount(): int
    {
        return app(UserReviewsIntegrationService::class)->getUserReceivedReviewsCount($this->id);
    }

    /**
     * Получить количество написанных отзывов
     * Заменяет: $this->writtenReviews()->count()
     */
    public function getWrittenReviewsCount(): int
    {
        return app(UserReviewsIntegrationService::class)->getUserWrittenReviewsCount($this->id);
    }

    /**
     * Получить средний рейтинг пользователя (для мастеров)
     * Заменяет: $this->receivedReviews()->avg('rating')
     */
    public function getAverageRating(): float
    {
        return app(UserReviewsIntegrationService::class)->getUserAverageRating($this->id);
    }

    /**
     * Получить рейтинг пользователя в формате звезд
     */
    public function getStarRating(int $precision = 1): float
    {
        return round($this->getAverageRating(), $precision);
    }

    /**
     * Проверить может ли пользователь оставить отзыв
     * Новый метод с бизнес-логикой (права, лимиты)
     */
    public function canWriteReview(int $targetUserId, ?int $bookingId = null): bool
    {
        return app(UserReviewsIntegrationService::class)->canUserWriteReview($this->id, $targetUserId, $bookingId);
    }

    /**
     * Проверить есть ли отзыв от пользователя о целевом пользователе
     * Заменяет: $this->writtenReviews()->where('reviewed_user_id', $userId)->exists()
     */
    public function hasReviewedUser(int $targetUserId): bool
    {
        return app(UserReviewsIntegrationService::class)->userHasReviewedUser($this->id, $targetUserId);
    }

    /**
     * Создать новый отзыв через событие
     * ВАЖНО: Не создает напрямую, а отправляет событие!
     */
    public function writeReview(int $targetUserId, array $reviewData): bool
    {
        return app(UserReviewsIntegrationService::class)->createUserReview($this->id, $targetUserId, $reviewData);
    }

    /**
     * Обновить отзыв через событие
     * ВАЖНО: Не обновляет напрямую, а отправляет событие!
     */
    public function updateReview(int $reviewId, array $reviewData): bool
    {
        return app(UserReviewsIntegrationService::class)->updateUserReview($this->id, $reviewId, $reviewData);
    }

    /**
     * Удалить отзыв через событие
     */
    public function deleteReview(int $reviewId): bool
    {
        return app(UserReviewsIntegrationService::class)->deleteUserReview($this->id, $reviewId);
    }

    /**
     * Получить недавние отзывы
     * Новый метод для аналитики
     */
    public function getRecentReceivedReviews(int $limit = 10): Collection
    {
        return app(UserReviewsIntegrationService::class)->getRecentUserReceivedReviews($this->id, $limit);
    }

    /**
     * Получить статистику отзывов
     * Новый метод для аналитики
     */
    public function getReviewsStatistics(): array
    {
        return app(UserReviewsIntegrationService::class)->getUserReviewsStatistics($this->id);
    }

    /**
     * Получить детальную статистику рейтинга (распределение по звездам)
     * Новый метод для аналитики
     */
    public function getRatingBreakdown(): array
    {
        return app(UserReviewsIntegrationService::class)->getUserRatingBreakdown($this->id);
    }

    /**
     * Получить отзывы с высоким рейтингом
     * Новый метод для маркетинга
     */
    public function getHighRatedReviews(int $minRating = 4, int $limit = 5): Collection
    {
        return app(UserReviewsIntegrationService::class)->getUserHighRatedReviews($this->id, $minRating, $limit);
    }

    /**
     * DEPRECATED методы для обратной совместимости
     * Постепенно удалим после рефакторинга всех вызовов
     */

    /**
     * @deprecated Используйте getReceivedReviews()
     */
    public function receivedReviews()
    {
        return $this->getReceivedReviews();
    }

    /**
     * @deprecated Используйте getWrittenReviews()
     */
    public function writtenReviews()
    {
        return $this->getWrittenReviews();
    }

    /**
     * @deprecated Используйте getReceivedReviews()
     */
    public function reviews()
    {
        return $this->getReceivedReviews();
    }

    /**
     * @deprecated Используйте getReceivedReviewsCount()
     */
    public function getReviewsCountAttribute(): int
    {
        return $this->getReceivedReviewsCount();
    }

    /**
     * @deprecated Используйте getAverageRating()
     */
    public function getRatingAttribute(): float
    {
        return $this->getAverageRating();
    }

    /**
     * @deprecated Используйте hasReviewedUser($userId)
     */
    public function hasReviewFor(int $userId): bool
    {
        return $this->hasReviewedUser($userId);
    }
}