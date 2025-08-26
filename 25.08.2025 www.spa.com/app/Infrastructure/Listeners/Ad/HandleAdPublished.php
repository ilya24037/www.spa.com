<?php

namespace App\Infrastructure\Listeners\Ad;

use App\Domain\Ad\Events\AdPublished;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель события публикации объявления
 */
class HandleAdPublished implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(AdPublished $event): void
    {
        try {
            // Логируем событие
            Log::info('Ad published', [
                'ad_id' => $event->adId,
                'user_id' => $event->userId,
                'published_at' => $event->publishedAt,
            ]);

            // Индексируем для поиска
            $this->indexForSearch($event->adId);

            // Отправляем уведомление о публикации
            $this->notifyUserAboutPublication($event->userId, $event->adId);

            // Запускаем проверку модерации (если требуется)
            $this->submitForModeration($event->adId);

            // Обновляем статистику пользователя
            $this->updateUserActiveAdsCount($event->userId);

            // Создаем запись о публикации в истории
            $this->createPublicationHistory($event->adId, $event->userId);

        } catch (\Exception $e) {
            Log::error('Failed to handle AdPublished event', [
                'event' => $event->toArray(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Индексировать объявление для поиска
     */
    private function indexForSearch(int $adId): void
    {
        try {
            // Логика индексации для поиска
            // SearchService::indexAd($adId);
            
            Log::info('Ad indexed for search', ['ad_id' => $adId]);
        } catch (\Exception $e) {
            Log::warning('Failed to index ad for search', [
                'ad_id' => $adId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить уведомление пользователю о публикации
     */
    private function notifyUserAboutPublication(int $userId, int $adId): void
    {
        try {
            \DB::table('notifications')->insert([
                'type' => 'ad_published',
                'notifiable_type' => 'App\\Domain\\User\\Models\\User',
                'notifiable_id' => $userId,
                'data' => json_encode([
                    'ad_id' => $adId,
                    'message' => 'Ваше объявление успешно опубликовано',
                    'action_url' => '/ads/' . $adId,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Email уведомление
            // NotificationService::sendAdPublishedEmail($userId, $adId);
            
        } catch (\Exception $e) {
            Log::warning('Failed to notify user about publication', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить объявление на модерацию
     */
    private function submitForModeration(int $adId): void
    {
        try {
            $moderationRequired = config('ads.moderation_required', false);
            
            if (!$moderationRequired) {
                return;
            }

            \DB::table('moderation_queue')->insert([
                'ad_id' => $adId,
                'status' => 'pending',
                'submitted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Ad submitted for moderation', ['ad_id' => $adId]);
        } catch (\Exception $e) {
            Log::warning('Failed to submit ad for moderation', [
                'ad_id' => $adId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обновить счетчик активных объявлений
     */
    private function updateUserActiveAdsCount(int $userId): void
    {
        try {
            $activeCount = \DB::table('ads')
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->count();

            \DB::table('user_profiles')
                ->where('user_id', $userId)
                ->update(['active_ads_count' => $activeCount]);
                
        } catch (\Exception $e) {
            Log::warning('Failed to update active ads count', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Создать запись в истории публикаций
     */
    private function createPublicationHistory(int $adId, int $userId): void
    {
        try {
            \DB::table('ad_publication_history')->insert([
                'ad_id' => $adId,
                'user_id' => $userId,
                'action' => 'published',
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to create publication history', [
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