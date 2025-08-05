<?php

namespace App\Infrastructure\Listeners\Review;

use App\Domain\Review\Events\ReviewCreated;
use App\Domain\Master\Services\MasterRatingService;
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

    private MasterRatingService $ratingService;

    public function __construct(MasterRatingService $ratingService)
    {
        $this->ratingService = $ratingService;
    }

    /**
     * Handle the event.
     */
    public function handle(ReviewCreated $event): void
    {
        try {
            // Проверяем, является ли пользователь мастером
            $user = \App\Domain\User\Models\User::find($event->reviewedId);
            
            if (!$user || !$user->isMaster()) {
                return;
            }

            // Обновляем рейтинг через специализированный сервис
            $this->ratingService->recalculateRating($event->reviewedId);

            Log::info('Master rating updated after review', [
                'user_id' => $event->reviewedId,
                'review_id' => $event->reviewId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update master rating', [
                'event' => $event->toArray(),
                'error' => $e->getMessage(),
            ]);
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