<?php

namespace App\Domain\Search\Services;

use App\Infrastructure\Search\ElasticsearchClient;
use App\Domain\Search\Enums\SearchType;
use App\Domain\Search\Enums\SortBy;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * Движок поиска через Elasticsearch
 */
class ElasticsearchSearchEngine implements SearchEngineInterface
{
    protected ElasticsearchClient $client;
    protected string $indexName;
    protected SearchType $searchType;

    public function __construct(ElasticsearchClient $client, SearchType $searchType)
    {
        $this->client = $client;
        $this->searchType = $searchType;
        $this->indexName = $this->getIndexName($searchType);
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
            $searchQuery = $this->buildSearchQuery($query, $filters, $sortBy, $location, $page, $perPage);
            $response = $this->client->search($this->indexName, $searchQuery);
            
            return $this->transformResults($response, $page, $perPage);
            
        } catch (\Exception $e) {
            Log::error('Elasticsearch search failed', [
                'index' => $this->indexName,
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            // Fallback на пустые результаты
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }
    }

    /**
     * Быстрый поиск
     */
    public function quickSearch(string $query, int $limit = 5): array
    {
        try {
            $searchQuery = [
                'size' => $limit,
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => $this->getQuickSearchFields(),
                        'type' => 'phrase_prefix',
                        'analyzer' => 'autocomplete_search'
                    ]
                ],
                '_source' => $this->getQuickSearchSource()
            ];
            
            $response = $this->client->search($this->indexName, $searchQuery);
            
            return array_map(function ($hit) {
                return $this->formatQuickResult($hit['_source']);
            }, $response['hits']['hits']);
            
        } catch (\Exception $e) {
            Log::error('Elasticsearch quick search failed', [
                'index' => $this->indexName,
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Получить связанные запросы
     */
    public function getRelatedQueries(string $query): array
    {
        try {
            // Используем more_like_this для поиска похожих документов
            $searchQuery = [
                'size' => 0,
                'query' => [
                    'more_like_this' => [
                        'fields' => $this->getContentFields(),
                        'like' => $query,
                        'min_term_freq' => 1,
                        'min_doc_freq' => 1
                    ]
                ],
                'aggs' => [
                    'related_terms' => [
                        'significant_terms' => [
                            'field' => $this->getKeywordField(),
                            'size' => 10,
                            'min_doc_count' => 2
                        ]
                    ]
                ]
            ];
            
            $response = $this->client->search($this->indexName, $searchQuery);
            
            $terms = [];
            foreach ($response['aggregations']['related_terms']['buckets'] ?? [] as $bucket) {
                $terms[] = $bucket['key'];
            }
            
            return $terms;
            
        } catch (\Exception $e) {
            Log::error('Failed to get related queries', [
                'index' => $this->indexName,
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Поиск похожих объектов
     */
    public function findSimilar(int $objectId, int $limit = 10, array $excludeIds = []): array
    {
        try {
            // Сначала получаем документ
            $doc = $this->client->get($this->indexName, (string)$objectId);
            
            if (!$doc) {
                return [];
            }
            
            $excludeIds[] = $objectId; // Исключаем сам объект
            
            $searchQuery = [
                'size' => $limit,
                'query' => [
                    'bool' => [
                        'must' => [
                            'more_like_this' => [
                                'fields' => $this->getContentFields(),
                                'like' => [
                                    '_index' => $this->client->getIndexName($this->indexName),
                                    '_id' => (string)$objectId
                                ],
                                'min_term_freq' => 1,
                                'min_doc_freq' => 1,
                                'max_query_terms' => 20
                            ]
                        ],
                        'must_not' => [
                            'ids' => [
                                'values' => array_map('strval', $excludeIds)
                            ]
                        ]
                    ]
                ],
                '_source' => true
            ];
            
            $response = $this->client->search($this->indexName, $searchQuery);
            
            return array_map(function ($hit) {
                return $this->formatSimilarResult($hit['_source'], $hit['_score']);
            }, $response['hits']['hits']);
            
        } catch (\Exception $e) {
            Log::error('Failed to find similar objects', [
                'index' => $this->indexName,
                'object_id' => $objectId,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Продвинутый поиск
     */
    public function advancedSearch(array $criteria): LengthAwarePaginator
    {
        $query = $criteria['query'] ?? '';
        $filters = $criteria['filters'] ?? [];
        $sortBy = SortBy::tryFrom($criteria['sort'] ?? 'relevance') ?? SortBy::RELEVANCE;
        $page = $criteria['page'] ?? 1;
        $perPage = $criteria['per_page'] ?? 20;
        $location = $criteria['location'] ?? null;
        
        // Добавляем дополнительные критерии в фильтры
        if (isset($criteria['date_from'])) {
            $filters['date_from'] = $criteria['date_from'];
        }
        
        if (isset($criteria['date_to'])) {
            $filters['date_to'] = $criteria['date_to'];
        }
        
        return $this->search($query, $filters, $sortBy, $page, $perPage, $location);
    }

    /**
     * Фасетный поиск
     */
    public function facetedSearch(string $query, array $facets = []): array
    {
        try {
            $aggs = [];
            
            foreach ($facets as $facet) {
                $aggs[$facet] = $this->buildFacetAggregation($facet);
            }
            
            $searchQuery = [
                'size' => 0,
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => $this->getSearchFields(),
                        'type' => 'best_fields'
                    ]
                ],
                'aggs' => $aggs
            ];
            
            $response = $this->client->search($this->indexName, $searchQuery);
            
            $result = [];
            foreach ($facets as $facet) {
                $result[$facet] = $this->extractFacetResults($response['aggregations'][$facet] ?? [], $facet);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Faceted search failed', [
                'index' => $this->indexName,
                'query' => $query,
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
            $searchQuery = [
                'size' => $limit,
                'query' => [
                    'bool' => [
                        'filter' => [
                            [
                                'geo_distance' => [
                                    'distance' => $radius . 'km',
                                    'location' => [
                                        'lat' => $location['lat'],
                                        'lon' => $location['lng'] ?? $location['lon']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'sort' => [
                    [
                        '_geo_distance' => [
                            'location' => [
                                'lat' => $location['lat'],
                                'lon' => $location['lng'] ?? $location['lon']
                            ],
                            'order' => 'asc',
                            'unit' => 'km'
                        ]
                    ]
                ],
                '_source' => true
            ];
            
            // Добавляем дополнительные фильтры
            if (!empty($filters)) {
                $searchQuery['query']['bool']['must'] = $this->buildFilters($filters);
            }
            
            $response = $this->client->search($this->indexName, $searchQuery);
            
            return array_map(function ($hit) {
                $result = $this->formatGeoResult($hit['_source']);
                $result['distance'] = $hit['sort'][0] ?? null;
                return $result;
            }, $response['hits']['hits']);
            
        } catch (\Exception $e) {
            Log::error('Geo search failed', [
                'index' => $this->indexName,
                'location' => $location,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Построить поисковый запрос
     */
    protected function buildSearchQuery(
        string $query,
        array $filters,
        SortBy $sortBy,
        ?array $location,
        int $page,
        int $perPage
    ): array {
        
        $from = ($page - 1) * $perPage;
        
        $searchQuery = [
            'from' => $from,
            'size' => $perPage,
            'query' => [
                'bool' => [
                    'must' => [],
                    'filter' => []
                ]
            ],
            'sort' => $this->buildSort($sortBy, $location),
            '_source' => true,
            'highlight' => [
                'fields' => $this->getHighlightFields(),
                'pre_tags' => ['<em>'],
                'post_tags' => ['</em>']
            ]
        ];
        
        // Добавляем текстовый поиск
        if (!empty($query)) {
            $searchQuery['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => $this->getSearchFields(),
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO',
                    'prefix_length' => 2
                ]
            ];
        }
        
        // Добавляем фильтры
        if (!empty($filters)) {
            $searchQuery['query']['bool']['filter'] = $this->buildFilters($filters);
        }
        
        // Добавляем геофильтр если есть локация
        if ($location && isset($location['lat']) && isset($location['radius'])) {
            $searchQuery['query']['bool']['filter'][] = [
                'geo_distance' => [
                    'distance' => ($location['radius'] ?? 10) . 'km',
                    'location' => [
                        'lat' => $location['lat'],
                        'lon' => $location['lng'] ?? $location['lon']
                    ]
                ]
            ];
        }
        
        return $searchQuery;
    }

    /**
     * Построить фильтры
     */
    protected function buildFilters(array $filters): array
    {
        $esFilters = [];
        
        foreach ($filters as $field => $value) {
            if (empty($value)) continue;
            
            switch ($field) {
                case 'price_range':
                    if (is_array($value) && count($value) === 2) {
                        $esFilters[] = [
                            'range' => [
                                'price' => [
                                    'gte' => $value[0],
                                    'lte' => $value[1]
                                ]
                            ]
                        ];
                    }
                    break;
                    
                case 'rating':
                    $esFilters[] = [
                        'range' => [
                            $this->getRatingField() => [
                                'gte' => (float)$value
                            ]
                        ]
                    ];
                    break;
                    
                case 'city':
                case 'region':
                case 'status':
                case 'work_format':
                    $esFilters[] = [
                        'term' => [
                            $field . '.keyword' => $value
                        ]
                    ];
                    break;
                    
                case 'categories':
                case 'tags':
                case 'services':
                    $esFilters[] = [
                        'terms' => [
                            $field => is_array($value) ? $value : [$value]
                        ]
                    ];
                    break;
                    
                case 'has_photos':
                case 'is_verified':
                case 'is_premium':
                case 'is_available':
                    $esFilters[] = [
                        'term' => [
                            $field => (bool)$value
                        ]
                    ];
                    break;
                    
                case 'date_from':
                    $esFilters[] = [
                        'range' => [
                            'created_at' => [
                                'gte' => $value
                            ]
                        ]
                    ];
                    break;
                    
                case 'date_to':
                    $esFilters[] = [
                        'range' => [
                            'created_at' => [
                                'lte' => $value
                            ]
                        ]
                    ];
                    break;
            }
        }
        
        return $esFilters;
    }

    /**
     * Построить сортировку
     */
    protected function buildSort(SortBy $sortBy, ?array $location = null): array
    {
        switch ($sortBy) {
            case SortBy::RELEVANCE:
                return ['_score' => 'desc', 'boost_score' => 'desc'];
                
            case SortBy::PRICE_ASC:
                return ['price' => 'asc'];
                
            case SortBy::PRICE_DESC:
                return ['price' => 'desc'];
                
            case SortBy::RATING:
                return [$this->getRatingField() => 'desc', 'reviews_count' => 'desc'];
                
            case SortBy::DATE:
                return ['created_at' => 'desc'];
                
            case SortBy::POPULARITY:
                return ['views_count' => 'desc', 'favorites_count' => 'desc'];
                
            case SortBy::DISTANCE:
                if ($location && isset($location['lat'])) {
                    return [
                        '_geo_distance' => [
                            'location' => [
                                'lat' => $location['lat'],
                                'lon' => $location['lng'] ?? $location['lon']
                            ],
                            'order' => 'asc',
                            'unit' => 'km'
                        ]
                    ];
                }
                return ['_score' => 'desc'];
                
            default:
                return ['_score' => 'desc'];
        }
    }

    /**
     * Преобразовать результаты
     */
    protected function transformResults(array $response, int $page, int $perPage): LengthAwarePaginator
    {
        $total = $response['hits']['total']['value'] ?? 0;
        $hits = $response['hits']['hits'] ?? [];
        
        $items = collect($hits)->map(function ($hit) {
            $source = $hit['_source'];
            $source['id'] = $hit['_id'];
            $source['_score'] = $hit['_score'] ?? 0;
            
            // Добавляем подсветку
            if (isset($hit['highlight'])) {
                $source['_highlights'] = $hit['highlight'];
            }
            
            // Добавляем расстояние если есть
            if (isset($hit['sort']) && is_array($hit['sort']) && isset($hit['sort'][0])) {
                $source['_distance'] = $hit['sort'][0];
            }
            
            return $this->hydrateModel($source);
        });
        
        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query()
            ]
        );
    }

    /**
     * Получить имя индекса
     */
    protected function getIndexName(SearchType $type): string
    {
        return match($type) {
            SearchType::ADS => 'ads',
            SearchType::MASTERS => 'masters',
            SearchType::SERVICES => 'services',
            default => 'ads'
        };
    }

    /**
     * Получить поля для поиска
     */
    protected function getSearchFields(): array
    {
        return match($this->searchType) {
            SearchType::ADS => [
                'title^3',
                'description^2',
                'specialty^2.5',
                'additional_features',
                'city',
                'master.name^1.5',
                'tags'
            ],
            SearchType::MASTERS => [
                'name^3',
                'about^2',
                'specialty^2.5',
                'specializations^2',
                'skills^1.5',
                'city',
                'tags'
            ],
            default => ['*']
        };
    }

    /**
     * Получить поля для быстрого поиска
     */
    protected function getQuickSearchFields(): array
    {
        return match($this->searchType) {
            SearchType::ADS => ['title.autocomplete', 'specialty.autocomplete'],
            SearchType::MASTERS => ['name.autocomplete', 'specialty.autocomplete'],
            default => ['title.autocomplete']
        };
    }

    /**
     * Получить поля для быстрого поиска
     */
    protected function getQuickSearchSource(): array
    {
        return match($this->searchType) {
            SearchType::ADS => ['id', 'title', 'price', 'city', 'master.name', 'master.rating'],
            SearchType::MASTERS => ['id', 'name', 'specialty', 'city', 'rating', 'avatar_url'],
            default => ['id', 'title']
        };
    }

    /**
     * Получить контентные поля
     */
    protected function getContentFields(): array
    {
        return match($this->searchType) {
            SearchType::ADS => ['title', 'description', 'specialty', 'additional_features'],
            SearchType::MASTERS => ['name', 'about', 'specialty', 'specializations'],
            default => ['title', 'description']
        };
    }

    /**
     * Получить поле с ключевыми словами
     */
    protected function getKeywordField(): string
    {
        return match($this->searchType) {
            SearchType::ADS => 'specialty.keyword',
            SearchType::MASTERS => 'specialty.keyword',
            default => 'title.keyword'
        };
    }

    /**
     * Получить поле рейтинга
     */
    protected function getRatingField(): string
    {
        return match($this->searchType) {
            SearchType::ADS => 'master.rating',
            SearchType::MASTERS => 'rating',
            default => 'rating'
        };
    }

    /**
     * Получить поля для подсветки
     */
    protected function getHighlightFields(): array
    {
        return [
            'title' => ['number_of_fragments' => 0],
            'description' => ['fragment_size' => 150, 'number_of_fragments' => 3],
            'specialty' => ['number_of_fragments' => 0],
            'name' => ['number_of_fragments' => 0],
            'about' => ['fragment_size' => 150, 'number_of_fragments' => 3]
        ];
    }

    /**
     * Гидратировать модель из данных ES
     */
    protected function hydrateModel(array $data)
    {
        // Получаем ID для загрузки модели
        $id = $data['id'] ?? null;
        
        if (!$id) {
            return $data;
        }
        
        try {
            $model = match($this->searchType) {
                SearchType::ADS => \App\Domain\Ad\Models\Ad::find($id),
                SearchType::MASTERS => \App\Domain\Master\Models\MasterProfile::find($id),
                default => null
            };
            
            if ($model) {
                // Добавляем дополнительные данные из ES
                $model->setAttribute('_score', $data['_score'] ?? 0);
                $model->setAttribute('_highlights', $data['_highlights'] ?? []);
                $model->setAttribute('_distance', $data['_distance'] ?? null);
                
                return $model;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to hydrate model from ES', [
                'type' => $this->searchType->value,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
        }
        
        // Возвращаем сырые данные если модель не найдена
        return $data;
    }

    /**
     * Форматировать быстрый результат
     */
    protected function formatQuickResult(array $data): array
    {
        return match($this->searchType) {
            SearchType::ADS => [
                'id' => $data['id'],
                'title' => $data['title'],
                'price' => $data['price'] ?? 0,
                'city' => $data['city'] ?? '',
                'master_name' => $data['master']['name'] ?? '',
                'master_rating' => $data['master']['rating'] ?? 0
            ],
            SearchType::MASTERS => [
                'id' => $data['id'],
                'name' => $data['name'],
                'specialty' => $data['specialty'] ?? '',
                'city' => $data['city'] ?? '',
                'rating' => $data['rating'] ?? 0,
                'avatar_url' => $data['avatar_url'] ?? null
            ],
            default => $data
        };
    }

    /**
     * Форматировать похожий результат
     */
    protected function formatSimilarResult(array $data, float $score): array
    {
        $result = $this->formatQuickResult($data);
        $result['similarity_score'] = $score;
        return $result;
    }

    /**
     * Форматировать гео результат
     */
    protected function formatGeoResult(array $data): array
    {
        $result = $this->formatQuickResult($data);
        $result['location'] = $data['location'] ?? null;
        $result['address'] = $data['address'] ?? '';
        return $result;
    }

    /**
     * Построить агрегацию для фасета
     */
    protected function buildFacetAggregation(string $facet): array
    {
        return match($facet) {
            'cities' => [
                'terms' => [
                    'field' => 'city.keyword',
                    'size' => 20
                ]
            ],
            'categories', 'specialties' => [
                'terms' => [
                    'field' => 'specialty.keyword',
                    'size' => 15
                ]
            ],
            'price_ranges' => [
                'range' => [
                    'field' => 'price',
                    'ranges' => [
                        ['key' => '0-1000', 'to' => 1000],
                        ['key' => '1000-2000', 'from' => 1000, 'to' => 2000],
                        ['key' => '2000-3000', 'from' => 2000, 'to' => 3000],
                        ['key' => '3000-5000', 'from' => 3000, 'to' => 5000],
                        ['key' => '5000+', 'from' => 5000]
                    ]
                ]
            ],
            'ratings' => [
                'range' => [
                    'field' => $this->getRatingField(),
                    'ranges' => [
                        ['key' => '4.5+', 'from' => 4.5],
                        ['key' => '4.0+', 'from' => 4.0],
                        ['key' => '3.5+', 'from' => 3.5],
                        ['key' => '3.0+', 'from' => 3.0]
                    ]
                ]
            ],
            default => [
                'terms' => [
                    'field' => $facet . '.keyword',
                    'size' => 10
                ]
            ]
        };
    }

    /**
     * Извлечь результаты фасета
     */
    protected function extractFacetResults(array $aggregation, string $facet): array
    {
        $results = [];
        
        foreach ($aggregation['buckets'] ?? [] as $bucket) {
            $results[] = [
                'value' => $bucket['key'],
                'count' => $bucket['doc_count']
            ];
        }
        
        return $results;
    }

    /**
     * Экспорт результатов
     */
    public function exportResults(LengthAwarePaginator $results, string $format): string
    {
        // Реализация экспорта в зависимости от формата
        throw new \Exception('Export not implemented for Elasticsearch engine');
    }

    /**
     * Обогащение результатов
     */
    public function enrichResults(LengthAwarePaginator $results): LengthAwarePaginator
    {
        // Результаты уже обогащены данными из ES
        return $results;
    }
}