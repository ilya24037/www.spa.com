<?php

namespace App\Domain\Analytics\Handlers;

use App\Domain\Analytics\Models\PageView;
use App\Domain\Analytics\Models\UserAction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Обработчик воронок конверсий и метрик производительности
 */
class AnalyticsConversionHandler
{
    /**
     * Получить воронку конверсий
     */
    public function getConversionFunnel(Carbon $from, Carbon $to): array
    {
        $steps = [
            'visits' => PageView::inPeriod($from, $to)->notBots()->count(),
            'registrations' => UserAction::inPeriod($from, $to)
                ->byActionType(UserAction::ACTION_REGISTER)->count(),
            'profile_updates' => UserAction::inPeriod($from, $to)
                ->byActionType(UserAction::ACTION_UPDATE_PROFILE)->count(),
            'ads_created' => UserAction::inPeriod($from, $to)
                ->byActionType(UserAction::ACTION_CREATE_AD)->count(),
            'bookings_made' => UserAction::inPeriod($from, $to)
                ->byActionType(UserAction::ACTION_BOOK_SERVICE)->count(),
            'payments_completed' => UserAction::inPeriod($from, $to)
                ->byActionType(UserAction::ACTION_MAKE_PAYMENT)->count(),
        ];

        $funnel = [];
        $previousCount = null;

        foreach ($steps as $step => $count) {
            $conversionRate = $previousCount ? ($count / $previousCount) * 100 : 100;
            
            $funnel[] = [
                'step' => $step,
                'count' => $count,
                'conversion_rate' => round($conversionRate, 2),
                'drop_off' => $previousCount ? $previousCount - $count : 0,
                'drop_off_rate' => $previousCount ? round((($previousCount - $count) / $previousCount) * 100, 2) : 0,
            ];

            $previousCount = $count;
        }

        return $funnel;
    }

    /**
     * Получить метрики производительности
     */
    public function getPerformanceMetrics(Carbon $from, Carbon $to): array
    {
        $totalPageViews = PageView::inPeriod($from, $to)->count();
        $totalActions = UserAction::inPeriod($from, $to)->count();
        
        $avgPageLoadTime = PageView::inPeriod($from, $to)
            ->where('duration_seconds', '>', 0)
            ->where('duration_seconds', '<', 300) // Исключаем аномально долгие сессии
            ->avg('duration_seconds');

        $bounceRate = $this->calculateBounceRate($from, $to);
        $sessionCount = $this->getSessionCount($from, $to);

        return [
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'total_page_views' => $totalPageViews,
            'total_actions' => $totalActions,
            'total_sessions' => $sessionCount,
            'avg_page_load_time' => round($avgPageLoadTime ?? 0, 2),
            'bounce_rate' => round($bounceRate, 2),
            'pages_per_session' => $sessionCount > 0 ? round($totalPageViews / $sessionCount, 2) : 0,
            'actions_per_session' => $sessionCount > 0 ? round($totalActions / $sessionCount, 2) : 0,
        ];
    }

