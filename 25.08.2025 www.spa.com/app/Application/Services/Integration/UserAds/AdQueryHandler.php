<?php

namespace App\Application\Services\Integration\UserAds;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Обработчик запросов к объявлениям пользователя
 */
class AdQueryHandler
{
    /**
     * Получить все объявления пользователя
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
     * Получить количество всех объявлений
     */
    public function getUserAdsCount(int $userId): int
    {
        return DB::table('ads')
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * Получить количество активных объявлений
     */
    public function getUserActiveAdsCount(int $userId): int
    {
        return DB::table('ads')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Проверить принадлежит ли объявление пользователю
     */
    public function userOwnsAd(int $userId, int $adId): bool
    {
        return DB::table('ads')
            ->where('id', $adId)
            ->where('user_id', $userId)
            ->exists();
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
}