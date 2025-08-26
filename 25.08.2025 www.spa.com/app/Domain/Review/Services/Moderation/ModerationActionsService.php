<?php

namespace App\Domain\Review\Services\Moderation;

use App\Domain\Review\Models\Review;
use App\Domain\User\Models\User;
use App\Domain\Review\Repositories\ReviewRepository;
use App\Enums\ReviewStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для выполнения действий модерации
 */
class ModerationActionsService
{
    private ReviewRepository $repository;
    private UserReputationService $reputationService;

    public function __construct(
        ReviewRepository $repository,
        UserReputationService $reputationService
    ) {
        $this->repository = $repository;
        $this->reputationService = $reputationService;
    }

    /**
     * Одобрить отзыв
     */
    public function approve(int $reviewId, User $moderator, ?string $notes = null): Review
    {
        try {
            DB::beginTransaction();

            $review = $this->repository->findOrFail($reviewId);
            
            $review->update([
                'status' => ReviewStatus::APPROVED,
                'moderated_at' => now(),
                'moderated_by' => $moderator->id,
                'moderation_notes' => $notes,
            ]);

            DB::commit();

            Log::info('Review approved', [
                'review_id' => $reviewId,
                'moderator_id' => $moderator->id,
                'notes' => $notes,
            ]);

            return $review;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Отклонить отзыв
     */
    public function reject(int $reviewId, User $moderator, string $reason): Review
    {
        try {
            DB::beginTransaction();

            $review = $this->repository->findOrFail($reviewId);
            
            $review->update([
                'status' => ReviewStatus::REJECTED,
                'moderated_at' => now(),
                'moderated_by' => $moderator->id,
                'moderation_notes' => $reason,
            ]);

            DB::commit();

            Log::info('Review rejected', [
                'review_id' => $reviewId,
                'moderator_id' => $moderator->id,
                'reason' => $reason,
            ]);

            return $review;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Пометить отзыв как спам
     */
    public function markAsSpam(int $reviewId, User $moderator, ?string $reason = null): Review
    {
        $review = $this->repository->findOrFail($reviewId);
        
        $review->update([
            'status' => ReviewStatus::SPAM,
            'moderated_at' => now(),
            'moderated_by' => $moderator->id,
            'moderation_notes' => $reason ?: 'Помечено как спам',
        ]);

        // Увеличиваем счетчик спама у пользователя
        $this->reputationService->incrementUserSpamCount($review->user);

        Log::info('Review marked as spam', [
            'review_id' => $reviewId,
            'moderator_id' => $moderator->id,
            'reason' => $reason,
        ]);

        return $review;
    }

    /**
     * Массовое одобрение отзывов
     */
    public function batchApprove(array $reviewIds, User $moderator): array
    {
        $results = ['approved' => 0, 'failed' => 0, 'errors' => []];

        foreach ($reviewIds as $reviewId) {
            try {
                $this->approve($reviewId, $moderator, 'Массовое одобрение');
                $results['approved']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][$reviewId] = $e->getMessage();
                Log::error('Failed to approve review in batch', [
                    'review_id' => $reviewId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Batch approval completed', [
            'moderator_id' => $moderator->id,
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Массовое отклонение отзывов
     */
    public function batchReject(array $reviewIds, User $moderator, string $reason): array
    {
        $results = ['rejected' => 0, 'failed' => 0, 'errors' => []];

        foreach ($reviewIds as $reviewId) {
            try {
                $this->reject($reviewId, $moderator, $reason);
                $results['rejected']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][$reviewId] = $e->getMessage();
                Log::error('Failed to reject review in batch', [
                    'review_id' => $reviewId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Batch rejection completed', [
            'moderator_id' => $moderator->id,
            'reason' => $reason,
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Массовое помечение как спам
     */
    public function batchMarkAsSpam(array $reviewIds, User $moderator, string $reason = 'Массовое помечение как спам'): array
    {
        $results = ['marked' => 0, 'failed' => 0, 'errors' => []];

        foreach ($reviewIds as $reviewId) {
            try {
                $this->markAsSpam($reviewId, $moderator, $reason);
                $results['marked']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][$reviewId] = $e->getMessage();
                Log::error('Failed to mark review as spam in batch', [
                    'review_id' => $reviewId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Batch spam marking completed', [
            'moderator_id' => $moderator->id,
            'reason' => $reason,
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Вернуть отзыв на модерацию
     */
    public function returnToModeration(int $reviewId, User $moderator, string $reason): Review
    {
        $review = $this->repository->findOrFail($reviewId);
        
        $review->update([
            'status' => ReviewStatus::PENDING,
            'moderated_at' => null,
            'moderated_by' => null,
            'moderation_notes' => 'Возвращено на модерацию: ' . $reason,
        ]);

        Log::info('Review returned to moderation', [
            'review_id' => $reviewId,
            'moderator_id' => $moderator->id,
            'reason' => $reason,
        ]);

        return $review;
    }

    /**
     * Получить историю модерации отзыва
     */
    public function getModerationHistory(int $reviewId): array
    {
        // Здесь должна быть логика получения истории модерации
        // Если есть таблица moderation_logs или аудит
        return [
            'review_id' => $reviewId,
            'actions' => [], // История действий
            'moderators' => [], // Модераторы участвовавшие в модерации
        ];
    }

    /**
     * Получить статистику действий модератора
     */
    public function getModeratorStats(User $moderator, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $approved = Review::where('moderated_by', $moderator->id)
            ->where('status', ReviewStatus::APPROVED)
            ->where('moderated_at', '>=', $startDate)
            ->count();

        $rejected = Review::where('moderated_by', $moderator->id)
            ->where('status', ReviewStatus::REJECTED)
            ->where('moderated_at', '>=', $startDate)
            ->count();

        $spam = Review::where('moderated_by', $moderator->id)
            ->where('status', ReviewStatus::SPAM)
            ->where('moderated_at', '>=', $startDate)
            ->count();

        $total = $approved + $rejected + $spam;

        return [
            'moderator_id' => $moderator->id,
            'period_days' => $days,
            'total_moderated' => $total,
            'approved_count' => $approved,
            'rejected_count' => $rejected,
            'spam_count' => $spam,
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 2) : 0,
            'rejection_rate' => $total > 0 ? round(($rejected / $total) * 100, 2) : 0,
            'spam_rate' => $total > 0 ? round(($spam / $total) * 100, 2) : 0,
        ];
    }
}