<?php

namespace App\Application\Services\Integration;

use Illuminate\Database\Eloquent\Collection;

/**
 * Сервис интеграции User ↔ Reviews доменов
 * Заменяет прямые связи через трейт HasReviews
 * РЕФАКТОРИНГ: Большая часть логики вынесена в UserReviewsReader, UserReviewsWriter, ReviewValidator
 */
class UserReviewsIntegrationService
{
    private UserReviewsReader $reader;
    private UserReviewsWriter $writer;
    private ReviewValidator $validator;

    public function __construct(
        UserReviewsReader $reader,
        UserReviewsWriter $writer,
        ReviewValidator $validator
    ) {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->validator = $validator;
    }
    /**
     * Получить все отзывы о пользователе (для мастеров)
     * Заменяет: $user->receivedReviews()
     */
    public function getUserReceivedReviews(int $userId): Collection
    {
        return $this->reader->getUserReceivedReviews($userId);
    }

    /**
     * Получить все отзывы написанные пользователем
     * Заменяет: $user->writtenReviews()
     */
    public function getUserWrittenReviews(int $userId): Collection
    {
        return $this->reader->getUserWrittenReviews($userId);
    }

    /**
     * Получить количество полученных отзывов
     * Заменяет: $user->receivedReviews()->count()
     */
    public function getUserReceivedReviewsCount(int $userId): int
    {
        return $this->reader->getUserReceivedReviewsCount($userId);
    }

    /**
     * Получить количество написанных отзывов
     * Заменяет: $user->writtenReviews()->count()
     */
    public function getUserWrittenReviewsCount(int $userId): int
    {
        return $this->reader->getUserWrittenReviewsCount($userId);
    }

    /**
     * Получить средний рейтинг пользователя
     * Заменяет: $user->receivedReviews()->avg('rating')
     */
    public function getUserAverageRating(int $userId): float
    {
        return $this->reader->getUserAverageRating($userId);
    }

    /**
     * Проверить может ли пользователь оставить отзыв
     */
    public function canUserWriteReview(int $reviewerId, int $targetUserId, ?int $bookingId = null): bool
    {
        return $this->validator->canUserWriteReview($reviewerId, $targetUserId, $bookingId);
    }

    /**
     * Проверить есть ли отзыв от пользователя о целевом пользователе
     * Заменяет: $user->writtenReviews()->where('reviewed_user_id', $userId)->exists()
     */
    public function userHasReviewedUser(int $reviewerId, int $targetUserId): bool
    {
        return $this->validator->userHasReviewedUser($reviewerId, $targetUserId);
    }

    /**
     * Создать новый отзыв через событие
     * ВАЖНО: Не создает напрямую, а отправляет событие!
     */
    public function createUserReview(int $reviewerId, int $targetUserId, array $reviewData): bool
    {
        return $this->writer->createUserReview($reviewerId, $targetUserId, $reviewData);
    }

    /**
     * Обновить отзыв через событие
     * ВАЖНО: Не обновляет напрямую, а отправляет событие!
     */
    public function updateUserReview(int $userId, int $reviewId, array $reviewData): bool
    {
        return $this->writer->updateUserReview($userId, $reviewId, $reviewData);
    }

    /**
     * Удалить отзыв (мягкое удаление)
     */
    public function deleteUserReview(int $userId, int $reviewId): bool
    {
        return $this->writer->deleteUserReview($userId, $reviewId);
    }

    /**
     * Получить недавние отзывы о пользователе
     */
    public function getRecentUserReceivedReviews(int $userId, int $limit = 10): Collection
    {
        return $this->reader->getRecentUserReceivedReviews($userId, $limit);
    }

    /**
     * Получить статистику отзывов пользователя
     */
    public function getUserReviewsStatistics(int $userId): array
    {
        return $this->reader->getUserReviewsStatistics($userId);
    }

    /**
     * Получить детальную статистику рейтинга (распределение по звездам)
     */
    public function getUserRatingBreakdown(int $userId): array
    {
        return $this->reader->getUserRatingBreakdown($userId);
    }

    /**
     * Получить отзывы с высоким рейтингом
     */
    public function getUserHighRatedReviews(int $userId, int $minRating = 4, int $limit = 5): Collection
    {
        return $this->reader->getUserHighRatedReviews($userId, $minRating, $limit);
    }
}