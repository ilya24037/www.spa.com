<?php

namespace App\Infrastructure\Listeners\Favorite;

use App\Domain\Favorite\Events\FavoriteRemoved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель события удаления из избранного
 * Отвечает за побочные эффекты и интеграцию с другими системами
 */
class HandleFavoriteRemoved implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(FavoriteRemoved $event): void
    {
        try {
            // Логируем событие
            Log::info('Favorite removed', [
                'user_id' => $event->userId,
                'ad_id' => $event->adId,
                'removed_at' => $event->removedAt,
            ]);

            // Обновляем счетчик избранного для объявления
            $this->updateAdFavoritesCount($event->adId);

            // Обновляем рекомендации для пользователя
            // $this->updateUserRecommendations($event->userId);

            // Аналитика
            // $this->trackAnalytics($event);

        } catch (\Exception $e) {
            Log::error('Failed to handle FavoriteRemoved event', [
                'event' => $event->toArray(),
                'error' => $e->getMessage(),
            ]);
            
            // Не пробрасываем исключение, чтобы не блокировать основной процесс
            // throw $e;
        }
    }

    /**
     * Обновить счетчик избранного для объявления
     */
    private function updateAdFavoritesCount(int $adId): void
    {
        try {
            // Используем прямой запрос для оптимизации
            \DB::table('ads')
                ->where('id', $adId)
                ->where('favorites_count', '>', 0) // Защита от отрицательных значений
                ->decrement('favorites_count');
                
        } catch (\Exception $e) {
            Log::warning('Failed to update ad favorites count', [
                'ad_id' => $adId,
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