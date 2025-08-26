<?php

namespace App\Infrastructure\Listeners\Review;

use App\Domain\Review\Events\ReviewCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель события создания отзыва
 * Отвечает за побочные эффекты и интеграцию с другими системами
 */
class HandleReviewCreated implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ReviewCreated $event): void
    {
        try {
            // Логируем событие
            Log::info('Review created', [
                'review_id' => $event->reviewId,
                'reviewer_id' => $event->reviewerId,
                'reviewed_id' => $event->reviewedId,
                'data' => $event->reviewData,
                'created_at' => $event->createdAt,
            ]);

            // Обновляем статистику пользователя
            $this->updateUserStatistics($event->reviewedId, $event->reviewData);

            // Отправляем уведомление получателю отзыва
            $this->notifyReviewedUser($event->reviewedId, $event->reviewerId, $event->reviewId);

            // Обновляем рейтинг, если это мастер
            $this->updateMasterRating($event->reviewedId);

            // Проверяем достижения
            // $this->checkAchievements($event->reviewedId);

            // Аналитика
            // $this->trackAnalytics($event);

        } catch (\Exception $e) {
            Log::error('Failed to handle ReviewCreated event', [
                'event' => $event->toArray(),
                'error' => $e->getMessage(),
            ]);
            
            // Не пробрасываем исключение, чтобы не блокировать основной процесс
            // throw $e;
        }
    }

    /**
     * Обновить статистику пользователя
     */
    private function updateUserStatistics(int $userId, array $reviewData): void
    {
        try {
            // Увеличиваем счетчик отзывов
            \DB::table('users')
                ->where('id', $userId)
                ->increment('reviews_count');

            // Обновляем средний рейтинг, если есть оценка
            if (isset($reviewData['rating'])) {
                $this->recalculateAverageRating($userId);
            }
                
        } catch (\Exception $e) {
            Log::warning('Failed to update user statistics', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
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
    }

    /**
     * Отправить уведомление пользователю о новом отзыве
     */
    private function notifyReviewedUser(int $reviewedId, int $reviewerId, int $reviewId): void
    {
        try {
            // Создаем уведомление в БД
            \DB::table('notifications')->insert([
                'type' => 'review_received',
                'notifiable_type' => 'App\\Domain\\User\\Models\\User',
                'notifiable_id' => $reviewedId,
                'data' => json_encode([
                    'review_id' => $reviewId,
                    'reviewer_id' => $reviewerId,
                    'message' => 'Вы получили новый отзыв',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Отправка push/email уведомления
            // NotificationService::send($reviewedId, new ReviewReceivedNotification($reviewId));
                
        } catch (\Exception $e) {
            Log::warning('Failed to notify user about review', [
                'user_id' => $reviewedId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обновить рейтинг мастера
     */
    private function updateMasterRating(int $userId): void
    {
        try {
            // Проверяем, является ли пользователь мастером
            $masterProfile = \DB::table('master_profiles')
                ->where('user_id', $userId)
                ->first();

            if (!$masterProfile) {
                return;
            }

            // Пересчитываем рейтинг мастера
            $stats = \DB::table('reviews')
                ->where('reviewed_id', $userId)
                ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as count')
                ->first();

            \DB::table('master_profiles')
                ->where('id', $masterProfile->id)
                ->update([
                    'rating' => round($stats->avg_rating ?? 0, 2),
                    'reviews_count' => $stats->count ?? 0,
                    'updated_at' => now(),
                ]);
                
        } catch (\Exception $e) {
            Log::warning('Failed to update master rating', [
                'user_id' => $userId,
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