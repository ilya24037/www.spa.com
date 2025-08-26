<?php

namespace App\Infrastructure\Listeners\Review;

use App\Domain\Review\Events\ReviewDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель события удаления отзыва
 */
class HandleReviewDeleted implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ReviewDeleted $event): void
    {
        try {
            // Логируем событие
            Log::info('Review deleted', [
                'review_id' => $event->reviewId,
                'reviewer_id' => $event->reviewerId,
                'reviewed_id' => $event->reviewedId,
                'reason' => $event->reason,
                'deleted_at' => $event->deletedAt,
            ]);

            // Обновляем статистику пользователя
            $this->updateUserStatistics($event->reviewedId);

            // Пересчитываем рейтинг
            $this->recalculateAverageRating($event->reviewedId);

            // Удаляем связанные уведомления
            $this->removeRelatedNotifications($event->reviewId);

        } catch (\Exception $e) {
            Log::error('Failed to handle ReviewDeleted event', [
                'event' => $event->toArray(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обновить статистику пользователя
     */
    private function updateUserStatistics(int $userId): void
    {
        try {
            // Уменьшаем счетчик отзывов
            \DB::table('users')
                ->where('id', $userId)
                ->where('reviews_count', '>', 0)
                ->decrement('reviews_count');
                
        } catch (\Exception $e) {
            Log::warning('Failed to update user statistics', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Пересчитать средний рейтинг после удаления отзыва
     */
    private function recalculateAverageRating(int $userId): void
    {
        $stats = \DB::table('reviews')
            ->where('reviewed_id', $userId)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as count')
            ->first();

        $avgRating = $stats->avg_rating ?? 0;

        \DB::table('users')
            ->where('id', $userId)
            ->update(['average_rating' => round($avgRating, 2)]);

        // Обновляем рейтинг мастера, если это мастер
        $masterProfile = \DB::table('master_profiles')
            ->where('user_id', $userId)
            ->first();

        if ($masterProfile) {
            \DB::table('master_profiles')
                ->where('id', $masterProfile->id)
                ->update([
                    'rating' => round($avgRating, 2),
                    'reviews_count' => $stats->count ?? 0,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Удалить связанные уведомления
     */
    private function removeRelatedNotifications(int $reviewId): void
    {
        try {
            \DB::table('notifications')
                ->where('type', 'review_received')
                ->where('data->review_id', $reviewId)
                ->delete();
                
        } catch (\Exception $e) {
            Log::warning('Failed to remove related notifications', [
                'review_id' => $reviewId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Определить задержку для повторной попытки
     */
    public function retryAfter(): int
    {
        return 60; // 1 минута
    }

    /**
     * Определить количество попыток
     */
    public function tries(): int
    {
        return 3;
    }
}