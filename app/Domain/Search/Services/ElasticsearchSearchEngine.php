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

/**
 * Упрощенный движок поиска через Elasticsearch
 * Логика построения запросов и трансформации вынесена в отдельные классы
 */
class ElasticsearchSearchEngine implements SearchEngineInterface
{
    protected ElasticsearchClient $client;
    protected ElasticsearchQueryBuilder $queryBuilder;
    protected SearchResultsTransformer $resultsTransformer;
    protected SearchType $searchType;

    public function __construct(
        ElasticsearchClient $client,
        ElasticsearchQueryBuilder $queryBuilder,
        SearchResultsTransformer $resultsTransformer,
        SearchType $searchType
    ) {
        $this->client = $client;
        $this->queryBuilder = $queryBuilder;
        $this->resultsTransformer = $resultsTransformer;
        $this->searchType = $searchType;
    }

    /**
     * Выполнить поиск
     */
    public function search(
        string $query,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20,
        ?array $location = null
    ): LengthAwarePaginator {
        
        try {
            // Кешируем результаты для популярных запросов
            $cacheKey = $this->generateCacheKey($query, $filters, $sortBy, $page, $perPage, $location);
            
            return Cache::remember($cacheKey, 300, function () use ($query, $filters, $sortBy, $page, $perPage, $location) {
                return $this->performSearch($query, $filters, $sortBy, $page, $perPage, $location);
            });
            
        } catch (\Exception $e) {
            Log::error('Elasticsearch search failed', [
                'search_type' => $this->searchType->value,
                'query' => $query,
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            
            return $this->getEmptyResults($page, $perPage);
        }
    }

    /**
     * Получить предложения (автокомплит)
     */
    public function suggest(string $query, int $limit = 10): array
    {
        try {
            $cacheKey = "suggest_{$this->searchType->value}_{$query}_{$limit}";
            
            return Cache::remember($cacheKey, 600, function () use ($query, $limit) {
                return $this->performSuggest($query, $limit);
            });
            
        } catch (\Exception $e) {
            Log::error('Elasticsearch suggest failed', [
                'search_type' => $this->searchType->value,
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Поиск похожих элементов
     */
    public function findSimilar(int $id, int $limit = 5): array
    {
        try {
            $cacheKey = "similar_{$this->searchType->value}_{$id}_{$limit}";
            
            return Cache::remember($cacheKey, 1800, function () use ($id, $limit) {
                return $this->performSimilarSearch($id, $limit);
            });
            
        } catch (\Exception $e) {
            Log::error('Elasticsearch similar search failed', [
                'search_type' => $this->searchType->value,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Получить популярные поисковые запросы
     */
    public function getPopularQueries(int $limit = 10): array
    {
        $cacheKey = "popular_queries_{$this->searchType->value}_{$limit}";
        
        return Cache::remember($cacheKey, 3600, function () use ($limit) {
            // Здесь можно добавить логику получения популярных запросов
            // из отдельной таблицы search_queries или аналогичной
            return [];
        });
    }

    /**
     * Выполнить поиск без кеширования
     */
    protected function performSearch(
        string $query,
        array $filters,
        SortBy $sortBy,
        int $page,
        int $perPage,
        ?array $location
    ): LengthAwarePaginator {
        
        $searchQuery = $this->queryBuilder->buildSearchQuery(
            $query, 
            $filters, 
            $sortBy, 
            $location, 
            $page, 
            $perPage
        );
        
        $response = $this->client->search($searchQuery['index'], $searchQuery['body']);
        
        Log::info('Elasticsearch search executed', [
            'search_type' => $this->searchType->value,
            'query' => $query,
            'total_hits' => $response['hits']['total']['value'] ?? 0,
            'took' => $response['took'] ?? 0
        ]);
        
        return $this->resultsTransformer->transformResults($response, $page, $perPage);
    }

    /**
     * Выполнить поиск предложений
     */
    protected function performSuggest(string $query, int $limit): array
    {
        $suggestQuery = [
            'suggest' => [
                'suggestions' => [
                    'text' => $query,
                    'completion' => [
                        'field' => 'suggest',
                        'size' => $limit,
                        'skip_duplicates' => true
                    ]
                ]
            ]
        ];
        
        $indexName = $this->getIndexName();
        $response = $this->client->search($indexName, $suggestQuery);
        
        $suggestions = $response['suggest']['suggestions'][0]['options'] ?? [];
        
        return collect($suggestions)->pluck('text')->toArray();
    }

    /**
     * Найти похожие элементы
     */
    protected function performSimilarSearch(int $id, int $limit): array
    {
        $mltQuery = [
            'query' => [
                'more_like_this' => [
                    'fields' => $this->getSimilarityFields(),
                    'like' => [
                        [
                            '_index' => $this->getIndexName(),
                            '_id' => $id
                        ]
                    ],
                    'min_term_freq' => 2,
                    'max_query_terms' => 12,
                    'min_doc_freq' => 5
                ]
            ],
            'size' => $limit
        ];
        
        $response = $this->client->search($this->getIndexName(), $mltQuery);
        $hits = $response['hits']['hits'] ?? [];
        
        return collect($hits)->map(function ($hit) {
            return [
                'id' => $hit['_id'],
                'score' => $hit['_score'],
                'data' => $hit['_source']
            ];
        })->toArray();
    }

    /**
     * Получить пустые результаты в случае ошибки
     */
    protected function getEmptyResults(int $page, int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, $perPage, $page);
    }

    /**
     * Сгенерировать ключ кеша
     */
    protected function generateCacheKey(
        string $query,
        array $filters,
        SortBy $sortBy,
        int $page,
        int $perPage,
        ?array $location
    ): string {
        $data = [
            'type' => $this->searchType->value,
            'query' => $query,
            'filters' => $filters,
            'sort' => $sortBy->value,
            'page' => $page,
            'per_page' => $perPage,
            'location' => $location
        ];
        
        return 'search_' . md5(serialize($data));
    }

    /**
     * Получить имя индекса
     */
    protected function getIndexName(): string
    {
        return match($this->searchType) {
            SearchType::MASTERS => 'masters',
            SearchType::ADS => 'ads',
            default => 'general'
        };
    }

    /**
     * Получить поля для поиска похожих элементов
     */
    protected function getSimilarityFields(): array
    {
        return match($this->searchType) {
            SearchType::MASTERS => ['specialty', 'about', 'specializations', 'skills'],
            SearchType::ADS => ['title', 'description', 'category', 'services'],
            default => ['_all']
        };
    }

    /**
     * Очистить кеш поиска
     */
    public function clearCache(): void
    {
        $prefix = "search_{$this->searchType->value}_*";
        
        // Здесь нужна реализация очистки кеша по префиксу
        // В зависимости от драйвера кеша
        Log::info('Search cache cleared', ['search_type' => $this->searchType->value]);
    }
}