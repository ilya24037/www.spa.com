<?php

namespace App\Application\Services\Integration\UserAds;

use Illuminate\Support\Facades\DB;

/**
 * Обработчик статистики и аналитики объявлений
 */
class AdStatisticsHandler
{
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
     * Получить топ объявлений по просмотрам
     */
    public function getUserTopAds(int $userId, int $limit = 5): array
    {
        return DB::table('ads')
            ->leftJoin('ad_statistics', 'ads.id', '=', 'ad_statistics.ad_id')
            ->where('ads.user_id', $userId)
            ->whereIn('ads.status', ['active', 'pending'])
            ->select('ads.id', 'ads.title', 'ad_statistics.views_count')
            ->orderBy('ad_statistics.views_count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Получить эффективность объявлений (конверсия просмотр -> контакт)
     */
    public function getUserAdsEfficiency(int $userId): array
    {
        $efficiency = DB::table('ads')
            ->leftJoin('ad_statistics', 'ads.id', '=', 'ad_statistics.ad_id')
            ->where('ads.user_id', $userId)
            ->whereIn('ads.status', ['active'])
            ->selectRaw('
                COUNT(*) as total_active_ads,
                SUM(COALESCE(ad_statistics.views_count, 0)) as total_views,
                SUM(COALESCE(ad_statistics.contacts_count, 0)) as total_contacts,
                AVG(COALESCE(ad_statistics.views_count, 0)) as avg_views_per_ad,
                AVG(COALESCE(ad_statistics.contacts_count, 0)) as avg_contacts_per_ad
            ')
            ->first();

        $conversion_rate = 0;
        if ($efficiency->total_views > 0) {
            $conversion_rate = ($efficiency->total_contacts / $efficiency->total_views) * 100;
        }

        return [
            'total_active_ads' => $efficiency->total_active_ads ?? 0,
            'total_views' => $efficiency->total_views ?? 0,
            'total_contacts' => $efficiency->total_contacts ?? 0,
            'avg_views_per_ad' => round($efficiency->avg_views_per_ad ?? 0, 2),
            'avg_contacts_per_ad' => round($efficiency->avg_contacts_per_ad ?? 0, 2),
            'conversion_rate' => round($conversion_rate, 2),
        ];
    }

    /**
     * Получить динамику создания объявлений по месяцам
     */
    public function getUserAdsGrowth(int $userId, int $months = 12): array
    {
        return DB::table('ads')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subMonths($months))
            ->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                COUNT(*) as ads_count
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('ads_count', 'month')
            ->toArray();
    }

    /**
     * Сравнить пользователя с остальными (средние показатели)
     */
    public function getUserComparison(int $userId): array
    {
        $userStats = $this->getUserAdsStatistics($userId);
        
        $globalStats = DB::table('ads')
            ->selectRaw('
                AVG(total_ads) as avg_total_ads,
                AVG(active_ads) as avg_active_ads
            ')
            ->fromSub(function($query) {
                $query->from('ads')
                    ->selectRaw('
                        user_id,
                        COUNT(*) as total_ads,
                        COUNT(CASE WHEN status = "active" THEN 1 END) as active_ads
                    ')
                    ->groupBy('user_id');
            }, 'user_stats')
            ->first();

        return [
            'user_total_ads' => $userStats['total_ads'],
            'user_active_ads' => $userStats['active_ads'],
            'platform_avg_total_ads' => round($globalStats->avg_total_ads ?? 0, 1),
            'platform_avg_active_ads' => round($globalStats->avg_active_ads ?? 0, 1),
            'performance_vs_average' => [
                'total_ads' => $userStats['total_ads'] > ($globalStats->avg_total_ads ?? 0) ? 'above' : 'below',
                'active_ads' => $userStats['active_ads'] > ($globalStats->avg_active_ads ?? 0) ? 'above' : 'below',
            ]
        ];
    }
}