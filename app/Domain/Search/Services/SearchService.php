<?php

namespace App\Domain\Search\Services;

use App\Domain\Search\Enums\SearchType;
use App\Domain\Search\Enums\SortBy;
use App\Domain\Search\Repositories\SearchRepository;
use App\Domain\Search\Engines\AdSearchEngine;
use App\Domain\Search\Engines\MasterSearchEngine;
use App\Domain\Search\Services\ServiceSearchEngine;
use App\Domain\Search\Services\GlobalSearchEngine;
use App\Domain\Search\Services\RecommendationEngine;
use App\Domain\Search\Services\SearchEngineInterface;
use App\Domain\Search\Services\ElasticsearchSearchEngine;
use App\Infrastructure\Search\ElasticsearchClient;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Сервис поиска с различными движками
 */
class SearchService
{
    protected array $engines = [];

    public function __construct(
        protected SearchRepository $repository,
        protected AdSearchEngine $adEngine,
        protected MasterSearchEngine $masterEngine,
        protected ServiceSearchEngine $serviceEngine,
        protected GlobalSearchEngine $globalEngine,
        protected RecommendationEngine $recommendationEngine,
        protected ?ElasticsearchClient $elasticsearchClient = null
    ) {
        $this->initializeEngines();
    }

    /**
     * Инициализация движков поиска
     */
    protected function initializeEngines(): void
    {
        // Проверяем, включен ли Elasticsearch
        $useElasticsearch = config('elasticsearch.enabled', false) && $this->elasticsearchClient !== null;
        
        if ($useElasticsearch) {
            // Используем Elasticsearch для основных типов поиска
            $this->engines = [
                SearchType::ADS->value => new ElasticsearchSearchEngine($this->elasticsearchClient, SearchType::ADS),
                SearchType::MASTERS->value => new ElasticsearchSearchEngine($this->elasticsearchClient, SearchType::MASTERS),
                SearchType::SERVICES->value => $this->serviceEngine, // Пока используем SQL
                SearchType::GLOBAL->value => $this->globalEngine,
                SearchType::RECOMMENDATIONS->value => $this->recommendationEngine,
            ];
        } else {
            // Fallback на SQL движки
            $this->engines = [
                SearchType::ADS->value => $this->adEngine,
                SearchType::MASTERS->value => $this->masterEngine,
                SearchType::SERVICES->value => $this->serviceEngine,
                SearchType::GLOBAL->value => $this->globalEngine,
                SearchType::RECOMMENDATIONS->value => $this->recommendationEngine,
            ];
        }
    }

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
        
        // Валидация
        $this->validateSearchParams($query, $type, $filters, $sortBy, $page, $perPage);

        // Логирование поискового запроса
        $this->logSearchQuery($query, $type, $filters, $sortBy);

        try {
            // Получаем подходящий движок
            $engine = $this->getEngine($type);
            
            // Выполняем поиск через движок
            $results = $engine->search($query, $filters, $sortBy, $page, $perPage, $location);
            
            // Обогащаем результаты
            $enrichedResults = $this->enrichResults($results, $type);
            
            // Логируем результаты
            $this->logSearchResults($query, $type, $enrichedResults->total());
            
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
        
        if (mb_strlen($query) < $type->getMinQueryLength()) {
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
        
        return $this->repository->getAutocomplete($query, $type, $limit);
    }

    /**
     * Получить популярные запросы
     */
    public function getPopularQueries(SearchType $type = SearchType::ADS, int $limit = 10): array
    {
        return $this->repository->getPopularQueries($type, $limit);
    }

    /**
     * Получить связанные запросы
     */
    public function getRelatedQueries(string $query, SearchType $type = SearchType::ADS): array
    {
        $cacheKey = "related_queries:{$type->value}:" . md5($query);
        
        return Cache::remember($cacheKey, 1800, function () use ($query, $type) {
            $engine = $this->getEngine($type);
            return $engine->getRelatedQueries($query);
        });
    }

    /**
     * Поисковые подсказки
     */
    public function getSearchSuggestions(
        string $query,
        SearchType $type = SearchType::ADS,
        array $context = []
    ): array {
        
        $suggestions = [];

        // Автодополнение
        $autocomplete = $this->getAutocomplete($query, $type, 5);
        $suggestions['autocomplete'] = $autocomplete;

        // Связанные запросы
        if (mb_strlen($query) >= 3) {
            $suggestions['related'] = $this->getRelatedQueries($query, $type);
        }

        // Популярные запросы в категории
        $suggestions['popular'] = $this->getPopularQueries($type, 5);

        // Исправления опечаток
        $suggestions['corrections'] = $this->getSpellingSuggestions($query);

        return $suggestions;
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
        
        if (!$type->supportsFacetedSearch()) {
            return [];
        }
        
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
        
        if (!$type->supportsGeoSearch()) {
            return [];
        }
        
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
        $cacheKey = "search_statistics:" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 3600, function () use ($filters) {
            return [
                'total_searches' => $this->getTotalSearchCount($filters),
                'popular_queries' => $this->getTopQueries($filters),
                'search_types_distribution' => $this->getSearchTypesDistribution($filters),
                'conversion_rates' => $this->getConversionRates($filters),
                'average_results_per_search' => $this->getAverageResultsCount($filters),
                'zero_results_queries' => $this->getZeroResultsQueries($filters),
            ];
        });
    }

