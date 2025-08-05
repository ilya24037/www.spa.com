<?php

namespace App\Infrastructure\Listeners\Ad;

use App\Domain\Ad\Events\AdCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель события создания объявления
 * Отвечает за побочные эффекты и интеграцию с другими системами
 */
class HandleAdCreated implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(AdCreated $event): void
    {
        try {
            // Логируем событие
            Log::info('Ad created', [
                'ad_id' => $event->adId,
                'user_id' => $event->userId,
                'data' => $event->adData,
                'created_at' => $event->createdAt,
            ]);

            // Обновляем счетчик объявлений пользователя
            $this->updateUserAdsCount($event->userId);

            // Создаем начальную аналитику для объявления
            $this->initializeAdAnalytics($event->adId);

            // Отправляем приветственное уведомление
            // $this->sendWelcomeNotification($event->userId, $event->adId);

            // Индексация для поиска
            // $this->indexForSearch($event->adId, $event->adData);

            // Проверка модерации (если включена)
            // $this->submitForModeration($event->adId);

        } catch (\Exception $e) {
            Log::error('Failed to handle AdCreated event', [
                'event' => $event->toArray(),
                'error' => $e->getMessage(),
            ]);
            
            // Не пробрасываем исключение, чтобы не блокировать основной процесс
            // throw $e;
        }
    }

    /**
     * Обновить счетчик объявлений пользователя
     */
    private function updateUserAdsCount(int $userId): void
    {
        try {
            \DB::table('users')
                ->where('id', $userId)
                ->increment('ads_count');
                
        } catch (\Exception $e) {
            Log::warning('Failed to update user ads count', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Инициализировать аналитику для объявления
     */
    private function initializeAdAnalytics(int $adId): void
    {
        try {
            \DB::table('ad_analytics')->insert([
                'ad_id' => $adId,
                'views_count' => 0,
                'calls_count' => 0,
                'messages_count' => 0,
                'favorites_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
                
        } catch (\Exception $e) {
            Log::warning('Failed to initialize ad analytics', [
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