<?php

namespace App\Domain\Analytics\Services\Reports;

use App\Domain\Analytics\Models\PageView;
use App\Domain\Analytics\Models\UserAction;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Ad\Models\Ad;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Генератор специализированных отчетов (мастера, кампании, поведение)
 */
class SpecializedReportGenerator
{
    private CampaignAnalyzer $campaignAnalyzer;
    private UserBehaviorAnalyzer $behaviorAnalyzer;
    private MetricsCalculator $metricsCalculator;

    public function __construct(
        CampaignAnalyzer $campaignAnalyzer,
        UserBehaviorAnalyzer $behaviorAnalyzer,
        MetricsCalculator $metricsCalculator
    ) {
        $this->campaignAnalyzer = $campaignAnalyzer;
        $this->behaviorAnalyzer = $behaviorAnalyzer;
        $this->metricsCalculator = $metricsCalculator;
    }

    /**
     * Отчет по конкретному мастеру
     */
    public function generateMasterReport(int $masterProfileId, Carbon $from, Carbon $to): array
    {
        $masterProfile = MasterProfile::findOrFail($masterProfileId);

        // Просмотры профиля мастера
        $profileViews = PageView::query()
            ->where('viewable_type', MasterProfile::class)
            ->where('viewable_id', $masterProfileId)
            ->inPeriod($from, $to)
            ->get();

        // Действия связанные с мастером
        $masterActions = UserAction::query()
            ->where('actionable_type', MasterProfile::class)
            ->where('actionable_id', $masterProfileId)
            ->inPeriod($from, $to)
            ->get()
            ->groupBy('action_type')
            ->map->count();

        // Статистика по объявлениям мастера
        $adViews = $this->getMasterAdViews($masterProfile, $from, $to);

        // Конверсии
        $conversions = UserAction::query()
            ->where('actionable_type', MasterProfile::class)
            ->where('actionable_id', $masterProfileId)
            ->inPeriod($from, $to)
            ->conversions()
            ->get();

        return [
            'report_type' => 'master',
            'master' => [
                'id' => $masterProfile->id,
                'display_name' => $masterProfile->display_name,
                'rating' => $masterProfile->rating,
                'reviews_count' => $masterProfile->reviews_count,
            ],
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'summary' => [
                'profile_views' => $profileViews->count(),
                'unique_profile_views' => $profileViews->unique('ip_address')->count(),
                'ad_views' => $adViews,
                'total_actions' => $masterActions->sum(),
                'conversions' => $conversions->count(),
                'conversion_value' => $conversions->sum('conversion_value'),
            ],
            'actions_by_type' => $masterActions->toArray(),
            'daily_views' => $this->metricsCalculator->getDailyStats($profileViews, $from, $to),
            'device_breakdown' => $profileViews->groupBy('device_type')->map->count(),
            'country_breakdown' => $profileViews->whereNotNull('country')->groupBy('country')->map->count(),
            'conversion_details' => $conversions->map(function ($conversion) {
                return [
                    'action_type' => $conversion->action_type,
                    'value' => $conversion->conversion_value,
                    'performed_at' => $conversion->performed_at->format('Y-m-d H:i:s'),
                ];
            }),
            'generated_at' => now(),
        ];
    }

    /**
     * Отчет по рекламным кампаниям (UTM метки)
     */
    public function generateCampaignReport(Carbon $from, Carbon $to): array
    {
        $campaignData = $this->campaignAnalyzer->analyzeCampaigns($from, $to);

        return [
            'report_type' => 'campaign',
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'campaigns' => $campaignData['campaigns'],
            'total_campaign_views' => $campaignData['total_views'],
            'top_sources' => $campaignData['top_sources'],
            'conversion_by_source' => $campaignData['conversions_by_source'],
            'generated_at' => now(),
        ];
    }

    /**
     * Отчет по поведению пользователей
     */
    public function generateUserBehaviorReport(Carbon $from, Carbon $to): array
    {
        $sessionMetrics = $this->behaviorAnalyzer->getSessionMetrics($from, $to);
        $userJourneys = $this->behaviorAnalyzer->analyzeUserJourneys($from, $to);
        $popularPaths = $this->behaviorAnalyzer->getPopularPaths($from, $to);
        $exitPages = $this->behaviorAnalyzer->getExitPages($from, $to);

        return [
            'report_type' => 'user_behavior',
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'session_metrics' => $sessionMetrics,
            'user_journeys' => $userJourneys,
            'popular_paths' => $popularPaths,
            'exit_pages' => $exitPages,
            'bounce_rate' => $this->behaviorAnalyzer->calculateBounceRate($from, $to),
            'pages_per_session' => $this->behaviorAnalyzer->calculatePagesPerSession($from, $to),
            'generated_at' => now(),
        ];
    }

