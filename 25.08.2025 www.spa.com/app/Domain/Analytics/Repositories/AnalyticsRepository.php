<?php

namespace App\Domain\Analytics\Repositories;

use App\Domain\Analytics\Models\PageView;
use App\Domain\Analytics\Models\UserAction;
use App\Domain\Common\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Репозиторий для работы с аналитическими данными
 */
class AnalyticsRepository extends BaseRepository
{
    /**
     * Сохранить просмотр страницы
     */
    public function createPageView(array $data): PageView
    {
        return PageView::create($data);
    }

    /**
     * Сохранить действие пользователя
     */
    public function createUserAction(array $data): UserAction
    {
        return UserAction::create($data);
    }

    /**
     * Обновить длительность просмотра
     */
    public function updatePageViewDuration(int $pageViewId, int $duration): bool
    {
        return PageView::where('id', $pageViewId)
            ->update(['duration_seconds' => $duration]) > 0;
    }

    /**
     * Получить просмотры за период
     */
    public function getPageViewsInPeriod(Carbon $from, Carbon $to, array $filters = []): Collection
    {
        $query = PageView::query()->inPeriod($from, $to);

        if (isset($filters['viewable_type'])) {
            $query->where('viewable_type', $filters['viewable_type']);
        }

        if (isset($filters['not_bots']) && $filters['not_bots']) {
            $query->notBots();
        }

        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        return $query->get();
    }

    /**
     * Получить действия за период
     */
    public function getUserActionsInPeriod(Carbon $from, Carbon $to, array $filters = []): Collection
    {
        $query = UserAction::query()->inPeriod($from, $to);

        if (isset($filters['action_type'])) {
            $query->byActionType($filters['action_type']);
        }

        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (isset($filters['conversions_only']) && $filters['conversions_only']) {
            $query->conversions();
        }

        return $query->get();
    }

    /**
     * Получить статистику просмотров
     */
    public function getPageViewStats(Carbon $from, Carbon $to, ?string $viewableType = null): array
    {
        $query = PageView::query()
            ->inPeriod($from, $to)
            ->notBots();

        if ($viewableType) {
            $query->byViewableType($viewableType);
        }

        return [
            'total' => $query->count(),
            'unique' => $query->distinct('ip_address')->count(),
            'average_duration' => $query->where('duration_seconds', '>', 0)->avg('duration_seconds'),
        ];
    }

    /**
     * Получить статистику действий
     */
    public function getUserActionStats(Carbon $from, Carbon $to, ?string $actionType = null): array
    {
        $query = UserAction::query()->inPeriod($from, $to);

        if ($actionType) {
            $query->byActionType($actionType);
        }

        return [
            'total' => $query->count(),
            'conversions' => $query->conversions()->count(),
            'conversion_value' => $query->conversions()->sum('conversion_value'),
        ];
    }

    /**
     * Получить топ страниц
     */
    public function getTopPages(Carbon $from, Carbon $to, int $limit = 10): Collection
    {
        return PageView::query()
            ->inPeriod($from, $to)
            ->notBots()
            ->select('url', 'title', 
                DB::raw('count(*) as views'), 
                DB::raw('count(distinct ip_address) as unique_views'),
                DB::raw('avg(duration_seconds) as avg_duration'))
            ->groupBy('url', 'title')
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить топ действий
     */
    public function getTopActions(Carbon $from, Carbon $to, int $limit = 10): Collection
    {
        return UserAction::query()
            ->inPeriod($from, $to)
            ->select('action_type', 
                DB::raw('count(*) as count'),
                DB::raw('count(distinct user_id) as unique_users'))
            ->groupBy('action_type')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить активность по дням
     */
    public function getDailyActivity(Carbon $from, Carbon $to): Collection
    {
        return PageView::query()
            ->inPeriod($from, $to)
            ->notBots()
            ->select(
                DB::raw('DATE(viewed_at) as date'),
                DB::raw('count(*) as views'),
                DB::raw('count(distinct ip_address) as unique_views')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Получить воронку конверсий
     */
    public function getConversionFunnel(Carbon $from, Carbon $to, array $steps): array
    {
        $funnel = [];

        foreach ($steps as $step) {
            $count = UserAction::query()
                ->inPeriod($from, $to)
                ->byActionType($step)
                ->count();

            $funnel[$step] = $count;
        }

        return $funnel;
    }

    /**
     * Очистить старые записи
     */
    public function cleanup(Carbon $cutoffDate): array
    {
        return [
            'page_views_deleted' => PageView::where('viewed_at', '<', $cutoffDate)->delete(),
            'user_actions_deleted' => UserAction::where('performed_at', '<', $cutoffDate)->delete(),
        ];
    }

    /**
     * Получить статистику по устройствам
     */
    public function getDeviceStats(Carbon $from, Carbon $to): Collection
    {
        return PageView::query()
            ->inPeriod($from, $to)
            ->notBots()
            ->select('device_type', 'browser', 'platform',
                DB::raw('count(*) as count'))
            ->groupBy('device_type', 'browser', 'platform')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Получить статистику по странам
     */
    public function getCountryStats(Carbon $from, Carbon $to): Collection
    {
        return PageView::query()
            ->inPeriod($from, $to)
            ->notBots()
            ->whereNotNull('country')
            ->select('country', DB::raw('count(*) as count'))
            ->groupBy('country')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Массовая вставка просмотров (для импорта)
     */
    public function bulkInsertPageViews(array $pageViews): int
    {
        return PageView::insert($pageViews);
    }

    /**
     * Массовая вставка действий (для импорта)
     */
    public function bulkInsertUserActions(array $actions): int
    {
        return UserAction::insert($actions);
    }

    /**
     * Получить активных пользователей
     */
    public function getActiveUsers(Carbon $from, Carbon $to, int $limit = 100): Collection
    {
        return UserAction::query()
            ->inPeriod($from, $to)
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('count(*) as actions_count'))
            ->groupBy('user_id')
            ->orderBy('actions_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Проверить существование просмотра
     */
    public function pageViewExists(string $sessionId, string $url, Carbon $from): bool
    {
        return PageView::where('session_id', $sessionId)
            ->where('url', $url)
            ->where('viewed_at', '>=', $from)
            ->exists();
    }

    /**
     * Получить последнее действие пользователя
     */
    public function getLastUserAction(int $userId, ?string $actionType = null): ?UserAction
    {
        $query = UserAction::where('user_id', $userId);

        if ($actionType) {
            $query->where('action_type', $actionType);
        }

        return $query->orderBy('performed_at', 'desc')->first();
    }
}