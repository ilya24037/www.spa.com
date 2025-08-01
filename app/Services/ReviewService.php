<?php

namespace App\Services;

use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\User;
use App\Repositories\ReviewRepository;
use App\Enums\ReviewStatus;
use App\Enums\ReviewType;
use App\Enums\ReviewRating;
use App\DTOs\Review\CreateReviewDTO;
use App\DTOs\Review\UpdateReviewDTO;
use App\Services\NotificationService;
use App\Enums\NotificationType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * Сервис для работы с отзывами
 */
class ReviewService
{
    protected ReviewRepository $repository;
    protected NotificationService $notificationService;

    public function __construct(
        ReviewRepository $repository,
        NotificationService $notificationService
    ) {
        $this->repository = $repository;
        $this->notificationService = $notificationService;
    }

    /**
     * Создать отзыв
     */
    public function create(CreateReviewDTO $dto): Review
    {
        try {
            DB::beginTransaction();

            // Проверяем, нет ли уже отзыва от этого пользователя
            if ($this->hasUserReviewed($dto->userId, $dto->reviewableType, $dto->reviewableId)) {
                throw new \InvalidArgumentException('User already reviewed this item');
            }

            // Создаем отзыв
            $review = $this->repository->create([
                'user_id' => $dto->userId,
                'reviewable_type' => $dto->reviewableType,
                'reviewable_id' => $dto->reviewableId,
                'booking_id' => $dto->bookingId,
                'type' => $dto->type,
                'status' => $this->determineInitialStatus($dto),
                'rating' => $dto->rating,
                'title' => $dto->title,
                'comment' => $dto->comment,
                'pros' => $dto->pros,
                'cons' => $dto->cons,
                'photos' => $dto->photos,
                'is_anonymous' => $dto->isAnonymous,
                'is_verified' => $this->isVerifiedPurchase($dto),
                'is_recommended' => $dto->isRecommended,
                'metadata' => $dto->metadata,
            ]);

            DB::commit();

            // Отправляем уведомления
            $this->sendNotifications($review);

            Log::info('Review created', [
                'review_id' => $review->id,
                'user_id' => $dto->userId,
                'reviewable_type' => $dto->reviewableType,
                'reviewable_id' => $dto->reviewableId,
            ]);

            return $review;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create review', [
                'error' => $e->getMessage(),
                'user_id' => $dto->userId,
                'reviewable_type' => $dto->reviewableType,
                'reviewable_id' => $dto->reviewableId,
            ]);

