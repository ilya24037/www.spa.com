<?php

namespace App\Domain\Ad\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Сервис для статистики объявлений
 */
class AdStatisticsService
{
    /**
     * Получить статистику по объявлению
     */
    public function getAdStatistics(Ad $ad): array
    {
        $cacheKey = "ad_stats_{$ad->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($ad) {
            return [
                'views' => $this->getViewsCount($ad),
                'contacts_shown' => $this->getContactsShownCount($ad),
                'favorites' => $this->getFavoritesCount($ad),
                'bookings' => $this->getBookingsCount($ad),
                'conversion_rate' => $this->getConversionRate($ad),
                'daily_views' => $this->getDailyViews($ad),
                'weekly_views' => $this->getWeeklyViews($ad),
                'monthly_views' => $this->getMonthlyViews($ad),
            ];
        });
    }

    /**
     * Получить статистику пользователя по объявлениям
     */
    public function getUserAdStatistics(User $user): array
    {
        $ads = $user->ads()->with('views', 'favorites', 'bookings')->get();

        return [
            'total_ads' => $ads->count(),
            'active_ads' => $ads->where('status', 'active')->count(),
            'draft_ads' => $ads->where('status', 'draft')->count(),
            'archived_ads' => $ads->where('status', 'archived')->count(),
            'total_views' => $ads->sum(fn($ad) => $this->getViewsCount($ad)),
            'total_favorites' => $ads->sum(fn($ad) => $this->getFavoritesCount($ad)),
            'total_bookings' => $ads->sum(fn($ad) => $this->getBookingsCount($ad)),
            'avg_conversion_rate' => $this->calculateAvgConversionRate($ads),
            'top_performing_ads' => $this->getTopPerformingAds($ads),
            'low_performing_ads' => $this->getLowPerformingAds($ads),
        ];
    }

    /**
     * Получить количество просмотров
     */
    private function getViewsCount(Ad $ad): int
    {
        return DB::table('ad_views')->where('ad_id', $ad->id)->count();
    }

    /**
     * Получить количество показов контактов
     */
    private function getContactsShownCount(Ad $ad): int
    {
        return DB::table('ad_contact_views')->where('ad_id', $ad->id)->count();
    }

    /**
     * Получить количество добавлений в избранное
     */
    private function getFavoritesCount(Ad $ad): int
    {
        // Используем прямой запрос к таблице user_favorites
        return \DB::table('user_favorites')
            ->where('ad_id', $ad->id)
            ->count();
    }

    /**
     * Получить количество бронирований
     */
    private function getBookingsCount(Ad $ad): int
    {
        return $ad->bookings()->count();
    }

    /**
     * Рассчитать конверсию
     */
    private function getConversionRate(Ad $ad): float
    {
        $views = $this->getViewsCount($ad);
        $contacts = $this->getContactsShownCount($ad);

        if ($views === 0) {
            return 0.0;
        }

        return round(($contacts / $views) * 100, 2);
    }

    /**
     * Получить просмотры по дням
     */
    private function getDailyViews(Ad $ad): Collection
    {
        return DB::table('ad_views')
            ->where('ad_id', $ad->id)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as views')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Получить еженедельную статистику
     */
    private function getWeeklyViews(Ad $ad): Collection
    {
        return DB::table('ad_views')
            ->where('ad_id', $ad->id)
            ->selectRaw('WEEK(created_at) as week, YEAR(created_at) as year, COUNT(*) as views')
            ->where('created_at', '>=', now()->subWeeks(12))
            ->groupByRaw('YEAR(created_at), WEEK(created_at)')
            ->orderByRaw('year DESC, week DESC')
            ->get();
    }

    /**
     * Получить месячную статистику
     */
    private function getMonthlyViews(Ad $ad): Collection
    {
        return DB::table('ad_views')
            ->where('ad_id', $ad->id)
            ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as views')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->orderByRaw('year DESC, month DESC')
            ->get();
    }

    /**
     * Рассчитать среднюю конверсию
     */
    private function calculateAvgConversionRate(Collection $ads): float
    {
        if ($ads->isEmpty()) {
            return 0.0;
        }

        $totalConversion = $ads->sum(fn($ad) => $this->getConversionRate($ad));
        return round($totalConversion / $ads->count(), 2);
    }

    /**
     * Получить топ объявления
     */
    private function getTopPerformingAds(Collection $ads, int $limit = 5): Collection
    {
        return $ads->sortByDesc(function ($ad) {
            return $this->getViewsCount($ad) + $this->getFavoritesCount($ad) * 2;
        })->take($limit)->values();
    }

    /**
     * Получить объявления с низкой эффективностью
     */
    private function getLowPerformingAds(Collection $ads, int $limit = 5): Collection
    {
        return $ads->sortBy(function ($ad) {
            return $this->getViewsCount($ad) + $this->getFavoritesCount($ad) * 2;
        })->take($limit)->values();
    }

    /**
     * Записать просмотр объявления
     */
    public function recordView(Ad $ad, ?User $user = null): void
    {
        DB::table('ad_views')->insert([
            'ad_id' => $ad->id,
            'user_id' => $user?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        // Инвалидируем кеш статистики
        Cache::forget("ad_stats_{$ad->id}");
    }

    /**
     * Записать показ контактов
     */
    public function recordContactView(Ad $ad, User $user): void
    {
        DB::table('ad_contact_views')->insert([
            'ad_id' => $ad->id,
            'user_id' => $user->id,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        Cache::forget("ad_stats_{$ad->id}");
    }
}