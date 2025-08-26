<?php

namespace App\Domain\Search\Services;

use App\Infrastructure\Search\ElasticsearchClient;
use App\Domain\Search\Enums\SearchType;
use App\Domain\Search\Enums\SortBy;
use App\Domain\Search\QueryBuilders\ElasticsearchQueryBuilder;
use App\Domain\Search\Transformers\SearchResultsTransformer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

/**
 * Унифицированный Elasticsearch движок поиска
 * Консолидирует: ElasticsearchSearchEngine + часть GlobalSearchEngine
 * 
 * Принцип KISS: один класс для всего поиска через ES
 */
class ElasticsearchEngine implements SearchEngineInterface
{
    protected ElasticsearchClient $client;
    protected ElasticsearchQueryBuilder $queryBuilder;
    protected SearchResultsTransformer $resultsTransformer;

    public function __construct(
        ElasticsearchClient $client,
        ?ElasticsearchQueryBuilder $queryBuilder = null,
        ?SearchResultsTransformer $resultsTransformer = null
    ) {
        $this->client = $client;
        $this->queryBuilder = $queryBuilder ?? new ElasticsearchQueryBuilder();
        $this->resultsTransformer = $resultsTransformer ?? new SearchResultsTransformer();
    }

    /**
     * Основной метод поиска
     */
    public function search(
        string $query,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20,
        ?array $location = null
    ): LengthAwarePaginator {
        
        $cacheKey = $this->getCacheKey($query, $filters, $sortBy, $page, $perPage, $location);
        
        return Cache::remember($cacheKey, 300, function () use ($query, $filters, $sortBy, $page, $perPage, $location) {
            return $this->performSearch($query, $filters, $sortBy, $page, $perPage, $location);
        });
    }

