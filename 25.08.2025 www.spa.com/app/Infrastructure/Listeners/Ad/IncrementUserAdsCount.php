<?php

namespace App\Infrastructure\Listeners\Ad;

use App\Domain\Ad\Events\AdCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель для инкремента счетчика объявлений пользователя
 * Отдельный слушатель для единственной ответственности
 */
class IncrementUserAdsCount implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(AdCreated $event): void
    {
        try {
            // Обновляем счетчик в профиле пользователя
            $updated = \DB::table('user_profiles')
                ->where('user_id', $event->userId)
                ->increment('ads_count');

            if (!$updated) {
                // Если профиль не найден, создаем его
                \DB::table('user_profiles')->insert([
                    'user_id' => $event->userId,
                    'ads_count' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Log::info('User ads count incremented', [
                'user_id' => $event->userId,
                'ad_id' => $event->adId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to increment user ads count', [
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
        return 30; // 30 секунд
    }

    /**
     * Определить количество попыток
     */
    public function tries(): int
    {
        return 2;
    }
}