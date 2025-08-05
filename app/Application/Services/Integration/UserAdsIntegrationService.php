<?php

namespace App\Application\Services\Integration;

use App\Domain\Ad\Events\AdCreated;
use App\Domain\Ad\Events\AdUpdated;
use App\Domain\Ad\Events\AdDeleted;
use App\Domain\Ad\Events\AdPublished;
use App\Domain\Ad\Events\AdArchived;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис интеграции User ↔ Ads доменов
 * Заменяет прямые связи через трейт HasAds
 */
class UserAdsIntegrationService
{
    /**
     * Получить все объявления пользователя
     * Заменяет: $user->ads()
     */
    public function getUserAds(int $userId): Collection
    {
        return DB::table('ads')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Получить активные объявления пользователя
     * Заменяет: $user->ads()->where('status', 'active')
     */
    public function getUserActiveAds(int $userId): Collection
    {
        return DB::table('ads')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Получить черновики пользователя
     * Заменяет: $user->ads()->where('status', 'draft')
     */
    public function getUserDraftAds(int $userId): Collection
    {
        return DB::table('ads')
            ->where('user_id', $userId)
            ->where('status', 'draft')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Получить архивированные объявления
     * Заменяет: $user->ads()->where('status', 'archived')
     */
    public function getUserArchivedAds(int $userId): Collection
    {
        return DB::table('ads')
            ->where('user_id', $userId)
            ->where('status', 'archived')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Получить количество всех объявлений
     * Заменяет: $user->ads()->count()
     */
    public function getUserAdsCount(int $userId): int
    {
        return DB::table('ads')
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * Получить количество активных объявлений
     * Заменяет: $user->ads()->where('status', 'active')->count()
     */
    public function getUserActiveAdsCount(int $userId): int
    {
        return DB::table('ads')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Проверить может ли пользователь создать новое объявление
     */
    public function canUserCreateAd(int $userId): bool
    {
        // Проверяем лимит активных объявлений (например, максимум 10)
        $activeAdsCount = $this->getUserActiveAdsCount($userId);
        if ($activeAdsCount >= 10) {
            return false;
        }

        // Проверяем лимит создания в день (например, максимум 3)
        $todayAdsCount = DB::table('ads')
            ->where('user_id', $userId)
            ->whereDate('created_at', today())
            ->count();

        if ($todayAdsCount >= 3) {
            return false;
        }

        // Проверяем статус пользователя
        $userStatus = DB::table('users')
            ->where('id', $userId)
            ->value('status');

        return in_array($userStatus, ['active', 'verified']);
    }

    /**
     * Проверить принадлежит ли объявление пользователю
     * Заменяет: $user->ads()->where('id', $adId)->exists()
     */
    public function userOwnsAd(int $userId, int $adId): bool
    {
        return DB::table('ads')
            ->where('id', $adId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Создать новое объявление через событие
     * ВАЖНО: Не создает напрямую, а отправляет событие!
     */
    public function createUserAd(int $userId, array $adData): ?int
    {
        try {
            // Проверяем права и лимиты
            if (!$this->canUserCreateAd($userId)) {
                return null;
            }

            // Валидируем данные объявления
            $validatedData = $this->validateAdData($adData);
            if (!$validatedData) {
                return null;
            }

            // Создаем объявление в БД
            $adId = DB::table('ads')->insertGetId([
                'user_id' => $userId,
                'title' => $validatedData['title'],
                'description' => $validatedData['description'] ?? '',
                'category' => $validatedData['category'] ?? 'massage',
                'specialty' => $validatedData['specialty'] ?? '',
                'price' => $validatedData['price'] ?? null,
                'status' => $validatedData['status'] ?? 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Отправляем событие
            Event::dispatch(new AdCreated($adId, $userId, $validatedData));

            Log::info('Ad created via integration service', [
                'ad_id' => $adId,
                'user_id' => $userId,
                'title' => $validatedData['title']
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
            // Проверяем права
            if (!$this->userOwnsAd($userId, $adId)) {
                return false;
            }

            // Проверяем что объявление можно редактировать
            $ad = DB::table('ads')->where('id', $adId)->first();
            if (!$ad || !in_array($ad->status, ['draft', 'pending'])) {
                return false;
            }

            // Валидируем данные
            $validatedData = $this->validateAdData($adData);
            if (!$validatedData) {
                return false;
            }

            // Обновляем объявление
            $updated = DB::table('ads')
                ->where('id', $adId)
                ->update([
                    'title' => $validatedData['title'] ?? $ad->title,
                    'description' => $validatedData['description'] ?? $ad->description,
                    'category' => $validatedData['category'] ?? $ad->category,
                    'specialty' => $validatedData['specialty'] ?? $ad->specialty,
                    'price' => $validatedData['price'] ?? $ad->price,
                    'updated_at' => now(),
                ]);

            if ($updated) {
                // Отправляем событие
                Event::dispatch(new AdUpdated($adId, $userId, $validatedData));

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
            // Проверяем права
            if (!$this->userOwnsAd($userId, $adId)) {
                return false;
            }

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
            if (!$this->userOwnsAd($userId, $adId)) {
                return false;
            }

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
            if (!$this->userOwnsAd($userId, $adId)) {
                return false;
            }

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
            if (!$this->userOwnsAd($userId, $adId)) {
                return false;
            }

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

    /**
     * Получить недавние объявления пользователя
     */
    public function getRecentUserAds(int $userId, int $limit = 10): Collection
    {
        return DB::table('ads')
            ->where('user_id', $userId)
            ->whereIn('status', ['active', 'draft', 'pending'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить статистику объявлений пользователя
     */
    public function getUserAdsStatistics(int $userId): array
    {
        $stats = DB::table('ads')
            ->where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total_ads,
                COUNT(CASE WHEN status = "active" THEN 1 END) as active_ads,
                COUNT(CASE WHEN status = "draft" THEN 1 END) as draft_ads,
                COUNT(CASE WHEN status = "archived" THEN 1 END) as archived_ads,
                COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_ads,
                MIN(created_at) as first_ad,
                MAX(created_at) as latest_ad
            ')
            ->first();

        return [
            'total_ads' => $stats->total_ads ?? 0,
            'active_ads' => $stats->active_ads ?? 0,
            'draft_ads' => $stats->draft_ads ?? 0,
            'archived_ads' => $stats->archived_ads ?? 0,
            'pending_ads' => $stats->pending_ads ?? 0,
            'first_ad' => $stats->first_ad,
            'latest_ad' => $stats->latest_ad,
        ];
    }

    /**
     * Получить популярные категории объявлений пользователя
     */
    public function getUserAdsCategories(int $userId): array
    {
        return DB::table('ads')
            ->where('user_id', $userId)
            ->whereIn('status', ['active', 'pending'])
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->pluck('count', 'category')
            ->toArray();
    }

    /**
     * Получить объявления с истекающим сроком
     */
    public function getUserExpiringAds(int $userId, int $daysBeforeExpiry = 7): Collection
    {
        $expiryDate = now()->addDays($daysBeforeExpiry);

        return DB::table('ads')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $expiryDate)
            ->where('expires_at', '>', now())
            ->orderBy('expires_at', 'asc')
            ->get();
    }

    /**
     * Получить неоплаченные объявления
     */
    public function getUserUnpaidAds(int $userId): Collection
    {
        return DB::table('ads')
            ->where('user_id', $userId)
            ->where('status', 'waiting_payment')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Получить доходы от объявлений
     */
    public function getUserAdsRevenue(int $userId): array
    {
        $revenue = DB::table('ads')
            ->join('payments', 'ads.id', '=', 'payments.ad_id')
            ->where('ads.user_id', $userId)
            ->where('payments.status', 'completed')
            ->selectRaw('
                COUNT(*) as paid_ads,
                SUM(payments.amount) as total_revenue,
                AVG(payments.amount) as average_payment,
                MIN(payments.created_at) as first_payment,
                MAX(payments.created_at) as latest_payment
            ')
            ->first();

        return [
            'paid_ads' => $revenue->paid_ads ?? 0,
            'total_revenue' => $revenue->total_revenue ?? 0,
            'average_payment' => $revenue->average_payment ?? 0,
            'first_payment' => $revenue->first_payment,
            'latest_payment' => $revenue->latest_payment,
        ];
    }

    /**
     * Валидировать данные объявления
     */
    private function validateAdData(array $data): ?array
    {
        // Проверяем обязательные поля
        if (empty($data['title']) || strlen($data['title']) < 3) {
            return null;
        }

        $title = trim($data['title']);
        if (strlen($title) > 100) {
            return null;
        }

        // Проверяем описание (опционально, но если есть - валидируем)
        $description = trim($data['description'] ?? '');
        if (strlen($description) > 2000) {
            return null;
        }

        // Проверяем цену
        $price = null;
        if (isset($data['price']) && is_numeric($data['price'])) {
            $price = (float) $data['price'];
            if ($price < 0 || $price > 100000) {
                return null;
            }
        }

        // Проверяем категорию
        $allowedCategories = ['massage', 'spa', 'wellness', 'fitness', 'beauty'];
        $category = $data['category'] ?? 'massage';
        if (!in_array($category, $allowedCategories)) {
            $category = 'massage';
        }

        return [
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'specialty' => trim($data['specialty'] ?? ''),
            'price' => $price,
            'status' => $data['status'] ?? 'draft',
        ];
    }
}