    /**
     * Быстрый поиск для автодополнения
     */
    public function quickSearch(string $query, int $limit = 5): array
    {
        if (mb_strlen($query) < 2) {
            return [];
        }

        $cacheKey = "quick_search:" . md5($query) . ":{$limit}";
        
        return Cache::remember($cacheKey, 300, function () use ($query, $limit) {
            try {
                $searchQuery = $this->queryBuilder->buildQuickSearchQuery($query, $limit);
                $response = $this->client->search(['body' => $searchQuery]);
                
                return $this->extractQuickResults($response);
                
            } catch (\Exception $e) {
                Log::warning('Quick search failed', [
                    'query' => $query,
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Поиск похожих объектов
     */
    public function findSimilar(int $objectId, int $limit = 10, array $excludeIds = []): array
    {
        try {
            $searchQuery = $this->queryBuilder->buildSimilarQuery($objectId, $limit, $excludeIds);
            $response = $this->client->search(['body' => $searchQuery]);
            
            return $this->extractSimilarResults($response);
            
        } catch (\Exception $e) {
            Log::error('Similar search failed', [
                'objectId' => $objectId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Продвинутый поиск с комплексными критериями
     */
    public function advancedSearch(array $criteria): LengthAwarePaginator
    {
        try {
            $searchQuery = $this->queryBuilder->buildAdvancedQuery($criteria);
            $response = $this->client->search(['body' => $searchQuery]);
            
            return $this->transformToLengthAwarePaginator(
                $response, 
                $criteria['page'] ?? 1, 
                $criteria['perPage'] ?? 20
            );
            
        } catch (\Exception $e) {
            Log::error('Advanced search failed', [
                'criteria' => $criteria,
                'error' => $e->getMessage()
            ]);
            return $this->getEmptyResults($criteria['page'] ?? 1, $criteria['perPage'] ?? 20);
        }
    }

    /**
     * Фасетный поиск (поиск с группировкой)
     */
    public function facetedSearch(string $query, array $facets = []): array
    {
        try {
            $searchQuery = $this->queryBuilder->buildFacetedQuery($query, $facets);
            $response = $this->client->search(['body' => $searchQuery]);
            
            return $this->extractFacetResults($response);
            
        } catch (\Exception $e) {
            Log::error('Faceted search failed', [
                'query' => $query,
                'facets' => $facets,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Геопоиск
     */
    public function geoSearch(
        array $location, 
        float $radius, 
        array $filters = [], 
        int $limit = 20
    ): array {
        try {
            $searchQuery = $this->queryBuilder->buildGeoQuery($location, $radius, $filters, $limit);
            $response = $this->client->search(['body' => $searchQuery]);
            
            return $this->extractGeoResults($response);
            
        } catch (\Exception $e) {
            Log::error('Geo search failed', [
                'location' => $location,
                'radius' => $radius,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Умный поиск с ML
     */
    public function intelligentSearch(string $query, ?int $userId = null, array $context = []): LengthAwarePaginator
    {
        try {
            // Обогащаем запрос контекстом пользователя
            $enrichedQuery = $this->enrichQueryWithContext($query, $userId, $context);
            
            $searchQuery = $this->queryBuilder->buildIntelligentQuery($enrichedQuery, $userId, $context);
            $response = $this->client->search(['body' => $searchQuery]);
            
            return $this->transformToLengthAwarePaginator($response, 1, 20);
            
        } catch (\Exception $e) {
            Log::error('Intelligent search failed', [
                'query' => $query,
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            
            // Fallback на обычный поиск
            return $this->search($query);
        }
    }

    /**
     * Экспорт результатов
     */
    public function exportResults(LengthAwarePaginator $results, string $format = 'csv'): string
    {
        return $this->resultsTransformer->exportResults($results, $format);
    }

    /**
     * Обогащение результатов дополнительными данными
     */
    public function enrichResults(LengthAwarePaginator $results): LengthAwarePaginator
    {
        return $this->resultsTransformer->enrichResults($results);
    }

    /**
     * Проверка работоспособности
     */
    public function healthCheck(): array
    {
        try {
            $response = $this->client->ping();
            $clusterHealth = $this->client->cluster()->health();
            
            return [
                'status' => 'healthy',
                'ping' => $response,
                'cluster_status' => $clusterHealth['status'] ?? 'unknown'
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Поиск объявлений
     */
    public function searchAds(
        string $query,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20,
        ?array $location = null
    ): LengthAwarePaginator {
        $filters['type'] = 'ads';
        return $this->search($query, $filters, $sortBy, $page, $perPage, $location);
    }

    /**
     * Поиск мастеров
     */
    public function searchMasters(
        string $query,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20,
        ?array $location = null
    ): LengthAwarePaginator {
        $filters['type'] = 'masters';
        return $this->search($query, $filters, $sortBy, $page, $perPage, $location);
    }

    /**
     * Поиск услуг
     */
    public function searchServices(
        string $query,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20
    ): LengthAwarePaginator {
        $filters['type'] = 'services';
        return $this->search($query, $filters, $sortBy, $page, $perPage, null);
    }

    // === ПРИВАТНЫЕ МЕТОДЫ ===

    /**
     * Выполнить реальный поиск
     */
    private function performSearch(
        string $query,
        array $filters,
        SortBy $sortBy,
        int $page,
        int $perPage,
        ?array $location
    ): LengthAwarePaginator {
        try {
            $searchQuery = $this->queryBuilder->buildSearchQuery($query, $filters, $sortBy, $page, $perPage, $location);
            $response = $this->client->search(['body' => $searchQuery]);
            
            return $this->transformToLengthAwarePaginator($response, $page, $perPage);
            
        } catch (\Exception $e) {
            Log::error('Elasticsearch search failed', [
                'query' => $query,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            
            return $this->getEmptyResults($page, $perPage);
        }
    }

    /**
     * Генерировать ключ кеша
     */
    private function getCacheKey(string $query, array $filters, SortBy $sortBy, int $page, int $perPage, ?array $location): string
    {
        return 'es_search:' . md5(serialize([
            'query' => $query,
            'filters' => $filters,
            'sortBy' => $sortBy->value,
            'page' => $page,
            'perPage' => $perPage,
            'location' => $location
        ]));
    }

    /**
     * Преобразовать ES ответ в LengthAwarePaginator
     */
    private function transformToLengthAwarePaginator(array $response, int $page, int $perPage): LengthAwarePaginator
    {
        $hits = $response['hits']['hits'] ?? [];
        $total = $response['hits']['total']['value'] ?? 0;
        
        $items = collect($hits)->map(function ($hit) {
            return $this->resultsTransformer->transformHit($hit);
        });
        
        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Извлечь результаты быстрого поиска
     */
    private function extractQuickResults(array $response): array
    {
        $hits = $response['hits']['hits'] ?? [];
        
        return collect($hits)->map(function ($hit) {
            return [
                'id' => $hit['_id'],
                'title' => $hit['_source']['title'] ?? '',
                'type' => $hit['_index'],
                'score' => $hit['_score']
            ];
        })->toArray();
    }

    /**
     * Извлечь похожие результаты
     */
    private function extractSimilarResults(array $response): array
    {
        $hits = $response['hits']['hits'] ?? [];
        
        return collect($hits)->map(function ($hit) {
            return $this->resultsTransformer->transformHit($hit);
        })->toArray();
    }

    /**
     * Извлечь результаты фасетного поиска
     */
    private function extractFacetResults(array $response): array
    {
        return [
            'results' => $this->extractQuickResults($response),
            'facets' => $response['aggregations'] ?? []
        ];
    }

    /**
     * Извлечь георезультаты
     */
    private function extractGeoResults(array $response): array
    {
        $hits = $response['hits']['hits'] ?? [];
        
        return collect($hits)->map(function ($hit) {
            $result = $this->resultsTransformer->transformHit($hit);
            
            // Добавляем расстояние если есть
            if (isset($hit['sort'][0])) {
                $result['distance'] = $hit['sort'][0];
            }
            
            return $result;
        })->toArray();
    }

    /**
     * Обогатить запрос контекстом
     */
    private function enrichQueryWithContext(string $query, ?int $userId, array $context): string
    {
        // Здесь можно добавить ML логику для улучшения запроса
        // Пока просто возвращаем исходный запрос
        return $query;
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
}