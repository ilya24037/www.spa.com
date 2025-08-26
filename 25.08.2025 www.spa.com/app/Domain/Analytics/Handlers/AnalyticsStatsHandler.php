<?php

namespace App\Domain\Analytics\Handlers;

use App\Domain\Analytics\Models\PageView;
use App\Domain\Analytics\Models\UserAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * Обработчик статистики и аналитики
 */
class AnalyticsStatsHandler
{
    /**
     * Получить статистику просмотров за период
     */
    public function getPageViewStats(Carbon $from, Carbon $to, ?string $viewableType = null): array
    {
        $query = PageView::query()
            ->inPeriod($from, $to)
            ->notBots();

        if ($viewableType) {
            $query->byViewableType($viewableType);
        }

        $totalViews = $query->count();
        $uniqueViews = $query->unique()->count();
        
        $deviceStats = $query->groupByDevice()
            ->select('device_type', DB::raw('count(*) as count'))
            ->get()
            ->pluck('count', 'device_type')
            ->toArray();

        $countryStats = $query->groupByCountry()
            ->select('country', DB::raw('count(*) as count'))
            ->whereNotNull('country')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->pluck('count', 'country')
            ->toArray();

        $dailyStats = $query->select(
                DB::raw('DATE(viewed_at) as date'),
                DB::raw('count(*) as views'),
                DB::raw('count(distinct ip_address) as unique_views')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->toArray();

        $averageDuration = $query->where('duration_seconds', '>', 0)
            ->avg('duration_seconds');

        return [
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'days' => $from->diffInDays($to) + 1,
            ],
            'totals' => [
                'total_views' => $totalViews,
                'unique_views' => $uniqueViews,
                'average_duration' => round($averageDuration ?? 0, 2),
            ],
            'devices' => $deviceStats,
            'countries' => $countryStats,
            'daily' => $dailyStats,
        ];
    }

