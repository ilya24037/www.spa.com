<?php

namespace App\Domain\Analytics\Services\Reports;

use App\Domain\Analytics\Contracts\AnalyticsServiceInterface;
use Carbon\Carbon;

/**
 * Генератор периодических отчетов (день, неделя, месяц)
 */
class PeriodReportGenerator
{
    private AnalyticsServiceInterface $analyticsService;
    private MetricsCalculator $metricsCalculator;
    private StatsCollector $statsCollector;

    public function __construct(
        AnalyticsServiceInterface $analyticsService,
        MetricsCalculator $metricsCalculator,
        StatsCollector $statsCollector
    ) {
        $this->analyticsService = $analyticsService;
        $this->metricsCalculator = $metricsCalculator;
        $this->statsCollector = $statsCollector;
    }

    /**
     * Сгенерировать ежедневный отчет
     */
    public function generateDailyReport(?Carbon $date = null): array
    {
        $date = $date ?? now()->subDay();
        $from = $date->copy()->startOfDay();
        $to = $date->copy()->endOfDay();

        $pageViewStats = $this->analyticsService->getPageViewStats($from, $to);
        $userActionStats = $this->analyticsService->getUserActionStats($from, $to);
        $topPages = $this->analyticsService->getTopPages($from, $to, 5);

        return [
            'report_type' => 'daily',
            'date' => $date->format('Y-m-d'),
            'summary' => [
                'page_views' => $pageViewStats['totals']['total_views'],
                'unique_views' => $pageViewStats['totals']['unique_views'],
                'user_actions' => $userActionStats['totals']['total_actions'],
                'conversions' => $userActionStats['totals']['total_conversions'],
                'conversion_rate' => $userActionStats['totals']['conversion_rate'],
            ],
            'page_views' => $pageViewStats,
            'user_actions' => $userActionStats,
            'top_pages' => $topPages->toArray(),
            'generated_at' => now(),
        ];
    }

    /**
     * Сгенерировать еженедельный отчет
     */
    public function generateWeeklyReport(?Carbon $startOfWeek = null): array
    {
        $startOfWeek = $startOfWeek ?? now()->startOfWeek()->subWeek();
        $from = $startOfWeek->copy();
        $to = $startOfWeek->copy()->endOfWeek();

        $pageViewStats = $this->analyticsService->getPageViewStats($from, $to);
        $userActionStats = $this->analyticsService->getUserActionStats($from, $to);
        $conversionFunnel = $this->analyticsService->getConversionFunnel($from, $to);
        $deviceStats = $this->analyticsService->getDeviceStats($from, $to);
        $topPages = $this->analyticsService->getTopPages($from, $to, 10);

        // Сравнение с предыдущей неделей
        $comparison = $this->generateWeekComparison($from, $to, $pageViewStats);

        return [
            'report_type' => 'weekly',
            'week' => [
                'start' => $from->format('Y-m-d'),
                'end' => $to->format('Y-m-d'),
                'week_number' => $from->weekOfYear,
            ],
            'summary' => [
                'page_views' => $pageViewStats['totals']['total_views'],
                'unique_views' => $pageViewStats['totals']['unique_views'],
                'user_actions' => $userActionStats['totals']['total_actions'],
                'conversions' => $userActionStats['totals']['total_conversions'],
                'conversion_rate' => $userActionStats['totals']['conversion_rate'],
            ],
            'comparison_with_previous_week' => $comparison,
            'page_views' => $pageViewStats,
            'user_actions' => $userActionStats,
            'conversion_funnel' => $conversionFunnel,
            'device_stats' => $deviceStats,
            'top_pages' => $topPages->toArray(),
            'generated_at' => now(),
        ];
    }

    /**
     * Сгенерировать ежемесячный отчет
     */
    public function generateMonthlyReport(?Carbon $month = null): array
    {
        $month = $month ?? now()->subMonth();
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $pageViewStats = $this->analyticsService->getPageViewStats($from, $to);
        $userActionStats = $this->analyticsService->getUserActionStats($from, $to);
        $conversionFunnel = $this->analyticsService->getConversionFunnel($from, $to);
        $deviceStats = $this->analyticsService->getDeviceStats($from, $to);
        $topPages = $this->analyticsService->getTopPages($from, $to, 20);

        // Дополнительная аналитика для месячного отчета
        $userGrowth = $this->statsCollector->getUserGrowthStats($from, $to);
        $contentStats = $this->statsCollector->getContentStats($from, $to);
        $performanceMetrics = $this->analyticsService->getPerformanceMetrics($from, $to);

        return [
            'report_type' => 'monthly',
            'month' => [
                'start' => $from->format('Y-m-d'),
                'end' => $to->format('Y-m-d'),
                'month_name' => $from->format('F Y'),
            ],
            'summary' => [
                'page_views' => $pageViewStats['totals']['total_views'],
                'unique_views' => $pageViewStats['totals']['unique_views'],
                'user_actions' => $userActionStats['totals']['total_actions'],
                'conversions' => $userActionStats['totals']['total_conversions'],
                'conversion_rate' => $userActionStats['totals']['conversion_rate'],
                'conversion_value' => $userActionStats['totals']['conversion_value'],
            ],
            'page_views' => $pageViewStats,
            'user_actions' => $userActionStats,
            'conversion_funnel' => $conversionFunnel,
            'device_stats' => $deviceStats,
            'user_growth' => $userGrowth,
            'content_stats' => $contentStats,
            'performance_metrics' => $performanceMetrics,
            'top_pages' => $topPages->toArray(),
            'generated_at' => now(),
        ];
    }

    /**
     * Сгенерировать сравнение недель
     */
    private function generateWeekComparison(Carbon $from, Carbon $to, array $currentStats): array
    {
        $previousWeekFrom = $from->copy()->subWeek();
        $previousWeekTo = $to->copy()->subWeek();
        $previousWeekStats = $this->analyticsService->getPageViewStats($previousWeekFrom, $previousWeekTo);

        return [
            'page_views_change' => $this->metricsCalculator->calculatePercentageChange(
                $previousWeekStats['totals']['total_views'],
                $currentStats['totals']['total_views']
            ),
            'unique_views_change' => $this->metricsCalculator->calculatePercentageChange(
                $previousWeekStats['totals']['unique_views'],
                $currentStats['totals']['unique_views']
            ),
        ];
    }
}