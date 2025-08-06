<?php

namespace App\Domain\Review\Services;

use App\Domain\Review\Models\Review;
use App\Domain\Review\Models\ReviewReply;
use App\Domain\User\Models\User;
use App\Domain\Review\Repositories\ReviewRepository;
use App\Domain\Review\DTOs\CreateReviewDTO;
use App\Domain\Review\DTOs\UpdateReviewDTO;
use App\Infrastructure\Notification\NotificationService;
use App\Enums\NotificationType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

/**
 * Упрощенный сервис для работы с отзывами
 * Делегирует сложную логику специализированным обработчикам
 */
class ReviewService
{
    public function __construct(
        private ReviewRepository $repository,
        private NotificationService $notificationService,
        private ReviewModerationHandler $moderationHandler,
        private ReviewInteractionHandler $interactionHandler,
        private ReviewQueryHandler $queryHandler,
        private ReviewValidationHandler $validationHandler
    ) {}

    /**
     * Создать отзыв
     */
    public function create(CreateReviewDTO $dto): Review
    {
        // Валидация через специализированный обработчик
        $errors = $this->validationHandler->validateCreateData($dto);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Validation failed: ' . implode(', ', $errors));
        }

        try {
            DB::beginTransaction();

            // Создаем отзыв
            $review = $this->repository->create([
                'user_id' => $dto->userId,
                'reviewable_type' => $dto->reviewableType,
                'reviewable_id' => $dto->reviewableId,
                'booking_id' => $dto->bookingId,
                'type' => $dto->type,
                'status' => $this->validationHandler->determineInitialStatus($dto),
                'rating' => $dto->rating,
                'title' => $dto->title,
                'comment' => $dto->comment,
                'pros' => $dto->pros,
                'cons' => $dto->cons,
                'photos' => $dto->photos,
                'is_anonymous' => $dto->isAnonymous,
                'is_verified' => $this->validationHandler->isVerifiedPurchase($dto),
                'is_recommended' => $dto->isRecommended,
                'metadata' => $dto->metadata,
            ]);

            DB::commit();

            // Отправляем уведомления
            $this->sendCreateNotifications($review);

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

        // Валидация через специализированный обработчик
        $errors = $this->validationHandler->validateUpdateData($review, $userId);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Validation failed: ' . implode(', ', $errors));
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
            'status' => \App\Enums\ReviewStatus::PENDING, // Переводим на повторную модерацию
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

    // ============ ДЕЛЕГИРОВАНИЕ К ОБРАБОТЧИКАМ ============

    /**
     * Модерация - делегируем к ModerationHandler
     */
    public function approve(int $reviewId, User $moderator): Review
    {
        return $this->moderationHandler->approve($reviewId, $moderator);
    }

    public function reject(int $reviewId, User $moderator, ?string $reason = null): Review
    {
        return $this->moderationHandler->reject($reviewId, $moderator, $reason);
    }

    public function flag(int $reviewId, User $flagger, string $reason): Review
    {
        return $this->moderationHandler->flag($reviewId, $flagger, $reason);
    }

    public function batchApprove(array $ids, User $moderator): int
    {
        return $this->moderationHandler->batchApprove($ids, $moderator);
    }

    public function batchReject(array $ids, User $moderator, ?string $reason = null): int
    {
        return $this->moderationHandler->batchReject($ids, $moderator, $reason);
    }

    public function getPendingModeration(int $limit = 50): Collection
    {
        return $this->moderationHandler->getPendingModeration($limit);
    }

    public function getFlagged(int $limit = 50): Collection
    {
        return $this->moderationHandler->getFlagged($limit);
    }

    /**
     * Взаимодействия - делегируем к InteractionHandler
     */
    public function reply(int $reviewId, int $userId, string $reply, bool $isOfficial = false): ReviewReply
    {
        return $this->interactionHandler->reply($reviewId, $userId, $reply, $isOfficial);
    }

    public function markAsHelpful(int $reviewId, int $userId): void
    {
        $this->interactionHandler->markAsHelpful($reviewId, $userId);
    }

    public function markAsNotHelpful(int $reviewId, int $userId): void
    {
        $this->interactionHandler->markAsNotHelpful($reviewId, $userId);
    }

    /**
     * Запросы - делегируем к QueryHandler
     */
    public function getForReviewable(string $type, int $id, array $filters = [], int $limit = 20): Collection
    {
        return $this->queryHandler->getForReviewable($type, $id, $filters, $limit);
    }

    public function getUserReviews(int $userId, int $limit = 20): Collection
    {
        return $this->queryHandler->getUserReviews($userId, $limit);
    }

    public function getReviewableStats(string $type, int $id): array
    {
        return $this->queryHandler->getReviewableStats($type, $id);
    }

    public function search(string $query, array $filters = [], int $limit = 20): Collection
    {
        return $this->queryHandler->search($query, $filters, $limit);
    }

    public function getPopular(int $days = 30, int $limit = 10): Collection
    {
        return $this->queryHandler->getPopular($days, $limit);
    }

    public function getStats(int $days = 30): array
    {
        return $this->queryHandler->getGeneralStats($days);
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

    /**
     * Отправить уведомления о создании отзыва
     */
    private function sendCreateNotifications(Review $review): void
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