    /**
     * Получить движок для типа поиска
     */
    protected function getEngine(SearchType $type): SearchEngineInterface
    {
        if (!isset($this->engines[$type->value])) {
            throw new \InvalidArgumentException("Движок для типа поиска {$type->value} не найден");
        }
        
        return $this->engines[$type->value];
    }

    /**
     * Валидация параметров поиска
     */
    protected function validateSearchParams(
        string $query,
        SearchType $type,
        array $filters,
        SortBy $sortBy,
        int $page,
        int $perPage
    ): void {
        
        if (mb_strlen($query) < $type->getMinQueryLength()) {
            throw new \InvalidArgumentException(
                "Минимальная длина запроса для типа {$type->value}: {$type->getMinQueryLength()}"
            );
        }
        
        if (!$sortBy->isApplicableForSearchType($type)) {
            throw new \InvalidArgumentException(
                "Сортировка {$sortBy->value} не применима для типа поиска {$type->value}"
            );
        }
        
        if ($page < 1) {
            throw new \InvalidArgumentException("Номер страницы должен быть больше 0");
        }
        
        if ($perPage < 1 || $perPage > 100) {
            throw new \InvalidArgumentException("Количество элементов на странице должно быть от 1 до 100");
        }
        
        if ($type->requiresAuth() && !auth()->check()) {
            throw new \UnauthorizedException("Тип поиска {$type->value} требует авторизации");
        }
    }

    /**
     * Обогащение результатов поиска
     */
    protected function enrichResults(LengthAwarePaginator $results, SearchType $type): LengthAwarePaginator
    {
        $engine = $this->getEngine($type);
        
        if (method_exists($engine, 'enrichResults')) {
            return $engine->enrichResults($results);
        }
        
        return $results;
    }

    /**
     * Получить пустые результаты
     */
    protected function getEmptyResults(int $page, int $perPage): LengthAwarePaginator
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
     * Логирование поискового запроса
     */
    protected function logSearchQuery(string $query, SearchType $type, array $filters, SortBy $sortBy): void
    {
        Log::info('Search query', [
            'query' => $query,
            'type' => $type->value,
            'filters' => $filters,
            'sort' => $sortBy->value,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Логирование результатов поиска
     */
    protected function logSearchResults(string $query, SearchType $type, int $count): void
    {
        $this->repository->logSearchQuery($query, $type, $count, auth()->id());
        
        if ($count === 0) {
            Log::warning('Zero search results', [
                'query' => $query,
                'type' => $type->value,
            ]);
        }
    }

    /**
     * Получить подсказки по исправлению опечаток
     */
    protected function getSpellingSuggestions(string $query): array
    {
        // Простая логика исправления опечаток
        // В реальном проекте можно использовать специализированные библиотеки
        
        $suggestions = [];
        $commonTypos = [
            'масаж' => 'массаж',
            'масажист' => 'массажист',
            'релакс' => 'релакс',
            'спа' => 'спа',
        ];
        
        foreach ($commonTypos as $typo => $correction) {
            if (stripos($query, $typo) !== false) {
                $suggestions[] = str_ireplace($typo, $correction, $query);
            }
        }
        
        return array_unique($suggestions);
    }

    /**
     * Получить общее количество поисковых запросов
     */
    protected function getTotalSearchCount(array $filters): int
    {
        // Здесь должна быть логика подсчета из БД аналитики
        return 0;
    }

    /**
     * Получить топ запросов
     */
    protected function getTopQueries(array $filters): array
    {
        // Здесь должна быть логика получения из БД аналитики
        return [];
    }

    /**
     * Получить распределение типов поиска
     */
    protected function getSearchTypesDistribution(array $filters): array
    {
        // Здесь должна быть логика подсчета из БД аналитики
        return [];
    }

    /**
     * Получить коэффициенты конверсии
     */
    protected function getConversionRates(array $filters): array
    {
        // Здесь должна быть логика подсчета из БД аналитики
        return [];
    }

    /**
     * Получить среднее количество результатов
     */
    protected function getAverageResultsCount(array $filters): float
    {
        // Здесь должна быть логика подсчета из БД аналитики
        return 0.0;
    }

    /**
     * Получить запросы без результатов
     */
    protected function getZeroResultsQueries(array $filters): array
    {
        // Здесь должна быть логика получения из БД аналитики
        return [];
    }

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
            $popularQueries = $this->getPopularQueries($type, 20);
            
            foreach ($popularQueries as $query) {
                try {
                    $this->search($query, $type, [], SortBy::getDefaultForSearchType($type), 1, 20);
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
}