    /**
     * Получить статистику действий за период
     */
    public function getUserActionStats(Carbon $from, Carbon $to, ?string $actionType = null): array
    {
        $query = UserAction::query()->inPeriod($from, $to);

        if ($actionType) {
            $query->byActionType($actionType);
        }

        $totalActions = $query->count();
        $totalConversions = $query->conversions()->count();
        $conversionRate = $totalActions > 0 ? ($totalConversions / $totalActions) * 100 : 0;
        
        $actionTypeStats = $query->groupByActionType()
            ->select('action_type', DB::raw('count(*) as count'))
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->action_type => $item->count];
            })
            ->toArray();

        $userStats = $query->groupByUser()
            ->select('user_id', DB::raw('count(*) as count'))
            ->whereNotNull('user_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->with('user:id,name')
            ->get();

        $dailyStats = $query->select(
                DB::raw('DATE(performed_at) as date'),
                DB::raw('count(*) as actions'),
                DB::raw('count(case when is_conversion = 1 then 1 end) as conversions')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->toArray();

        $conversionValue = $query->conversions()->sum('conversion_value');

        return [
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'days' => $from->diffInDays($to) + 1,
            ],
            'totals' => [
                'total_actions' => $totalActions,
                'total_conversions' => $totalConversions,
                'conversion_rate' => round($conversionRate, 2),
                'conversion_value' => $conversionValue,
            ],
            'by_action_type' => $actionTypeStats,
            'top_users' => $userStats->toArray(),
            'daily' => $dailyStats,
        ];
    }

    /**
     * Получить популярные страницы
     */
    public function getTopPages(Carbon $from, Carbon $to, int $limit = 10): Collection
    {
        return PageView::query()
            ->inPeriod($from, $to)
            ->notBots()
            ->select('url', 'title', DB::raw('count(*) as views'), 
                     DB::raw('count(distinct ip_address) as unique_views'),
                     DB::raw('avg(duration_seconds) as avg_duration'))
            ->groupBy('url', 'title')
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить статистику по устройствам
     */
    public function getDeviceStats(Carbon $from, Carbon $to): array
    {
        $pageViews = PageView::inPeriod($from, $to)
            ->notBots()
            ->select('device_type', 'browser', 'platform', 
                     DB::raw('count(*) as views'),
                     DB::raw('avg(duration_seconds) as avg_duration'))
            ->groupBy('device_type', 'browser', 'platform')
            ->orderBy('views', 'desc')
            ->get();

        $deviceTotals = $pageViews->groupBy('device_type')
            ->map(function ($group) {
                return [
                    'views' => $group->sum('views'),
                    'avg_duration' => $group->avg('avg_duration'),
                    'browsers' => $group->groupBy('browser')->map->count(),
                    'platforms' => $group->groupBy('platform')->map->count(),
                ];
            });

        return [
            'devices' => $deviceTotals->toArray(),
            'detailed' => $pageViews->toArray(),
        ];
    }

    /**
     * Получить активность пользователя
     */
    public function getUserActivity(int $userId, int $days = 30): array
    {
        $from = now()->subDays($days);
        $to = now();

        $pageViews = PageView::byUser($userId)
            ->inPeriod($from, $to)
            ->count();

        $actions = UserAction::byUser($userId)
            ->inPeriod($from, $to)
            ->get()
            ->groupBy('action_type')
            ->map->count()
            ->toArray();

        $lastActivity = UserAction::byUser($userId)
            ->orderBy('performed_at', 'desc')
            ->first();

        return [
            'user_id' => $userId,
            'period_days' => $days,
            'page_views' => $pageViews,
            'actions' => $actions,
            'last_activity' => $lastActivity ? $lastActivity->performed_at : null,
            'total_actions' => array_sum($actions),
        ];
    }

    /**
     * Получить статистику источников трафика
     */
    public function getTrafficSources(Carbon $from, Carbon $to): array
    {
        $referrerStats = PageView::inPeriod($from, $to)
            ->notBots()
            ->whereNotNull('referrer')
            ->select('referrer', DB::raw('count(*) as visits'))
            ->groupBy('referrer')
            ->orderBy('visits', 'desc')
            ->limit(20)
            ->get();

        $directTraffic = PageView::inPeriod($from, $to)
            ->notBots()
            ->whereNull('referrer')
            ->count();

        return [
            'referrers' => $referrerStats->toArray(),
            'direct_traffic' => $directTraffic,
            'total_visits' => $referrerStats->sum('visits') + $directTraffic,
        ];
    }

    /**
     * Получить статистику по времени
     */
    public function getHourlyStats(Carbon $from, Carbon $to): array
    {
        $hourlyViews = PageView::inPeriod($from, $to)
            ->notBots()
            ->select(
                DB::raw('HOUR(viewed_at) as hour'),
                DB::raw('count(*) as views')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour')
            ->toArray();

        $hourlyActions = UserAction::inPeriod($from, $to)
            ->select(
                DB::raw('HOUR(performed_at) as hour'),
                DB::raw('count(*) as actions')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour')
            ->toArray();

        return [
            'hourly_views' => $hourlyViews,
            'hourly_actions' => $hourlyActions,
            'peak_hours' => $this->findPeakHours($hourlyViews, $hourlyActions),
        ];
    }

    /**
     * Найти пиковые часы
     */
    protected function findPeakHours(array $views, array $actions): array
    {
        $combined = [];
        
        for ($hour = 0; $hour < 24; $hour++) {
            $viewCount = $views[$hour]['views'] ?? 0;
            $actionCount = $actions[$hour]['actions'] ?? 0;
            
            $combined[$hour] = $viewCount + $actionCount;
        }

        arsort($combined);
        
        return array_slice(array_keys($combined), 0, 3, true);
    }

    /**
     * Получить сравнительную статистику
     */
    public function getComparativeStats(Carbon $currentFrom, Carbon $currentTo, Carbon $previousFrom, Carbon $previousTo): array
    {
        $currentStats = $this->getBasicStats($currentFrom, $currentTo);
        $previousStats = $this->getBasicStats($previousFrom, $previousTo);

        return [
            'current' => $currentStats,
            'previous' => $previousStats,
            'changes' => [
                'page_views' => $this->calculateChange($previousStats['page_views'], $currentStats['page_views']),
                'unique_visitors' => $this->calculateChange($previousStats['unique_visitors'], $currentStats['unique_visitors']),
                'user_actions' => $this->calculateChange($previousStats['user_actions'], $currentStats['user_actions']),
                'conversions' => $this->calculateChange($previousStats['conversions'], $currentStats['conversions']),
            ],
        ];
    }

    /**
     * Получить базовую статистику за период
     */
    protected function getBasicStats(Carbon $from, Carbon $to): array
    {
        return [
            'page_views' => PageView::inPeriod($from, $to)->notBots()->count(),
            'unique_visitors' => PageView::inPeriod($from, $to)->notBots()->distinct('ip_address')->count(),
            'user_actions' => UserAction::inPeriod($from, $to)->count(),
            'conversions' => UserAction::inPeriod($from, $to)->conversions()->count(),
        ];
    }

    /**
     * Рассчитать изменение в процентах
     */
    protected function calculateChange(int $old, int $new): array
    {
        if ($old === 0) {
            $percentage = $new > 0 ? 100 : 0;
        } else {
            $percentage = round((($new - $old) / $old) * 100, 2);
        }

        return [
            'absolute' => $new - $old,
            'percentage' => $percentage,
            'direction' => $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'same'),
        ];
    }
}