    /**
     * Получить просмотры объявлений мастера
     */
    private function getMasterAdViews(MasterProfile $masterProfile, Carbon $from, Carbon $to): int
    {
        $masterAds = Ad::where('user_id', $masterProfile->user_id)->pluck('id');
        
        return PageView::query()
            ->where('viewable_type', Ad::class)
            ->whereIn('viewable_id', $masterAds)
            ->inPeriod($from, $to)
            ->count();
    }

    /**
     * Отчет по эффективности контента
     */
    public function generateContentPerformanceReport(Carbon $from, Carbon $to): array
    {
        $topPerformingAds = $this->getTopPerformingAds($from, $to);
        $masterPerformance = $this->getMasterPerformanceStats($from, $to);
        $contentEngagement = $this->getContentEngagementMetrics($from, $to);

        return [
            'report_type' => 'content_performance',
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'top_performing_ads' => $topPerformingAds,
            'master_performance' => $masterPerformance,
            'content_engagement' => $contentEngagement,
            'generated_at' => now(),
        ];
    }

    /**
     * Получить топ объявления по эффективности
     */
    private function getTopPerformingAds(Carbon $from, Carbon $to, int $limit = 20): array
    {
        return PageView::query()
            ->where('viewable_type', Ad::class)
            ->inPeriod($from, $to)
            ->with('viewable')
            ->selectRaw('viewable_id, COUNT(*) as views, COUNT(DISTINCT ip_address) as unique_views')
            ->groupBy('viewable_id')
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'ad_id' => $item->viewable_id,
                    'ad_title' => $item->viewable->title ?? 'N/A',
                    'views' => $item->views,
                    'unique_views' => $item->unique_views,
                    'engagement_rate' => $item->views > 0 ? round(($item->unique_views / $item->views) * 100, 2) : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Получить статистику эффективности мастеров
     */
    private function getMasterPerformanceStats(Carbon $from, Carbon $to): array
    {
        return PageView::query()
            ->where('viewable_type', MasterProfile::class)
            ->inPeriod($from, $to)
            ->with('viewable')
            ->selectRaw('viewable_id, COUNT(*) as views, COUNT(DISTINCT ip_address) as unique_views')
            ->groupBy('viewable_id')
            ->having('views', '>=', 10) // Минимум просмотров для включения в статистику
            ->orderBy('unique_views', 'desc')
            ->limit(15)
            ->get()
            ->map(function ($item) {
                return [
                    'master_id' => $item->viewable_id,
                    'display_name' => $item->viewable->display_name ?? 'N/A',
                    'views' => $item->views,
                    'unique_views' => $item->unique_views,
                    'rating' => $item->viewable->rating ?? 0,
                ];
            })
            ->toArray();
    }

    /**
     * Получить метрики вовлеченности контента
     */
    private function getContentEngagementMetrics(Carbon $from, Carbon $to): array
    {
        $totalViews = PageView::inPeriod($from, $to)->count();
        $totalActions = UserAction::inPeriod($from, $to)->count();
        $avgSessionDuration = PageView::inPeriod($from, $to)
            ->where('duration_seconds', '>', 0)
            ->avg('duration_seconds');

        return [
            'total_content_views' => $totalViews,
            'total_user_actions' => $totalActions,
            'action_per_view_ratio' => $totalViews > 0 ? round(($totalActions / $totalViews) * 100, 2) : 0,
            'average_session_duration' => round($avgSessionDuration ?? 0, 2),
            'engagement_score' => $this->calculateEngagementScore($totalViews, $totalActions, $avgSessionDuration),
        ];
    }

    /**
     * Рассчитать общий скор вовлеченности
     */
    private function calculateEngagementScore(int $views, int $actions, ?float $avgDuration): float
    {
        if ($views === 0) return 0;

        $actionRatio = ($actions / $views) * 100;
        $durationScore = min(($avgDuration ?? 0) / 60, 10); // Максимум 10 баллов за время
        
        return round(($actionRatio + $durationScore) / 2, 2);
    }
}