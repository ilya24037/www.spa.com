<?php

namespace App\Domain\Analytics\Services;

use App\Domain\Analytics\Contracts\ReportServiceInterface;
use App\Domain\Analytics\Contracts\AnalyticsServiceInterface;
use App\Domain\Analytics\Services\Reports\PeriodReportGenerator;
use App\Domain\Analytics\Services\Reports\SpecializedReportGenerator;
use App\Domain\Analytics\Services\Reports\MetricsCalculator;
use App\Domain\Analytics\Services\Reports\StatsCollector;
use Carbon\Carbon;

/**
 * Упрощенный сервис для генерации аналитических отчетов
 * Делегирует работу специализированным генераторам отчетов
 */
class ReportService implements ReportServiceInterface
{
    private AnalyticsServiceInterface $analyticsService;
    private PeriodReportGenerator $periodReportGenerator;
    private SpecializedReportGenerator $specializedReportGenerator;
    private MetricsCalculator $metricsCalculator;
    private StatsCollector $statsCollector;

    public function __construct(
        AnalyticsServiceInterface $analyticsService,
        PeriodReportGenerator $periodReportGenerator,
        SpecializedReportGenerator $specializedReportGenerator,
        MetricsCalculator $metricsCalculator,
        StatsCollector $statsCollector
    ) {
        $this->analyticsService = $analyticsService;
        $this->periodReportGenerator = $periodReportGenerator;
        $this->specializedReportGenerator = $specializedReportGenerator;
        $this->metricsCalculator = $metricsCalculator;
        $this->statsCollector = $statsCollector;
    }

    // === ПЕРИОДИЧЕСКИЕ ОТЧЕТЫ ===

    /**
     * Сгенерировать ежедневный отчет
     */
    public function generateDailyReport(?Carbon $date = null): array
    {
        return $this->periodReportGenerator->generateDailyReport($date);
    }

    /**
     * Сгенерировать еженедельный отчет
     */
    public function generateWeeklyReport(?Carbon $startOfWeek = null): array
    {
        return $this->periodReportGenerator->generateWeeklyReport($startOfWeek);
    }

    /**
     * Сгенерировать ежемесячный отчет
     */
    public function generateMonthlyReport(?Carbon $month = null): array
    {
        return $this->periodReportGenerator->generateMonthlyReport($month);
    }

    // === СПЕЦИАЛИЗИРОВАННЫЕ ОТЧЕТЫ ===

    /**
     * Отчет по конкретному мастеру
     */
    public function generateMasterReport(int $masterProfileId, Carbon $from, Carbon $to): array
    {
        return $this->specializedReportGenerator->generateMasterReport($masterProfileId, $from, $to);
    }

    /**
     * Отчет по рекламным кампаниям (UTM метки)
     */
    public function generateCampaignReport(Carbon $from, Carbon $to): array
    {
        return $this->specializedReportGenerator->generateCampaignReport($from, $to);
    }

    /**
     * Отчет по поведению пользователей
     */
    public function generateUserBehaviorReport(Carbon $from, Carbon $to): array
    {
        return $this->specializedReportGenerator->generateUserBehaviorReport($from, $to);
    }

    /**
     * Отчет по эффективности контента
     */
    public function generateContentPerformanceReport(Carbon $from, Carbon $to): array
    {
        return $this->specializedReportGenerator->generateContentPerformanceReport($from, $to);
    }

    // === ДОПОЛНИТЕЛЬНЫЕ ОТЧЕТЫ ===

    /**
     * Сгенерировать пользовательский отчет с выбранными метриками
     */
    public function generateCustomReport(Carbon $from, Carbon $to, array $metrics = []): array
    {
        $report = [
            'report_type' => 'custom',
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'generated_at' => now(),
        ];

        // Добавляем запрошенные метрики
        if (in_array('page_views', $metrics)) {
            $report['page_views'] = $this->analyticsService->getPageViewStats($from, $to);
        }

        if (in_array('user_actions', $metrics)) {
            $report['user_actions'] = $this->analyticsService->getUserActionStats($from, $to);
        }

        if (in_array('user_growth', $metrics)) {
            $report['user_growth'] = $this->statsCollector->getUserGrowthStats($from, $to);
        }

        if (in_array('content_stats', $metrics)) {
            $report['content_stats'] = $this->statsCollector->getContentStats($from, $to);
        }

        if (in_array('traffic_sources', $metrics)) {
            $report['traffic_sources'] = $this->statsCollector->getTrafficSourceStats($from, $to);
        }

        if (in_array('geographic', $metrics)) {
            $report['geographic'] = $this->statsCollector->getGeographicStats($from, $to);
        }

        if (in_array('device_browser', $metrics)) {
            $report['device_browser'] = $this->statsCollector->getDeviceBrowserStats($from, $to);
        }

        return $report;
    }

