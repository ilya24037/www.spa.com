<?php

namespace App\Application\Services\Integration;

use App\Domain\Favorite\Events\FavoriteAdded;
use App\Domain\Favorite\Events\FavoriteRemoved;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

/**
 * Сервис интеграции User ↔ Favorites доменов
 * Заменяет прямые связи через трейт HasFavorites
 */
class UserFavoritesIntegrationService
{
    /**
     * Получить все избранные объявления пользователя
     * Заменяет: $user->favorites()
     */
    public function getUserFavorites(int $userId): Collection
    {
        // Временно используем прямой запрос до создания Favorite домена
        return DB::table('user_favorites')
            ->join('ads', 'ads.id', '=', 'user_favorites.ad_id')
            ->where('user_favorites.user_id', $userId)
            ->where('ads.status', 'active')
            ->select('ads.*', 'user_favorites.created_at as favorited_at')
            ->orderBy('user_favorites.created_at', 'desc')
            ->get();
    }

    /**
     * Получить количество избранных объявлений пользователя
     * Заменяет: $user->favorites()->count()
     */
    public function getUserFavoritesCount(int $userId): int
    {
        return DB::table('user_favorites')
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * Проверить находится ли объявление в избранном пользователя
     * Заменяет: $user->favorites()->where('ad_id', $adId)->exists()
     */
    public function userHasFavorite(int $userId, int $adId): bool
    {
        return DB::table('user_favorites')
            ->where('user_id', $userId)
            ->where('ad_id', $adId)
            ->exists();
    }

    /**
     * Добавить объявление в избранное через событие
     * Заменяет: $user->favorites()->attach($adId)
     */
    public function addToUserFavorites(int $userId, int $adId): bool
    {
        try {
            // Проверяем лимиты и бизнес-правила
            if (!$this->canUserAddToFavorites($userId, $adId)) {
                return false;
            }

            // Проверяем что еще не в избранном
            if ($this->userHasFavorite($userId, $adId)) {
                return true; // Уже в избранном
            }

            // Добавляем в БД
            DB::table('user_favorites')->insert([
                'user_id' => $userId,
                'ad_id' => $adId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Отправляем событие
            Event::dispatch(new FavoriteAdded($userId, $adId));

            return true;

        } catch (\Exception $e) {
            \Log::error('Failed to add to favorites', [
                'user_id' => $userId,
                'ad_id' => $adId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Удалить объявление из избранного через событие
     * Заменяет: $user->favorites()->detach($adId)
     */
    public function removeFromUserFavorites(int $userId, int $adId): bool
    {
        try {
            $deleted = DB::table('user_favorites')
                ->where('user_id', $userId)
                ->where('ad_id', $adId)
                ->delete();

            if ($deleted > 0) {
                // Отправляем событие
                Event::dispatch(new FavoriteRemoved($userId, $adId));
            }

            return $deleted > 0;

        } catch (\Exception $e) {
            \Log::error('Failed to remove from favorites', [
                'user_id' => $userId,
                'ad_id' => $adId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Переключить состояние избранного
     * Новый метод для удобства UI
     */
    public function toggleUserFavorite(int $userId, int $adId): bool
    {
        if ($this->userHasFavorite($userId, $adId)) {
            return $this->removeFromUserFavorites($userId, $adId);
        } else {
            return $this->addToUserFavorites($userId, $adId);
        }
    }

    /**
     * Очистить все избранное пользователя
     * Заменяет: $user->favorites()->detach()
     */
    public function clearUserFavorites(int $userId): int
    {
        $favoriteIds = DB::table('user_favorites')
            ->where('user_id', $userId)
            ->pluck('ad_id')
            ->toArray();

        $deleted = DB::table('user_favorites')
            ->where('user_id', $userId)
            ->delete();

        // Отправляем события для каждого удаленного
        foreach ($favoriteIds as $adId) {
            Event::dispatch(new FavoriteRemoved($userId, $adId));
        }

        return $deleted;
    }

    /**
     * Получить недавно добавленные в избранное
     */
    public function getRecentUserFavorites(int $userId, int $limit = 10): Collection
    {
        return DB::table('user_favorites')
            ->join('ads', 'ads.id', '=', 'user_favorites.ad_id')
            ->where('user_favorites.user_id', $userId)
            ->where('ads.status', 'active')
            ->select('ads.*', 'user_favorites.created_at as favorited_at')
            ->orderBy('user_favorites.created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить статистику избранного пользователя
     */
    public function getUserFavoritesStatistics(int $userId): array
    {
        $stats = DB::table('user_favorites')
            ->join('ads', 'ads.id', '=', 'user_favorites.ad_id')
            ->where('user_favorites.user_id', $userId)
            ->selectRaw('
                COUNT(*) as total_favorites,
                COUNT(CASE WHEN ads.status = "active" THEN 1 END) as active_favorites,
                COUNT(CASE WHEN ads.status = "archived" THEN 1 END) as archived_favorites,
                MIN(user_favorites.created_at) as first_favorite,
                MAX(user_favorites.created_at) as latest_favorite
            ')
            ->first();

        return [
            'total_favorites' => $stats->total_favorites ?? 0,
            'active_favorites' => $stats->active_favorites ?? 0,
            'archived_favorites' => $stats->archived_favorites ?? 0,
            'first_favorite' => $stats->first_favorite,
            'latest_favorite' => $stats->latest_favorite,
        ];
    }

    /**
     * Получить популярные категории в избранном пользователя
     */
    public function getUserFavoriteCategories(int $userId): array
    {
        return DB::table('user_favorites')
            ->join('ads', 'ads.id', '=', 'user_favorites.ad_id')
            ->where('user_favorites.user_id', $userId)
            ->where('ads.status', 'active')
            ->selectRaw('ads.category, COUNT(*) as count')
            ->groupBy('ads.category')
            ->orderBy('count', 'desc')
            ->pluck('count', 'category')
            ->toArray();
    }

    /**
     * Проверить может ли пользователь добавить в избранное
     */
    public function canUserAddToFavorites(int $userId, int $adId): bool
    {
        // Проверяем лимит избранного (например, максимум 100)
        $currentCount = $this->getUserFavoritesCount($userId);
        if ($currentCount >= 100) {
            return false;
        }

        // Проверяем что объявление существует и активно
        $adExists = DB::table('ads')
            ->where('id', $adId)
            ->where('status', 'active')
            ->exists();

        if (!$adExists) {
            return false;
        }

        // Проверяем что пользователь не добавляет свое собственное объявление
        $isOwnAd = DB::table('ads')
            ->where('id', $adId)
            ->where('user_id', $userId)
            ->exists();

        return !$isOwnAd;
    }
}