    /**
     * Получить детальные метрики конверсий
     */
    public function getDetailedConversionMetrics(Carbon $from, Carbon $to): array
    {
        $conversionsByType = UserAction::inPeriod($from, $to)
            ->conversions()
            ->select('action_type', 
                     DB::raw('count(*) as count'), 
                     DB::raw('sum(conversion_value) as total_value'),
                     DB::raw('avg(conversion_value) as avg_value'))
            ->groupBy('action_type')
            ->get()
            ->keyBy('action_type')
            ->toArray();

        $totalConversions = array_sum(array_column($conversionsByType, 'count'));
        $totalValue = array_sum(array_column($conversionsByType, 'total_value'));

        $conversionsByHour = UserAction::inPeriod($from, $to)
            ->conversions()
            ->select(DB::raw('HOUR(performed_at) as hour'), 
                     DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour')
            ->toArray();

        $conversionsByDay = UserAction::inPeriod($from, $to)
            ->conversions()
            ->select(DB::raw('DAYNAME(performed_at) as day'), 
                     DB::raw('count(*) as count'))
            ->groupBy('day')
            ->get()
            ->keyBy('day')
            ->toArray();

        return [
            'overview' => [
                'total_conversions' => $totalConversions,
                'total_value' => $totalValue,
                'avg_conversion_value' => $totalConversions > 0 ? round($totalValue / $totalConversions, 2) : 0,
            ],
            'by_type' => $conversionsByType,
            'by_hour' => $conversionsByHour,
            'by_day' => $conversionsByDay,
        ];
    }

    /**
     * Рассчитать показатель отказов
     */
    public function calculateBounceRate(Carbon $from, Carbon $to): float
    {
        $totalSessions = $this->getSessionCount($from, $to);
        
        if ($totalSessions === 0) {
            return 0;
        }

        $singlePageSessions = PageView::inPeriod($from, $to)
            ->select('session_id')
            ->groupBy('session_id')
            ->havingRaw('count(*) = 1')
            ->count();

        return ($singlePageSessions / $totalSessions) * 100;
    }

    /**
     * Получить количество сессий
     */
    public function getSessionCount(Carbon $from, Carbon $to): int
    {
        return PageView::inPeriod($from, $to)
            ->distinct('session_id')
            ->count();
    }

    /**
     * Получить когортный анализ
     */
    public function getCohortAnalysis(Carbon $from, Carbon $to, string $period = 'week'): array
    {
        // Группируем пользователей по периодам регистрации
        $cohorts = UserAction::where('action_type', UserAction::ACTION_REGISTER)
            ->inPeriod($from, $to)
            ->select(
                DB::raw($this->getCohortGrouping($period) . ' as cohort_period'),
                'user_id',
                'performed_at as registration_date'
            )
            ->get()
            ->groupBy('cohort_period');

        $cohortData = [];

        foreach ($cohorts as $period => $users) {
            $userIds = $users->pluck('user_id')->toArray();
            
            // Анализируем активность пользователей из этой когорты
            $cohortData[$period] = [
                'users' => count($userIds),
                'retention' => $this->calculateRetention($userIds, $users->first()->registration_date, $to),
                'conversion_rates' => $this->calculateCohortConversions($userIds, $from, $to),
            ];
        }

        return $cohortData;
    }

    /**
     * Рассчитать удержание пользователей
     */
    protected function calculateRetention(array $userIds, Carbon $registrationDate, Carbon $endDate): array
    {
        $retention = [];
        $totalUsers = count($userIds);

        // Проверяем активность через разные интервалы
        $intervals = [1, 7, 14, 30, 60, 90];

        foreach ($intervals as $days) {
            $checkDate = $registrationDate->copy()->addDays($days);
            
            if ($checkDate->gt($endDate)) {
                break;
            }

            $activeUsers = UserAction::whereIn('user_id', $userIds)
                ->whereDate('performed_at', $checkDate)
                ->distinct('user_id')
                ->count();

            $retention["day_{$days}"] = [
                'active_users' => $activeUsers,
                'retention_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0,
            ];
        }

        return $retention;
    }

    /**
     * Рассчитать конверсии для когорты
     */
    protected function calculateCohortConversions(array $userIds, Carbon $from, Carbon $to): array
    {
        return UserAction::whereIn('user_id', $userIds)
            ->inPeriod($from, $to)
            ->conversions()
            ->select('action_type', DB::raw('count(*) as count'))
            ->groupBy('action_type')
            ->get()
            ->pluck('count', 'action_type')
            ->toArray();
    }

    /**
     * Получить группировку для когорт
     */
    protected function getCohortGrouping(string $period): string
    {
        return match($period) {
            'day' => 'DATE(performed_at)',
            'week' => 'YEARWEEK(performed_at)',
            'month' => 'DATE_FORMAT(performed_at, "%Y-%m")',
            default => 'YEARWEEK(performed_at)',
        };
    }

    /**
     * Получить анализ пути пользователя
     */
    public function getUserJourneyAnalysis(Carbon $from, Carbon $to): array
    {
        // Анализируем последовательности действий пользователей
        $journeys = DB::table('user_actions')
            ->join('page_views', 'user_actions.user_id', '=', 'page_views.user_id')
            ->whereBetween('user_actions.performed_at', [$from, $to])
            ->whereBetween('page_views.viewed_at', [$from, $to])
            ->whereNotNull('user_actions.user_id')
            ->select([
                'user_actions.user_id',
                'user_actions.action_type',
                'user_actions.performed_at',
                'page_views.url',
                'page_views.viewed_at'
            ])
            ->orderBy('user_actions.user_id')
            ->orderBy('user_actions.performed_at')
            ->get()
            ->groupBy('user_id');

        $pathAnalysis = [];
        
        foreach ($journeys as $userId => $events) {
            $path = $events->pluck('action_type')->unique()->values()->toArray();
            $pathKey = implode(' → ', $path);
            
            if (!isset($pathAnalysis[$pathKey])) {
                $pathAnalysis[$pathKey] = [
                    'count' => 0,
                    'conversion_rate' => 0,
                ];
            }
            
            $pathAnalysis[$pathKey]['count']++;
            
            // Проверяем, завершился ли путь конверсией
            $lastAction = $events->last();
            if ($lastAction && $this->isConversionAction($lastAction->action_type)) {
                $pathAnalysis[$pathKey]['conversions'] = ($pathAnalysis[$pathKey]['conversions'] ?? 0) + 1;
            }
        }

        // Рассчитываем коэффициенты конверсии для каждого пути
        foreach ($pathAnalysis as $path => &$data) {
            $conversions = $data['conversions'] ?? 0;
            $data['conversion_rate'] = $data['count'] > 0 ? round(($conversions / $data['count']) * 100, 2) : 0;
        }

        // Сортируем по популярности
        uasort($pathAnalysis, function($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return array_slice($pathAnalysis, 0, 20, true); // Топ 20 путей
    }

    /**
     * Проверить, является ли действие конверсией
     */
    protected function isConversionAction(string $actionType): bool
    {
        $conversionActions = [
            UserAction::ACTION_REGISTER,
            UserAction::ACTION_CREATE_AD,
            UserAction::ACTION_BOOK_SERVICE,
            UserAction::ACTION_COMPLETE_BOOKING,
            UserAction::ACTION_MAKE_PAYMENT,
            UserAction::ACTION_LEAVE_REVIEW,
            UserAction::ACTION_CONTACT_MASTER,
        ];

        return in_array($actionType, $conversionActions);
    }
}