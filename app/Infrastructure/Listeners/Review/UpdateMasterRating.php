<?php

namespace App\Infrastructure\Listeners\Review;

use App\Domain\Review\Events\ReviewCreated;
use App\Domain\Review\Services\RatingCalculator;
use App\Domain\Master\Models\MasterProfile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель для обновления рейтинга мастера при создании отзыва
 * Отдельный слушатель для специфичной логики мастеров
 */
class UpdateMasterRating implements ShouldQueue
{
    use InteractsWithQueue;

    private RatingCalculator $ratingCalculator;

    public function __construct(RatingCalculator $ratingCalculator)
    {
        $this->ratingCalculator = $ratingCalculator;
    }

    /**
     * Handle the event.
     */
    public function handle(ReviewCreated $event): void
    {
        try {
            $review = \App\Domain\Review\Models\Review::find($event->reviewId);
            
            if (!$review) {
                Log::warning('Review not found for rating update', [
                    'review_id' => $event->reviewId,
                ]);
                return;
            }

            // Проверяем, касается ли отзыв мастера
            if ($review->reviewable_type !== MasterProfile::class) {
                return; // Отзыв не о мастере
            }

            // Обновляем рейтинг мастера через RatingCalculator
            $stats = $this->ratingCalculator->recalculateMasterRating($review->reviewable_id);

            Log::info('Master rating updated after review', [
                'master_profile_id' => $review->reviewable_id,
                'review_id' => $event->reviewId,
                'new_rating' => $stats['average_rating'],
                'total_reviews' => $stats['total_reviews'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update master rating', [
                'review_id' => $event->reviewId ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Повторяем попытку для критических ошибок
            throw $e;
        }
    }

    /**
     * Определить задержку для повторной попытки
     */
    public function retryAfter(): int
    {
        return 120; // 2 минуты
    }

    /**
     * Определить количество попыток
     */
    public function tries(): int
    {
        return 2;
    }
}