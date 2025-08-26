<?php

namespace App\Infrastructure\Listeners\Ad;

use App\Domain\Ad\Events\AdUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель события обновления объявления
 */
class HandleAdUpdated implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(AdUpdated $event): void
    {
        try {
            // Логируем событие
            Log::info('Ad updated', [
                'ad_id' => $event->adId,
                'user_id' => $event->userId,
                'changed_fields' => $event->changedFields,
                'updated_at' => $event->updatedAt,
            ]);

            // Если изменились ключевые поля, обновляем поисковый индекс
            if ($this->hasSearchableFieldsChanged($event->changedFields)) {
                $this->updateSearchIndex($event->adId);
            }

            // Если изменились фото, обновляем превью
            if (in_array('photos', $event->changedFields)) {
                $this->updateAdThumbnails($event->adId);
            }

            // Если изменился статус, выполняем соответствующие действия
            if (in_array('status', $event->changedFields)) {
                $this->handleStatusChange($event->adId, $event->userId);
            }

            // Обновляем время последней активности
            $this->updateLastActivity($event->adId);

        } catch (\Exception $e) {
            Log::error('Failed to handle AdUpdated event', [
                'event' => $event->toArray(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Проверить, изменились ли поля для поиска
     */
    private function hasSearchableFieldsChanged(array $changedFields): bool
    {
        $searchableFields = ['title', 'description', 'specialty', 'city', 'price'];
        
        return !empty(array_intersect($searchableFields, $changedFields));
    }

    /**
     * Обновить поисковый индекс
     */
    private function updateSearchIndex(int $adId): void
    {
        try {
            // Логика обновления поискового индекса
            // SearchService::reindexAd($adId);
            
            Log::info('Search index updated for ad', ['ad_id' => $adId]);
        } catch (\Exception $e) {
            Log::warning('Failed to update search index', [
                'ad_id' => $adId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обновить превью объявления
     */
    private function updateAdThumbnails(int $adId): void
    {
        try {
            // Логика генерации новых превью
            // MediaService::regenerateThumbnails($adId);
            
            Log::info('Thumbnails updated for ad', ['ad_id' => $adId]);
        } catch (\Exception $e) {
            Log::warning('Failed to update thumbnails', [
                'ad_id' => $adId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обработать изменение статуса
     */
    private function handleStatusChange(int $adId, int $userId): void
    {
        try {
            $ad = \DB::table('ads')->find($adId);
            
            if (!$ad) {
                return;
            }

            // Отправляем уведомления в зависимости от нового статуса
            switch ($ad->status) {
                case 'active':
                    // NotificationService::sendAdActivated($userId, $adId);
                    break;
                case 'archived':
                    // NotificationService::sendAdArchived($userId, $adId);
                    break;
                case 'rejected':
                    // NotificationService::sendAdRejected($userId, $adId);
                    break;
            }
            
        } catch (\Exception $e) {
            Log::warning('Failed to handle status change', [
                'ad_id' => $adId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обновить время последней активности
     */
    private function updateLastActivity(int $adId): void
    {
        try {
            \DB::table('ads')
                ->where('id', $adId)
                ->update(['last_activity_at' => now()]);
        } catch (\Exception $e) {
            Log::warning('Failed to update last activity', [
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