    /**
     * Сгенерировать сравнительный отчет между двумя периодами
     */
    public function generateComparisonReport(Carbon $period1From, Carbon $period1To, Carbon $period2From, Carbon $period2To): array
    {
        $period1Stats = $this->analyticsService->getPageViewStats($period1From, $period1To);
        $period2Stats = $this->analyticsService->getPageViewStats($period2From, $period2To);

        $comparison = [
            'page_views_change' => $this->metricsCalculator->calculatePercentageChange(
                $period1Stats['totals']['total_views'],
                $period2Stats['totals']['total_views']
            ),
            'unique_views_change' => $this->metricsCalculator->calculatePercentageChange(
                $period1Stats['totals']['unique_views'],
                $period2Stats['totals']['unique_views']
            ),
        ];

        return [
            'report_type' => 'comparison',
            'period_1' => [
                'from' => $period1From->format('Y-m-d'),
                'to' => $period1To->format('Y-m-d'),
                'stats' => $period1Stats,
            ],
            'period_2' => [
                'from' => $period2From->format('Y-m-d'),
                'to' => $period2To->format('Y-m-d'),
                'stats' => $period2Stats,
            ],
            'comparison' => $comparison,
            'generated_at' => now(),
        ];
    }

    /**
     * Получить итоговые метрики для дашборда
     */
    public function getDashboardSummary(Carbon $from, Carbon $to): array
    {
        $pageViewStats = $this->analyticsService->getPageViewStats($from, $to);
        $userActionStats = $this->analyticsService->getUserActionStats($from, $to);
        $userGrowth = $this->statsCollector->getUserGrowthStats($from, $to);
        $contentStats = $this->statsCollector->getContentStats($from, $to);

        return [
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'key_metrics' => [
                'total_page_views' => $pageViewStats['totals']['total_views'],
                'unique_visitors' => $pageViewStats['totals']['unique_views'],
                'total_actions' => $userActionStats['totals']['total_actions'],
                'conversions' => $userActionStats['totals']['total_conversions'],
                'conversion_rate' => $userActionStats['totals']['conversion_rate'],
                'new_users' => $userGrowth['new_users'],
                'new_content' => $contentStats['new_ads'],
            ],
            'trends' => [
                'user_growth_rate' => $userGrowth['growth_rate'],
                'content_engagement' => $contentStats['views_per_ad'],
            ],
            'generated_at' => now(),
        ];
    }

    // === УСТАРЕВШИЕ МЕТОДЫ ДЛЯ СОВМЕСТИМОСТИ ===

    /**
     * @deprecated Используйте metricsCalculator->calculatePercentageChange()
     */
    protected function calculatePercentageChange(float $oldValue, float $newValue): float
    {
        return $this->metricsCalculator->calculatePercentageChange($oldValue, $newValue);
    }

    /**
     * @deprecated Используйте statsCollector->getUserGrowthStats()
     */
    protected function getUserGrowthStats(Carbon $from, Carbon $to): array
    {
        return $this->statsCollector->getUserGrowthStats($from, $to);
    }

    /**
     * @deprecated Используйте statsCollector->getContentStats()
     */
    protected function getContentStats(Carbon $from, Carbon $to): array
    {
        return $this->statsCollector->getContentStats($from, $to);
    }

    /**
     * @deprecated Логика перенесена в SpecializedReportGenerator
     */
    protected function getDailyStats($items, Carbon $from, Carbon $to): array
    {
        return $this->metricsCalculator->getDailyStats($items, $from, $to);
    }

    /**
     * @deprecated Логика перенесена в CampaignAnalyzer
     */
    protected function extractUTMParams($pageView): ?array
    {
        // Заглушка для обратной совместимости
        return null;
    }

    /**
     * @deprecated Логика перенесена в UserBehaviorAnalyzer
     */
    protected function analyzeUserJourneys(Carbon $from, Carbon $to): array
    {
        // Заглушка для обратной совместимости
        return [];
    }
}