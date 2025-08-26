<?php

namespace App\Application\Services\Integration\UserAds;

use App\Domain\Ad\Events\AdCreated;
use App\Domain\Ad\Events\AdUpdated;
use App\Domain\Ad\Events\AdDeleted;
use App\Domain\Ad\Events\AdPublished;
use App\Domain\Ad\Events\AdArchived;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик CRUD операций с объявлениями пользователя
 */
class AdOperationsHandler
{
    /**
     * Создать новое объявление через событие
     * ВАЖНО: Не создает напрямую, а отправляет событие!
     */
    public function createUserAd(int $userId, array $adData): ?int
    {
        try {
            // Создаем объявление в БД
            $adId = DB::table('ads')->insertGetId([
                'user_id' => $userId,
                'title' => $adData['title'],
                'description' => $adData['description'] ?? '',
                'category' => $adData['category'] ?? 'massage',
                'specialty' => $adData['specialty'] ?? '',
                'price' => $adData['price'] ?? null,
                'status' => $adData['status'] ?? 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Отправляем событие
            Event::dispatch(new AdCreated($adId, $userId, $adData));

            Log::info('Ad created via integration service', [
                'ad_id' => $adId,
                'user_id' => $userId,
                'title' => $adData['title']
            ]);

            return $adId;

        } catch (\Exception $e) {
            Log::error('Failed to create ad', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Обновить объявление через событие
     * ВАЖНО: Не обновляет напрямую, а отправляет событие!
     */
    public function updateUserAd(int $userId, int $adId, array $adData): bool
    {
        try {
            // Получаем текущие данные объявления
            $ad = DB::table('ads')->where('id', $adId)->first();
            if (!$ad || !in_array($ad->status, ['draft', 'pending'])) {
                return false;
            }

            // Обновляем объявление
            $updated = DB::table('ads')
                ->where('id', $adId)
                ->update([
                    'title' => $adData['title'] ?? $ad->title,
                    'description' => $adData['description'] ?? $ad->description,
                    'category' => $adData['category'] ?? $ad->category,
                    'specialty' => $adData['specialty'] ?? $ad->specialty,
                    'price' => $adData['price'] ?? $ad->price,
                    'updated_at' => now(),
                ]);

            if ($updated) {
                // Отправляем событие
                Event::dispatch(new AdUpdated($adId, $userId, $adData));

                Log::info('Ad updated via integration service', [
                    'ad_id' => $adId,
                    'user_id' => $userId
                ]);
            }

            return $updated > 0;

        } catch (\Exception $e) {
            Log::error('Failed to update ad', [
                'user_id' => $userId,
                'ad_id' => $adId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Удалить объявление (мягкое удаление)
     */
    public function deleteUserAd(int $userId, int $adId): bool
    {
        try {
            // Мягкое удаление
            $deleted = DB::table('ads')
                ->where('id', $adId)
                ->update([
                    'status' => 'deleted',
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($deleted) {
                // Отправляем событие
                Event::dispatch(new AdDeleted($adId, $userId));

                Log::info('Ad deleted via integration service', [
                    'ad_id' => $adId,
                    'user_id' => $userId
                ]);
            }

            return $deleted > 0;

        } catch (\Exception $e) {
            Log::error('Failed to delete ad', [
                'user_id' => $userId,
                'ad_id' => $adId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Архивировать объявление
     */
    public function archiveUserAd(int $userId, int $adId): bool
    {
        try {
            $updated = DB::table('ads')
                ->where('id', $adId)
                ->update([
                    'status' => 'archived',
                    'updated_at' => now(),
                ]);

            if ($updated) {
                Event::dispatch(new AdArchived($adId, $userId));
                
                Log::info('Ad archived via integration service', [
                    'ad_id' => $adId,
                    'user_id' => $userId
                ]);
            }

            return $updated > 0;

        } catch (\Exception $e) {
            Log::error('Failed to archive ad', [
                'user_id' => $userId,
                'ad_id' => $adId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Восстановить объявление из архива
     */
    public function restoreUserAd(int $userId, int $adId): bool
    {
        try {
            $updated = DB::table('ads')
                ->where('id', $adId)
                ->where('status', 'archived')
                ->update([
                    'status' => 'draft',
                    'updated_at' => now(),
                ]);

            if ($updated) {
                Log::info('Ad restored from archive via integration service', [
                    'ad_id' => $adId,
                    'user_id' => $userId
                ]);
            }

            return $updated > 0;

        } catch (\Exception $e) {
            Log::error('Failed to restore ad', [
                'user_id' => $userId,
                'ad_id' => $adId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Опубликовать объявление
     */
    public function publishUserAd(int $userId, int $adId): bool
    {
        try {
            // Проверяем готовность к публикации
            $ad = DB::table('ads')->where('id', $adId)->first();
            if (!$ad || $ad->status !== 'draft') {
                return false;
            }

            // Проверяем обязательные поля
            if (empty($ad->title) || empty($ad->description)) {
                return false;
            }

            $updated = DB::table('ads')
                ->where('id', $adId)
                ->update([
                    'status' => 'waiting_payment',
                    'published_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated) {
                Event::dispatch(new AdPublished($adId, $userId));

                Log::info('Ad published via integration service', [
                    'ad_id' => $adId,
                    'user_id' => $userId
                ]);
            }

            return $updated > 0;

        } catch (\Exception $e) {
            Log::error('Failed to publish ad', [
                'user_id' => $userId,
                'ad_id' => $adId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}