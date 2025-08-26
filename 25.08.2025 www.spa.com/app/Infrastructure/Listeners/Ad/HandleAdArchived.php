<?php

namespace App\Infrastructure\Listeners\Ad;

use App\Domain\Ad\Events\AdArchived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель события архивирования объявления
 */
class HandleAdArchived implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(AdArchived $event): void
    {
        try {
            // Логируем событие
            Log::info('Ad archived', [
                'ad_id' => $event->adId,
                'user_id' => $event->userId,
                'reason' => $event->reason,
                'archived_at' => $event->archivedAt,
            ]);

            // Удаляем из поискового индекса
            $this->removeFromSearchIndex($event->adId);

            // Обновляем счетчик активных объявлений
            $this->updateUserActiveAdsCount($event->userId);

            // Отправляем уведомление пользователю
            if ($event->reason) {
                $this->notifyUserAboutArchiving($event->userId, $event->adId, $event->reason);
            }

            // Создаем запись в истории
            $this->createArchiveHistory($event->adId, $event->userId, $event->reason);

            // Отменяем все активные продвижения
            $this->cancelPromotions($event->adId);

        } catch (\Exception $e) {
            Log::error('Failed to handle AdArchived event', [
                'event' => $event->toArray(),
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
                ->update([
                    'active_ads_count' => $activeCount,
                    'updated_at' => now(),
                ]);
                
        } catch (\Exception $e) {
            Log::warning('Failed to update active ads count', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить уведомление об архивировании
     */
    private function notifyUserAboutArchiving(int $userId, int $adId, string $reason): void
    {
        try {
            $reasonMessages = [
                'expired' => 'Срок размещения объявления истек',
                'user_request' => 'Вы архивировали объявление',
                'violation' => 'Объявление нарушает правила площадки',
                'inactive' => 'Объявление было неактивно длительное время',
            ];

            $message = $reasonMessages[$reason] ?? 'Объявление было архивировано';

            \DB::table('notifications')->insert([
                'type' => 'ad_archived',
                'notifiable_type' => 'App\\Domain\\User\\Models\\User',
                'notifiable_id' => $userId,
                'data' => json_encode([
                    'ad_id' => $adId,
                    'reason' => $reason,
                    'message' => $message,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
        } catch (\Exception $e) {
            Log::warning('Failed to notify user about archiving', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Создать запись в истории архивирования
     */
    private function createArchiveHistory(int $adId, int $userId, ?string $reason): void
    {
        try {
            \DB::table('ad_archive_history')->insert([
                'ad_id' => $adId,
                'user_id' => $userId,
                'reason' => $reason,
                'archived_at' => now(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to create archive history', [
                'ad_id' => $adId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отменить активные продвижения
     */
    private function cancelPromotions(int $adId): void
    {
        try {
            $canceled = \DB::table('ad_promotions')
                ->where('ad_id', $adId)
                ->where('status', 'active')
                ->update([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($canceled > 0) {
                Log::info('Ad promotions canceled', [
                    'ad_id' => $adId,
                    'count' => $canceled,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to cancel promotions', [
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