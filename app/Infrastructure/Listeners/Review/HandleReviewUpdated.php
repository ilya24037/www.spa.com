<?php

namespace App\Infrastructure\Listeners\Review;

use App\Domain\Review\Events\ReviewUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель события обновления отзыва
 */
class HandleReviewUpdated implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ReviewUpdated $event): void
    {
        try {
            // Логируем событие
            Log::info('Review updated', [
                'review_id' => $event->reviewId,
                'reviewer_id' => $event->reviewerId,
                'old_data' => $event->oldData,
                'new_data' => $event->newData,
                'updated_at' => $event->updatedAt,
            ]);

            // Если изменился рейтинг, пересчитываем средний
            if ($this->hasRatingChanged($event->oldData, $event->newData)) {
                $this->recalculateAverageRating($event->reviewerId);
            }

            // Отправляем уведомление об изменении отзыва
            // $this->notifyAboutUpdate($event);

        } catch (\Exception $e) {
            Log::error('Failed to handle ReviewUpdated event', [
                'event' => $event->toArray(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Проверить, изменился ли рейтинг
     */
    private function hasRatingChanged(array $oldData, array $newData): bool
    {
        $oldRating = $oldData['rating'] ?? null;
        $newRating = $newData['rating'] ?? null;
        
        return $oldRating !== $newRating;
    }

    /**
     * Пересчитать средний рейтинг
     */
    private function recalculateAverageRating(int $userId): void
    {
        $avgRating = \DB::table('reviews')
            ->where('reviewed_id', $userId)
            ->avg('rating');

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
                    'updated_at' => now(),
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