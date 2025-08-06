<?php

namespace App\Domain\Search\Services\Handlers;

use App\Domain\Search\Enums\SearchType;
use App\Domain\Search\Repositories\SearchRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик аналитики и статистики поиска
 */
class SearchAnalytics
{
    public function __construct(
        private SearchRepository $repository
    ) {}

    /**
     * Логировать поисковый запрос
     */
    public function logSearchQuery(string $query, SearchType $type, array $filters, $sortBy): void
    {
        Log::info('Search query', [
            'query' => $query,
            'type' => $type->value,
            'filters' => $filters,
            'sort' => $sortBy->value ?? $sortBy,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Логировать результаты поиска
     */
    public function logSearchResults(string $query, SearchType $type, int $count): void
    {
        $this->repository->logSearchQuery($query, $type, $count, auth()->id());
        
        if ($count === 0) {
            Log::warning('Zero search results', [
                'query' => $query,
                'type' => $type->value,
                'user_id' => auth()->id(),
                'timestamp' => now(),
            ]);
        }
    }

    /**
     * Получить статистику поиска
     */
    public function getSearchStatistics(array $filters = []): array
    {
        $cacheKey = "search_statistics:" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 3600, function () use ($filters) {
            return [
                'total_searches' => $this->getTotalSearchCount($filters),
                'popular_queries' => $this->getTopQueries($filters),
                'search_types_distribution' => $this->getSearchTypesDistribution($filters),
                'conversion_rates' => $this->getConversionRates($filters),
                'average_results_per_search' => $this->getAverageResultsCount($filters),
                'zero_results_queries' => $this->getZeroResultsQueries($filters),
                'peak_hours' => $this->getPeakSearchHours($filters),
                'user_behavior' => $this->getUserBehaviorStats($filters),
            ];
        });
    }

    /**
     * Получить общее количество поисковых запросов
     */
    public function getTotalSearchCount(array $filters): int
    {
        return $this->repository->getTotalSearchCount($filters);
    }

    /**
     * Получить топ запросов
     */
    public function getTopQueries(array $filters): array
    {
        return $this->repository->getTopQueries($filters);
    }

    /**
     * Получить распределение типов поиска
     */
    public function getSearchTypesDistribution(array $filters): array
    {
        return $this->repository->getSearchTypesDistribution($filters);
    }

    /**
     * Получить коэффициенты конверсии
     */
    public function getConversionRates(array $filters): array
    {
        return $this->repository->getConversionRates($filters);
    }

    /**
     * Получить среднее количество результатов
     */
    public function getAverageResultsCount(array $filters): float
    {
        return $this->repository->getAverageResultsCount($filters);
    }

    /**
     * Получить запросы без результатов
     */
    public function getZeroResultsQueries(array $filters): array
    {
        return $this->repository->getZeroResultsQueries($filters);
    }

    /**
     * Получить пиковые часы поиска
     */
    public function getPeakSearchHours(array $filters): array
    {
        return $this->repository->getPeakSearchHours($filters);
    }

    /**
     * Получить статистику поведения пользователей
     */
    public function getUserBehaviorStats(array $filters): array
    {
        return [
            'bounce_rate' => $this->repository->getSearchBounceRate($filters),
            'average_session_duration' => $this->repository->getAverageSessionDuration($filters),
            'pages_per_session' => $this->repository->getPagesPerSession($filters),
            'return_visitors' => $this->repository->getReturnVisitorsCount($filters),
        ];
    }

    /**
     * Трекинг кликов по результатам поиска
     */
    public function trackSearchClick(string $query, SearchType $type, int $position, int $itemId): void
    {
        $this->repository->logSearchClick([
            'query' => $query,
            'search_type' => $type->value,
            'position' => $position,
            'item_id' => $itemId,
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'clicked_at' => now(),
        ]);

        Log::info('Search result clicked', [
            'query' => $query,
            'type' => $type->value,
            'position' => $position,
            'item_id' => $itemId,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Трекинг конверсий из поиска
     */
    public function trackSearchConversion(string $query, SearchType $type, int $itemId, float $value = 0): void
    {
        $this->repository->logSearchConversion([
            'query' => $query,
            'search_type' => $type->value,
            'item_id' => $itemId,
            'conversion_value' => $value,
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'converted_at' => now(),
        ]);

        Log::info('Search conversion', [
            'query' => $query,
            'type' => $type->value,
            'item_id' => $itemId,
            'value' => $value,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Анализ эффективности поиска
     */
    public function analyzeSearchEffectiveness(string $period = '30 days'): array
    {
        $filters = ['period' => $period];
        
        return [
            'total_searches' => $this->getTotalSearchCount($filters),
            'successful_searches' => $this->getSuccessfulSearchesCount($filters),
            'success_rate' => $this->calculateSearchSuccessRate($filters),
            'average_click_position' => $this->getAverageClickPosition($filters),
            'query_refinement_rate' => $this->getQueryRefinementRate($filters),
            'abandonment_rate' => $this->getSearchAbandonmentRate($filters),
        ];
    }

    /**
     * Получить количество успешных поисков
     */
    private function getSuccessfulSearchesCount(array $filters): int
    {
        return $this->repository->getSuccessfulSearchesCount($filters);
    }

    /**
     * Рассчитать коэффициент успешности поиска
     */
    private function calculateSearchSuccessRate(array $filters): float
    {
        $total = $this->getTotalSearchCount($filters);
        $successful = $this->getSuccessfulSearchesCount($filters);
        
        return $total > 0 ? round(($successful / $total) * 100, 2) : 0;
    }

    /**
     * Получить среднюю позицию клика
     */
    private function getAverageClickPosition(array $filters): float
    {
        return $this->repository->getAverageClickPosition($filters);
    }

    /**
     * Получить коэффициент уточнения запросов
     */
    private function getQueryRefinementRate(array $filters): float
    {
        return $this->repository->getQueryRefinementRate($filters);
    }

    /**
     * Получить коэффициент отказов от поиска
     */
    private function getSearchAbandonmentRate(array $filters): float
    {
        return $this->repository->getSearchAbandonmentRate($filters);
    }

    /**
     * Генерировать отчет по поисковым трендам
     */
    public function generateSearchTrendsReport(string $period = '7 days'): array
    {
        return [
            'trending_queries' => $this->getTrendingQueries($period),
            'declining_queries' => $this->getDecliningQueries($period),
            'new_queries' => $this->getNewQueries($period),
            'seasonal_patterns' => $this->getSeasonalPatterns($period),
        ];
    }

    /**
     * Получить трендовые запросы
     */
    private function getTrendingQueries(string $period): array
    {
        return $this->repository->getTrendingQueries($period);
    }

    /**
     * Получить убывающие запросы
     */
    private function getDecliningQueries(string $period): array
    {
        return $this->repository->getDecliningQueries($period);
    }

    /**
     * Получить новые запросы
     */
    private function getNewQueries(string $period): array
    {
        return $this->repository->getNewQueries($period);
    }

    /**
     * Получить сезонные паттерны
     */
    private function getSeasonalPatterns(string $period): array
    {
        return $this->repository->getSeasonalPatterns($period);
    }
}