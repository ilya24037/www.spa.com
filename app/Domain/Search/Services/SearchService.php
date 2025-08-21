<?php

namespace App\Domain\Search\Services;

use App\Domain\Search\Enums\SearchType;
use App\Domain\Search\Enums\SortBy;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Основной сервис поиска (обновленный после рефакторинга)
 * Интегрирован с новыми консолидированными сервисами
 * 
 * Принцип KISS: минимум зависимостей, максимум функциональности
 */
class SearchService
{
    public function __construct(
        private SearchAnalyticsService $analyticsService,
        private DatabaseSearchEngine $databaseEngine,
        private ElasticsearchEngine $elasticsearchEngine,
        private RecommendationEngine $recommendationEngine
    ) {}

    /**
     * Выполнить поиск
     */
    public function search(
        string $query,
        SearchType $type = SearchType::ADS,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20,
        ?array $location = null
    ): LengthAwarePaginator {
        
        // Валидация через новый аналитический сервис
        $this->analyticsService->validateSearchParams($query, $type, $filters, $sortBy, $page, $perPage);

        // Логирование поискового запроса
        $this->analyticsService->logSearchQuery($query, $type, $filters, $sortBy, auth()->id());

        try {
            // Получаем подходящий движок
            $engine = $this->getEngine($type);
            
            // Выполняем поиск через движок
            $results = $engine->search($query, $filters, $sortBy, $page, $perPage, $location);
            
            // Обогащаем результаты
            $enrichedResults = $this->enrichResults($results, $engine);
            
            // Логируем результаты
            $this->analyticsService->logSearchResults($query, $type, $enrichedResults->total());
            
            return $enrichedResults;
            
        } catch (\Exception $e) {
            Log::error('Search error', [
                'query' => $query,
                'type' => $type->value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Возвращаем пустые результаты при ошибке
            return $this->getEmptyResults($page, $perPage);
        }
    }

    /**
     * Быстрый поиск (упрощенная версия для автодополнения)
     */
    public function quickSearch(
        string $query,
        SearchType $type = SearchType::ADS,
        int $limit = 5
    ): array {
        
        if (mb_strlen($query) < 2) { // Упрощено, убрали вызов $type->getMinQueryLength()
            return [];
        }

        $cacheKey = "quick_search:{$type->value}:" . md5($query) . ":{$limit}";
        
        return Cache::remember($cacheKey, 300, function () use ($query, $type, $limit) {
            $engine = $this->getEngine($type);
            return $engine->quickSearch($query, $limit);
        });
    }

    /**
     * Получить автодополнение
     */
    public function getAutocomplete(
        string $query,
        SearchType $type = SearchType::ADS,
        int $limit = 10
    ): array {
        return $this->analyticsService->getAutocomplete($query, $type, $limit);
    }

    /**
     * Получить популярные запросы
     */
    public function getPopularQueries(SearchType $type = SearchType::ADS, int $limit = 10): array
    {
        return $this->analyticsService->getPopularQueries($type, $limit);
    }

    /**
     * Получить связанные запросы
     */
    public function getRelatedQueries(string $query, SearchType $type = SearchType::ADS): array
    {
        return $this->analyticsService->getRelatedQueries($query, $type);
    }

    /**
     * Поисковые подсказки
     */
    public function getSearchSuggestions(
        string $query,
        SearchType $type = SearchType::ADS,
        array $context = []
    ): array {
        return $this->analyticsService->getSearchSuggestions($query, $type, $context);
    }

    /**
     * Поиск похожих объектов
     */
    public function findSimilar(
        int $objectId,
        SearchType $type,
        int $limit = 10,
        array $excludeIds = []
    ): array {
        $engine = $this->getEngine($type);
        return $engine->findSimilar($objectId, $limit, $excludeIds);
    }

    /**
     * Продвинутый поиск с множественными критериями
     */
    public function advancedSearch(array $criteria): LengthAwarePaginator
    {
        $this->analyticsService->validateAdvancedCriteria($criteria);
        
        $type = SearchType::tryFrom($criteria['type'] ?? 'ads') ?? SearchType::ADS;
        $engine = $this->getEngine($type);
        
        return $engine->advancedSearch($criteria);
    }

    /**
     * Фасетный поиск (поиск по категориям с подсчетом)
     */
    public function facetedSearch(
        string $query,
        SearchType $type,
        array $facets = []
    ): array {
        $engine = $this->getEngine($type);
        return $engine->facetedSearch($query, $facets);
    }

    /**
     * Геопоиск
     */
    public function geoSearch(
        array $location,
        float $radius,
        SearchType $type = SearchType::ADS,
        array $filters = [],
        int $limit = 20
    ): array {
        $this->analyticsService->validateGeoSearch($location, $radius, $type);
        
        $engine = $this->getEngine($type);
        return $engine->geoSearch($location, $radius, $filters, $limit);
    }

    /**
     * Поиск с использованием AI/ML
     */
    public function intelligentSearch(
        string $query,
        SearchType $type,
        ?int $userId = null,
        array $context = []
    ): LengthAwarePaginator {
        $cacheKey = "intelligent_search:" . md5($query . $type->value . ($userId ?? '') . serialize($context));
        
        return Cache::remember($cacheKey, 900, function () use ($query, $type, $userId, $context) {
            $engine = $this->getEngine($type);
            
            // Используем ML для улучшения релевантности
            if (method_exists($engine, 'intelligentSearch')) {
                return $engine->intelligentSearch($query, $userId, $context);
            }
            
            // Fallback на обычный поиск
            return $this->search($query, $type);
        });
    }

    /**
     * Экспорт результатов поиска
     */
    public function exportResults(
        string $query,
        SearchType $type,
        array $filters = [],
        string $format = 'csv',
        int $limit = 1000
    ): string {
        $results = $this->search($query, $type, $filters, SortBy::RELEVANCE, 1, $limit);
        $engine = $this->getEngine($type);
        
        return $engine->exportResults($results, $format);
    }

    /**
     * Получить статистику поиска
     */
    public function getSearchStatistics(array $filters = []): array
    {
        return $this->analyticsService->getSearchStatistics($filters);
    }

    /**
     * Трекинг взаимодействий с результатами поиска
     */
    public function trackSearchClick(string $query, SearchType $type, int $position, int $itemId): void
    {
        $this->analyticsService->trackSearchClick($query, $type, $position, $itemId);
    }

    public function trackSearchConversion(string $query, SearchType $type, int $itemId, float $value = 0): void
    {
        $this->analyticsService->trackSearchConversion($query, $type, $itemId, $value);
    }

    // === МЕТОДЫ УПРАВЛЕНИЯ ===

    /**
     * Очистить кэш поиска
     */
    public function clearSearchCache(?SearchType $type = null): void
    {
        if ($type) {
            Cache::tags(["search", $type->value])->flush();
        } else {
            Cache::tags("search")->flush();
        }
    }

    /**
     * Прогреть кэш популярными запросами
     */
    public function warmupCache(): void
    {
        foreach (SearchType::cases() as $type) {
            $popularQueries = $this->analyticsService->getPopularQueries($type, 20);
            
            foreach ($popularQueries as $query) {
                try {
                    $this->search($query, $type, [], SortBy::RELEVANCE, 1, 20);
                } catch (\Exception $e) {
                    Log::warning('Cache warmup failed', [
                        'query' => $query,
                        'type' => $type->value,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Проверить работоспособность поисковой системы
     */
    public function healthCheck(): array
    {
        return [
            'engines' => $this->checkEnginesHealth(),
            'cache_status' => $this->checkCacheStatus(),
            'search_test' => $this->performSearchTest(),
        ];
    }

    // === ПРИВАТНЫЕ МЕТОДЫ ===

    /**
     * Получить движок для типа поиска (встроенный менеджер)
     */
    private function getEngine(SearchType $type): SearchEngineInterface
    {
        switch ($type) {
            case SearchType::ADS:
            case SearchType::MASTERS:
            case SearchType::SERVICES:
                // Используем DatabaseEngine для всех основных типов
                return $this->databaseEngine;
                
            case SearchType::RECOMMENDATIONS:
                return $this->recommendationEngine;
                
            case SearchType::GLOBAL:
            default:
                // Проверяем доступность Elasticsearch
                if ($this->isElasticsearchAvailable()) {
                    return $this->elasticsearchEngine;
                }
                
                // Fallback на DatabaseEngine
                return $this->databaseEngine;
        }
    }

    /**
     * Проверить доступность Elasticsearch
     */
    private function isElasticsearchAvailable(): bool
    {
        try {
            $health = $this->elasticsearchEngine->healthCheck();
            return $health['status'] === 'healthy';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Обогащение результатов поиска
     */
    private function enrichResults(LengthAwarePaginator $results, SearchEngineInterface $engine): LengthAwarePaginator
    {        
        if (method_exists($engine, 'enrichResults')) {
            return $engine->enrichResults($results);
        }
        
        return $results;
    }

    /**
     * Получить пустые результаты
     */
    private function getEmptyResults(int $page, int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            collect([]),
            0,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Проверить работоспособность всех движков
     */
    private function checkEnginesHealth(): array
    {
        return [
            'database' => $this->databaseEngine->healthCheck(),
            'elasticsearch' => $this->elasticsearchEngine->healthCheck(),
            'recommendation' => $this->recommendationEngine->healthCheck()
        ];
    }

    /**
     * Проверить статус кеша
     */
    private function checkCacheStatus(): string
    {
        try {
            Cache::put('health_check', 'ok', 60);
            $value = Cache::get('health_check');
            return $value === 'ok' ? 'healthy' : 'unhealthy';
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }

    /**
     * Выполнить тестовый поиск
     */
    private function performSearchTest(): string
    {
        try {
            $results = $this->quickSearch('test', SearchType::ADS, 1);
            return 'success';
        } catch (\Exception $e) {
            return 'error: ' . $e->getMessage();
        }
    }
}