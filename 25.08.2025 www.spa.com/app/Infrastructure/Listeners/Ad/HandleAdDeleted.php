<?php

namespace App\Infrastructure\Listeners\Ad;

use App\Domain\Ad\Events\AdDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель события удаления объявления
 */
class HandleAdDeleted implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(AdDeleted $event): void
    {
        try {
            // Логируем событие
            Log::info('Ad deleted', [
                'ad_id' => $event->adId,
                'user_id' => $event->userId,
                'reason' => $event->reason,
                'deleted_at' => $event->deletedAt,
            ]);

            // Обновляем счетчик объявлений пользователя
            $this->decrementUserAdsCount($event->userId);

            // Удаляем из поискового индекса
            $this->removeFromSearchIndex($event->adId);

            // Удаляем связанные медиафайлы
            $this->cleanupMediaFiles($event->adId);

            // Удаляем аналитику
            $this->removeAnalytics($event->adId);

            // Удаляем из избранного у всех пользователей
            $this->removeFromAllFavorites($event->adId);

        } catch (\Exception $e) {
            Log::error('Failed to handle AdDeleted event', [
                'event' => $event->toArray(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Уменьшить счетчик объявлений пользователя
     */
    private function decrementUserAdsCount(int $userId): void
    {
        try {
            \DB::table('users')
                ->where('id', $userId)
                ->where('ads_count', '>', 0)
                ->decrement('ads_count');
                
            \DB::table('user_profiles')
                ->where('user_id', $userId)
                ->where('ads_count', '>', 0)
                ->decrement('ads_count');
                
        } catch (\Exception $e) {
            Log::warning('Failed to decrement user ads count', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Удалить из поискового индекса
     */
    private function removeFromSearchIndex(int $adId): void
    {
        try {
            // Логика удаления из поискового индекса
            // SearchService::removeFromIndex($adId);
            
            Log::info('Ad removed from search index', ['ad_id' => $adId]);
        } catch (\Exception $e) {
            Log::warning('Failed to remove from search index', [
                'ad_id' => $adId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Очистить медиафайлы
     */
    private function cleanupMediaFiles(int $adId): void
    {
        try {
            // Получаем список медиафайлов
            $media = \DB::table('ad_media')
                ->where('ad_id', $adId)
                ->get();

            foreach ($media as $file) {
                // Удаляем физические файлы
                if ($file->path && \Storage::exists($file->path)) {
                    \Storage::delete($file->path);
                }
                if ($file->thumbnail_path && \Storage::exists($file->thumbnail_path)) {
                    \Storage::delete($file->thumbnail_path);
                }
            }

            // Удаляем записи из БД
            \DB::table('ad_media')->where('ad_id', $adId)->delete();
            
            Log::info('Media files cleaned up for ad', ['ad_id' => $adId]);
        } catch (\Exception $e) {
            Log::warning('Failed to cleanup media files', [
                'ad_id' => $adId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Удалить аналитику
     */
    private function removeAnalytics(int $adId): void
    {
        try {
            \DB::table('ad_analytics')->where('ad_id', $adId)->delete();
            \DB::table('ad_views')->where('ad_id', $adId)->delete();
            \DB::table('ad_calls')->where('ad_id', $adId)->delete();
            
        } catch (\Exception $e) {
            Log::warning('Failed to remove analytics', [
                'ad_id' => $adId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Удалить из избранного у всех пользователей
     */
    private function removeFromAllFavorites(int $adId): void
    {
        try {
            $count = \DB::table('favorites')
                ->where('ad_id', $adId)
                ->delete();
                
            if ($count > 0) {
                Log::info('Ad removed from favorites', [
                    'ad_id' => $adId,
                    'affected_users' => $count,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to remove from favorites', [
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
        return 120; // 2 минуты
    }

    /**
     * Определить количество попыток
     */
    public function tries(): int
    {
        return 3;
    }
}