<?php

namespace App\Domain\Analytics\Services\Reports;

use App\Domain\Analytics\Models\UserAction;
use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;
use App\Domain\Analytics\Models\PageView;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Сборщик статистических данных для отчетов
 */
class StatsCollector
{
    /**
     * Получить статистику роста пользователей
     */
    public function getUserGrowthStats(Carbon $from, Carbon $to): array
    {
        $registrations = UserAction::inPeriod($from, $to)
            ->byActionType(UserAction::ACTION_REGISTER)
            ->select(DB::raw('DATE(performed_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        $totalUsers = User::where('created_at', '<=', $to)->count();
        $newUsers = UserAction::inPeriod($from, $to)
            ->byActionType(UserAction::ACTION_REGISTER)
            ->count();

        $growthRate = $this->calculateUserGrowthRate($from, $to);

        return [
            'total_users' => $totalUsers,
            'new_users' => $newUsers,
            'daily_registrations' => $registrations,
            'growth_rate' => $growthRate,
            'average_daily_registrations' => count($registrations) > 0 ? round(array_sum($registrations) / count($registrations), 2) : 0,
        ];
    }

    /**
     * Получить статистику по контенту
     */
    public function getContentStats(Carbon $from, Carbon $to): array
    {
        $newAds = UserAction::inPeriod($from, $to)
            ->byActionType(UserAction::ACTION_CREATE_AD)
            ->count();

        $totalAds = Ad::where('created_at', '<=', $to)->count();

        $adViews = PageView::inPeriod($from, $to)
            ->where('viewable_type', Ad::class)
            ->count();

        $uniqueAdViews = PageView::inPeriod($from, $to)
            ->where('viewable_type', Ad::class)
            ->distinct('viewable_id', 'ip_address')
            ->count();

        return [
            'new_ads' => $newAds,
            'total_ads' => $totalAds,
            'ad_views' => $adViews,
            'unique_ad_views' => $uniqueAdViews,
            'views_per_ad' => $totalAds > 0 ? round($adViews / $totalAds, 2) : 0,
            'unique_views_per_ad' => $totalAds > 0 ? round($uniqueAdViews / $totalAds, 2) : 0,
        ];
    }

    /**
     * Получить статистику активности пользователей
     */
    public function getUserActivityStats(Carbon $from, Carbon $to): array
    {
        $activeUsers = UserAction::inPeriod($from, $to)
            ->distinct('user_id')
            ->count();

        $totalActions = UserAction::inPeriod($from, $to)->count();

        $actionTypes = UserAction::inPeriod($from, $to)
            ->select('action_type', DB::raw('count(*) as count'))
            ->groupBy('action_type')
            ->orderBy('count', 'desc')
            ->get()
            ->pluck('count', 'action_type')
            ->toArray();

        return [
            'active_users' => $activeUsers,
            'total_actions' => $totalActions,
            'actions_per_user' => $activeUsers > 0 ? round($totalActions / $activeUsers, 2) : 0,
            'action_types_breakdown' => $actionTypes,
        ];
    }

    /**
     * Получить статистику источников трафика
     */
    public function getTrafficSourceStats(Carbon $from, Carbon $to): array
    {
        $sources = PageView::inPeriod($from, $to)
            ->select(
                DB::raw('CASE 
                    WHEN referrer IS NULL OR referrer = "" THEN "direct"
                    WHEN referrer LIKE "%google%" THEN "google"
                    WHEN referrer LIKE "%yandex%" THEN "yandex"
                    WHEN referrer LIKE "%facebook%" THEN "facebook"
                    WHEN referrer LIKE "%vk.com%" THEN "vkontakte"
                    ELSE "other"
                END as source'),
                DB::raw('count(*) as count'),
                DB::raw('count(distinct ip_address) as unique_visitors')
            )
            ->groupBy('source')
            ->orderBy('count', 'desc')
            ->get();

        $totalViews = $sources->sum('count');

        return [
            'sources' => $sources->map(function ($source) use ($totalViews) {
                return [
                    'source' => $source->source,
                    'views' => $source->count,
                    'unique_visitors' => $source->unique_visitors,
                    'percentage' => $totalViews > 0 ? round(($source->count / $totalViews) * 100, 2) : 0,
                ];
            })->toArray(),
            'total_views' => $totalViews,
        ];
    }

    /**
     * Получить географическую статистику
     */
    public function getGeographicStats(Carbon $from, Carbon $to): array
    {
        $countries = PageView::inPeriod($from, $to)
            ->whereNotNull('country')
            ->select('country', DB::raw('count(*) as views'), DB::raw('count(distinct ip_address) as unique_visitors'))
            ->groupBy('country')
            ->orderBy('views', 'desc')
            ->limit(20)
            ->get();

        $cities = PageView::inPeriod($from, $to)
            ->whereNotNull('city')
            ->select('city', 'country', DB::raw('count(*) as views'))
            ->groupBy('city', 'country')
            ->orderBy('views', 'desc')
            ->limit(15)
            ->get();

        return [
            'countries' => $countries->toArray(),
            'cities' => $cities->toArray(),
            'total_countries' => $countries->count(),
        ];
    }

    /**
     * Получить статистику устройств и браузеров
     */
    public function getDeviceBrowserStats(Carbon $from, Carbon $to): array
    {
        $devices = PageView::inPeriod($from, $to)
            ->select('device_type', DB::raw('count(*) as count'))
            ->groupBy('device_type')
            ->get()
            ->pluck('count', 'device_type')
            ->toArray();

        $browsers = PageView::inPeriod($from, $to)
            ->whereNotNull('user_agent')
            ->select('user_agent', DB::raw('count(*) as count'))
            ->groupBy('user_agent')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return [
            'devices' => $devices,
            'browsers' => $browsers->map(function ($browser) {
                return [
                    'user_agent' => $browser->user_agent,
                    'count' => $browser->count,
                    'browser_name' => $this->extractBrowserName($browser->user_agent),
                ];
            })->toArray(),
        ];
    }

    /**
     * Рассчитать темп роста пользователей
     */
    private function calculateUserGrowthRate(Carbon $from, Carbon $to): float
    {
        $days = $from->diffInDays($to) + 1;
        $previousPeriodFrom = $from->copy()->subDays($days);
        $previousPeriodTo = $from->copy()->subDay();

        $currentPeriodUsers = UserAction::inPeriod($from, $to)
            ->byActionType(UserAction::ACTION_REGISTER)
            ->count();

        $previousPeriodUsers = UserAction::inPeriod($previousPeriodFrom, $previousPeriodTo)
            ->byActionType(UserAction::ACTION_REGISTER)
            ->count();

        if ($previousPeriodUsers == 0) {
            return $currentPeriodUsers > 0 ? 100 : 0;
        }

        return round((($currentPeriodUsers - $previousPeriodUsers) / $previousPeriodUsers) * 100, 2);
    }

    /**
     * Извлечь название браузера из User-Agent
     */
    private function extractBrowserName(string $userAgent): string
    {
        if (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        } elseif (strpos($userAgent, 'Opera') !== false) {
            return 'Opera';
        } else {
            return 'Other';
        }
    }

    /**
     * Получить статистику времени загрузки страниц
     */
    public function getPageLoadStats(Carbon $from, Carbon $to): array
    {
        $loadTimes = PageView::inPeriod($from, $to)
            ->whereNotNull('load_time_ms')
            ->where('load_time_ms', '>', 0)
            ->pluck('load_time_ms');

        if ($loadTimes->isEmpty()) {
            return [
                'average_load_time' => 0,
                'median_load_time' => 0,
                'slow_pages_count' => 0,
                'fast_pages_count' => 0,
            ];
        }

        $average = $loadTimes->avg();
        $median = $loadTimes->median();
        $slowPages = $loadTimes->filter(fn($time) => $time > 3000)->count(); // > 3 секунд
        $fastPages = $loadTimes->filter(fn($time) => $time < 1000)->count(); // < 1 секунды

        return [
            'average_load_time' => round($average, 2),
            'median_load_time' => round($median, 2),
            'slow_pages_count' => $slowPages,
            'fast_pages_count' => $fastPages,
            'total_measured_pages' => $loadTimes->count(),
        ];
    }
}