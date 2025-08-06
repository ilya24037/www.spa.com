<?php

namespace App\Domain\Review\Services;

use App\Domain\Review\Models\Review;
use App\Domain\User\Models\User;
use App\Enums\ReviewStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для бизнес-логики отзывов (вынесен из модели)
 */
class ReviewBusinessLogicService
{
    private ReviewModerationService $moderationService;

    public function __construct(ReviewModerationService $moderationService)
    {
        $this->moderationService = $moderationService;
    }

    /**
     * Одобрить отзыв
     */
    public function approve(Review $review, ?User $moderator = null): void
    {
        $this->moderationService->approve($review, $moderator);
    }

    /**
     * Отклонить отзыв
     */
    public function reject(Review $review, ?User $moderator = null, ?string $reason = null): void
    {
        $this->moderationService->reject($review, $moderator, $reason);
    }

    /**
     * Пожаловаться на отзыв
     */
    public function flag(Review $review, User $flagger, string $reason): void
    {
        $this->moderationService->flag($review, $flagger, $reason);
    }

    /**
     * Отметить как полезный
     */
    public function markAsHelpful(Review $review): void
    {
        DB::transaction(function () use ($review) {
            $review->increment('helpful_count');
            
            Log::info('Review marked as helpful', [
                'review_id' => $review->id,
                'helpful_count' => $review->helpful_count + 1,
            ]);
        });
    }

    /**
     * Отметить как бесполезный
     */
    public function markAsNotHelpful(Review $review): void
    {
        DB::transaction(function () use ($review) {
            $review->increment('not_helpful_count');
            
            Log::info('Review marked as not helpful', [
                'review_id' => $review->id,
                'not_helpful_count' => $review->not_helpful_count + 1,
            ]);
        });
    }

    /**
     * Увеличить счетчик просмотров
     */
    public function incrementViews(Review $review): void
    {
        $metadata = $review->metadata ?? [];
        $metadata['views'] = ($metadata['views'] ?? 0) + 1;
        $metadata['last_viewed'] = now()->toISOString();
        
        $review->update(['metadata' => $metadata]);
    }

    /**
     * Добавить ответ мастера на отзыв
     */
    public function addMasterResponse(Review $review, string $response, User $master): void
    {
        DB::transaction(function () use ($review, $response, $master) {
            $review->update([
                'master_response' => $response,
                'responded_at' => now(),
            ]);

            // Увеличиваем счетчик ответов
            $review->increment('reply_count');

            Log::info('Master response added to review', [
                'review_id' => $review->id,
                'master_id' => $master->id,
            ]);
        });
    }

    /**
     * Проверить можно ли редактировать отзыв
     */
    public function canBeEdited(Review $review, User $user): bool
    {
        // Отзыв может редактировать только автор
        if ($review->user_id !== $user->id) {
            return false;
        }

        // Нельзя редактировать отклоненные или заблокированные отзывы
        if (in_array($review->status, [ReviewStatus::REJECTED, ReviewStatus::FLAGGED])) {
            return false;
        }

        // Можно редактировать в течение 24 часов после создания
        return $review->created_at->diffInHours(now()) <= 24;
    }

    /**
     * Проверить можно ли удалить отзыв
     */
    public function canBeDeleted(Review $review, User $user): bool
    {
        // Автор может удалить свой отзыв
        if ($review->user_id === $user->id) {
            return true;
        }

        // Модераторы могут удалить любой отзыв
        return $user->hasRole(['admin', 'moderator']);
    }

    /**
     * Рассчитать общую полезность отзыва
     */
    public function calculateHelpfulnessScore(Review $review): float
    {
        $total = $review->helpful_count + $review->not_helpful_count;
        
        if ($total === 0) {
            return 0.5; // Нейтральный рейтинг для отзывов без оценок
        }

        return $review->helpful_count / $total;
    }
}