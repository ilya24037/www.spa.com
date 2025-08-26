<?php

namespace App\Domain\Review\Services;

use App\Domain\Review\Models\Review;
use App\Domain\Review\Models\ReviewReply;
use App\Domain\Review\Repositories\ReviewRepository;
use App\Infrastructure\Notification\NotificationService;
use App\Enums\NotificationType;
use App\Enums\ReviewStatus;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик взаимодействий с отзывами
 */
class ReviewInteractionHandler
{
    public function __construct(
        private ReviewRepository $repository,
        private NotificationService $notificationService
    ) {}

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
        $this->sendReplyNotification($review, $reviewReply, $userId);

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

        $this->updateHelpfulnessCounters($review, $reaction, true);

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

        $this->updateHelpfulnessCounters($review, $reaction, false);

        Log::info('Review marked as not helpful', [
            'review_id' => $reviewId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Поделиться отзывом
     */
    public function share(int $reviewId, int $userId, string $platform): void
    {
        $review = $this->repository->findOrFail($reviewId);

        // Увеличиваем счетчик поделившихся
        $review->increment('share_count');

        // Логируем событие для аналитики
        $this->repository->logShareEvent($reviewId, $userId, $platform);

        Log::info('Review shared', [
            'review_id' => $reviewId,
            'user_id' => $userId,
            'platform' => $platform,
        ]);
    }

    /**
     * Добавить отзыв в избранное
     */
    public function addToFavorites(int $reviewId, int $userId): void
    {
        $review = $this->repository->findOrFail($reviewId);

        if ($review->user_id === $userId) {
            throw new \InvalidArgumentException('You cannot favorite your own review');
        }

        $favorite = $this->repository->createFavorite($reviewId, $userId);

        if ($favorite->wasRecentlyCreated) {
            $review->increment('favorite_count');

            Log::info('Review added to favorites', [
                'review_id' => $reviewId,
                'user_id' => $userId,
            ]);
        }
    }

    /**
     * Удалить отзыв из избранного
     */
    public function removeFromFavorites(int $reviewId, int $userId): void
    {
        $review = $this->repository->findOrFail($reviewId);

        $deleted = $this->repository->deleteFavorite($reviewId, $userId);

        if ($deleted) {
            $review->decrement('favorite_count');

            Log::info('Review removed from favorites', [
                'review_id' => $reviewId,
                'user_id' => $userId,
            ]);
        }
    }

    /**
     * Проголосовать за отзыв (лайк/дизлайк)
     */
    public function vote(int $reviewId, int $userId, bool $isUpvote): void
    {
        $review = $this->repository->findOrFail($reviewId);
        
        if ($review->user_id === $userId) {
            throw new \InvalidArgumentException('You cannot vote for your own review');
        }

        $vote = $this->repository->createVote($reviewId, $userId, $isUpvote);

        $this->updateVoteCounters($review, $vote, $isUpvote);

        Log::info('Review vote recorded', [
            'review_id' => $reviewId,
            'user_id' => $userId,
            'is_upvote' => $isUpvote,
        ]);
    }

    /**
     * Удалить голос
     */
    public function removeVote(int $reviewId, int $userId): void
    {
        $review = $this->repository->findOrFail($reviewId);
        $vote = $this->repository->getUserVote($reviewId, $userId);

        if ($vote) {
            $this->repository->deleteVote($reviewId, $userId);
            
            // Обновляем счетчики
            if ($vote->is_upvote) {
                $review->decrement('upvote_count');
            } else {
                $review->decrement('downvote_count');
            }

            Log::info('Review vote removed', [
                'review_id' => $reviewId,
                'user_id' => $userId,
            ]);
        }
    }

    /**
     * Получить статистику взаимодействий с отзывом
     */
    public function getInteractionStats(int $reviewId): array
    {
        $review = $this->repository->findOrFail($reviewId);

        return [
            'helpful_count' => $review->helpful_count ?? 0,
            'not_helpful_count' => $review->not_helpful_count ?? 0,
            'share_count' => $review->share_count ?? 0,
            'favorite_count' => $review->favorite_count ?? 0,
            'upvote_count' => $review->upvote_count ?? 0,
            'downvote_count' => $review->downvote_count ?? 0,
            'reply_count' => $review->replies()->count(),
            'helpfulness_ratio' => $this->calculateHelpfulnessRatio($review),
        ];
    }

    /**
     * Получить популярные отзывы по взаимодействиям
     */
    public function getMostEngaging(int $limit = 10, int $days = 30): array
    {
        return $this->repository->getMostEngaging($limit, $days);
    }

    /**
     * Отправить уведомление об ответе
     */
    private function sendReplyNotification(Review $review, ReviewReply $reply, int $userId): void
    {
        if ($review->user_id !== $userId) {
            try {
                $this->notificationService->create(
                    \App\DTOs\Notification\CreateNotificationDTO::forUser(
                        $review->user_id,
                        NotificationType::REVIEW_RESPONSE,
                        'Ответ на отзыв',
                        'На ваш отзыв дан ответ',
                        [
                            'review_id' => $review->id,
                            'reply_id' => $reply->id,
                            'action_url' => route('reviews.show', $review->id),
                        ]
                    )
                );
            } catch (\Exception $e) {
                Log::error('Failed to send reply notification', [
                    'review_id' => $review->id,
                    'reply_id' => $reply->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Обновить счетчики полезности
     */
    private function updateHelpfulnessCounters($review, $reaction, bool $isHelpful): void
    {
        if ($reaction->wasRecentlyCreated) {
            if ($isHelpful) {
                $review->increment('helpful_count');
            } else {
                $review->increment('not_helpful_count');
            }
        } elseif ($reaction->is_helpful !== $isHelpful) {
            if ($isHelpful) {
                $review->increment('helpful_count');
                $review->decrement('not_helpful_count');
            } else {
                $review->increment('not_helpful_count');
                $review->decrement('helpful_count');
            }
        }
    }

    /**
     * Обновить счетчики голосов
     */
    private function updateVoteCounters($review, $vote, bool $isUpvote): void
    {
        if ($vote->wasRecentlyCreated) {
            if ($isUpvote) {
                $review->increment('upvote_count');
            } else {
                $review->increment('downvote_count');
            }
        } elseif ($vote->is_upvote !== $isUpvote) {
            if ($isUpvote) {
                $review->increment('upvote_count');
                $review->decrement('downvote_count');
            } else {
                $review->increment('downvote_count');
                $review->decrement('upvote_count');
            }
        }
    }

    /**
     * Рассчитать коэффициент полезности
     */
    private function calculateHelpfulnessRatio(Review $review): float
    {
        $total = ($review->helpful_count ?? 0) + ($review->not_helpful_count ?? 0);
        
        if ($total === 0) {
            return 0;
        }

        return round(($review->helpful_count ?? 0) / $total * 100, 1);
    }
}