            throw $e;
        }
    }

    /**
     * Обновить отзыв
     */
    public function update(int $reviewId, UpdateReviewDTO $dto, int $userId): Review
    {
        $review = $this->repository->findOrFail($reviewId);

        // Проверяем права
        if ($review->user_id !== $userId) {
            throw new \UnauthorizedHttpException('You can only edit your own reviews');
        }

        if (!$review->canBeEdited()) {
            throw new \InvalidArgumentException('Review cannot be edited in current status');
        }

        $updateData = array_filter([
            'title' => $dto->title,
            'comment' => $dto->comment,
            'pros' => $dto->pros,
            'cons' => $dto->cons,
            'rating' => $dto->rating,
            'is_anonymous' => $dto->isAnonymous,
            'is_recommended' => $dto->isRecommended,
            'photos' => $dto->photos,
            'metadata' => $dto->metadata,
            'status' => ReviewStatus::PENDING, // Переводим на повторную модерацию
        ], fn($value) => $value !== null);

        $this->repository->update($reviewId, $updateData);

        Log::info('Review updated', [
            'review_id' => $reviewId,
            'user_id' => $userId,
        ]);

        return $this->repository->findOrFail($reviewId);
    }

    /**
     * Удалить отзыв
     */
    public function delete(int $reviewId, int $userId): bool
    {
        $review = $this->repository->findOrFail($reviewId);

        // Проверяем права
        if ($review->user_id !== $userId) {
            throw new \UnauthorizedHttpException('You can only delete your own reviews');
        }

        $result = $this->repository->delete($reviewId);

        if ($result) {
            Log::info('Review deleted', [
                'review_id' => $reviewId,
                'user_id' => $userId,
            ]);
        }

        return $result;
    }

    /**
     * Одобрить отзыв (модератор)
     */
    public function approve(int $reviewId, User $moderator): Review
    {
        $review = $this->repository->findOrFail($reviewId);
        
        $review->approve($moderator);

        // Уведомляем автора
        $this->notificationService->create(
            \App\DTOs\Notification\CreateNotificationDTO::forUser(
                $review->user_id,
                NotificationType::REVIEW_RECEIVED,
                'Отзыв одобрен',
                'Ваш отзыв прошел модерацию и опубликован',
                [
                    'review_id' => $review->id,
                    'action_url' => route('reviews.show', $review->id),
                ]
            )
        );

        Log::info('Review approved', [
            'review_id' => $reviewId,
            'moderator_id' => $moderator->id,
        ]);

        return $review;
    }

    /**
     * Отклонить отзыв (модератор)
     */
    public function reject(int $reviewId, User $moderator, ?string $reason = null): Review
    {
        $review = $this->repository->findOrFail($reviewId);
        
        $review->reject($moderator, $reason);

        // Уведомляем автора
        $this->notificationService->create(
            \App\DTOs\Notification\CreateNotificationDTO::forUser(
                $review->user_id,
                NotificationType::REVIEW_RECEIVED,
                'Отзыв отклонен',
                'Ваш отзыв не прошел модерацию. Причина: ' . ($reason ?: 'Не указана'),
                [
                    'review_id' => $review->id,
                    'reason' => $reason,
                ]
            )
        );

        Log::info('Review rejected', [
            'review_id' => $reviewId,
            'moderator_id' => $moderator->id,
            'reason' => $reason,
        ]);

        return $review;
    }

    /**
     * Пожаловаться на отзыв
     */
    public function flag(int $reviewId, User $flagger, string $reason): Review
    {
        $review = $this->repository->findOrFail($reviewId);
        
        if ($review->user_id === $flagger->id) {
            throw new \InvalidArgumentException('You cannot flag your own review');
        }

        $review->flag($flagger, $reason);

        Log::info('Review flagged', [
            'review_id' => $reviewId,
            'flagger_id' => $flagger->id,
            'reason' => $reason,
        ]);

        return $review;
    }

    /**
     * Ответить на отзыв
     */
    public function reply(int $reviewId, int $userId, string $reply, bool $isOfficial = false): ReviewReply
    {
        $review = $this->repository->findOrFail($reviewId);

        if (!$review->canBeReplied()) {
            throw new \InvalidArgumentException('Cannot reply to this review');
        }

        $replyData = [
            'user_id' => $userId,
            'reply' => $reply,
            'is_official' => $isOfficial,
            'status' => $isOfficial ? ReviewStatus::PENDING : ReviewStatus::APPROVED,
        ];

        $reviewReply = $this->repository->createReply($reviewId, $replyData);

        // Уведомляем автора отзыва
        if ($review->user_id !== $userId) {
            $this->notificationService->create(
                \App\DTOs\Notification\CreateNotificationDTO::forUser(
                    $review->user_id,
                    NotificationType::REVIEW_RESPONSE,
                    'Ответ на отзыв',
                    'На ваш отзыв дан ответ',
                    [
                        'review_id' => $review->id,
                        'reply_id' => $reviewReply->id,
                        'action_url' => route('reviews.show', $review->id),
                    ]
                )
            );
        }

        Log::info('Review reply created', [
            'review_id' => $reviewId,
            'reply_id' => $reviewReply->id,
            'user_id' => $userId,
            'is_official' => $isOfficial,
        ]);

        return $reviewReply;
    }

    /**
     * Отметить отзыв как полезный
     */
    public function markAsHelpful(int $reviewId, int $userId): void
    {
        $review = $this->repository->findOrFail($reviewId);
        
        if ($review->user_id === $userId) {
            throw new \InvalidArgumentException('You cannot rate your own review');
        }

        $reaction = $this->repository->createReaction($reviewId, $userId, true);

        // Обновляем счетчики
        if ($reaction->wasRecentlyCreated) {
            $review->increment('helpful_count');
        } elseif (!$reaction->is_helpful) {
            $review->increment('helpful_count');
            $review->decrement('not_helpful_count');
        }

        Log::info('Review marked as helpful', [
            'review_id' => $reviewId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Отметить отзыв как бесполезный
     */
    public function markAsNotHelpful(int $reviewId, int $userId): void
    {
        $review = $this->repository->findOrFail($reviewId);
        
        if ($review->user_id === $userId) {
            throw new \InvalidArgumentException('You cannot rate your own review');
        }

        $reaction = $this->repository->createReaction($reviewId, $userId, false);

        // Обновляем счетчики
        if ($reaction->wasRecentlyCreated) {
            $review->increment('not_helpful_count');
        } elseif ($reaction->is_helpful) {
            $review->increment('not_helpful_count');
            $review->decrement('helpful_count');
        }

        Log::info('Review marked as not helpful', [
            'review_id' => $reviewId,
            'user_id' => $userId,
        ]);
    }

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
     * Получить статистику отзывов для сущности
     */
    public function getReviewableStats(string $type, int $id): array
    {
        return $this->repository->getReviewableStats($type, $id);
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
     * Получить отзывы на модерации
     */
    public function getPendingModeration(int $limit = 50): Collection
    {
        return $this->repository->getPendingModeration($limit);
    }

    /**
     * Получить отзывы по жалобам
     */
    public function getFlagged(int $limit = 50): Collection
    {
        return $this->repository->getFlagged($limit);
    }

    /**
     * Batch операции для модераторов
     */
    public function batchApprove(array $ids, User $moderator): int
    {
        $count = $this->repository->batchApprove($ids);

        Log::info('Batch approve reviews', [
            'count' => $count,
            'moderator_id' => $moderator->id,
            'review_ids' => $ids,
        ]);

        return $count;
    }

    public function batchReject(array $ids, User $moderator, ?string $reason = null): int
    {
        $count = $this->repository->batchReject($ids, $reason);

        Log::info('Batch reject reviews', [
            'count' => $count,
            'moderator_id' => $moderator->id,
            'reason' => $reason,
            'review_ids' => $ids,
        ]);

        return $count;
    }

    /**
     * Получить статистику
     */
    public function getStats(int $days = 30): array
    {
        return $this->repository->getStats($days);
    }

    /**
     * Очистка старых отзывов
     */
    public function cleanup(): array
    {
        $deletedOld = $this->repository->cleanupOld(365);

        return [
            'deleted_old' => $deletedOld,
        ];
    }

    // ============ HELPER METHODS ============

    /**
     * Проверить, оставлял ли пользователь отзыв
     */
    protected function hasUserReviewed(int $userId, string $type, int $id): bool
    {
        return Review::where('user_id', $userId)
            ->where('reviewable_type', $type)
            ->where('reviewable_id', $id)
            ->exists();
    }

    /**
     * Определить начальный статус отзыва
     */
    protected function determineInitialStatus(CreateReviewDTO $dto): ReviewStatus
    {
        // Жалобы требуют обязательной модерации
        if ($dto->type === ReviewType::COMPLAINT) {
            return ReviewStatus::PENDING;
        }

        // Отзывы с низким рейтингом требуют модерации
        if ($dto->rating && $dto->rating->value <= 2) {
            return ReviewStatus::PENDING;
        }

        // По умолчанию - требует модерации
        return ReviewStatus::PENDING;
    }

    /**
     * Проверить, является ли покупка подтвержденной
     */
    protected function isVerifiedPurchase(CreateReviewDTO $dto): bool
    {
        return $dto->bookingId !== null;
    }

    /**
     * Отправить уведомления о новом отзыве
     */
    protected function sendNotifications(Review $review): void
    {
        try {
            // Уведомляем владельца объекта отзыва
            if ($review->reviewable && method_exists($review->reviewable, 'getOwnerId')) {
                $ownerId = $review->reviewable->getOwnerId();
                
                if ($ownerId && $ownerId !== $review->user_id) {
                    $this->notificationService->create(
                        \App\DTOs\Notification\CreateNotificationDTO::forUser(
                            $ownerId,
                            NotificationType::REVIEW_RECEIVED,
                            'Новый отзыв',
                            'Вы получили новый отзыв',
                            [
                                'review_id' => $review->id,
                                'rating' => $review->rating?->value,
                                'action_url' => route('reviews.show', $review->id),
                            ]
                        )
                    );
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to send review notifications', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}