<?php

namespace App\Domain\Search\QueryBuilders;

use App\Domain\Search\Enums\SearchType;
use App\Domain\Search\Enums\SortBy;

/**
 * Построитель запросов для Elasticsearch
 */
class ElasticsearchQueryBuilder
{
    protected SearchType $searchType;

    public function __construct(SearchType $searchType)
    {
        $this->searchType = $searchType;
    }

    /**
     * Построить основной поисковый запрос
     */
    public function buildSearchQuery(
        string $query,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        ?array $location = null,
        int $page = 1,
        int $perPage = 20
    ): array {
        $searchQuery = [
            'index' => $this->getIndexName(),
            'body' => [
                'query' => $this->buildQuery($query, $filters, $location),
                'sort' => $this->buildSort($sortBy, $location),
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
                'track_total_hits' => true
            ]
        ];

        // Добавляем аггрегации для фасетного поиска
        $searchQuery['body']['aggs'] = $this->buildAggregations();

        return $searchQuery;
    }

    /**
     * Построить основной запрос
     */
    protected function buildQuery(string $query, array $filters = [], ?array $location = null): array
    {
        $boolQuery = [
            'bool' => [
                'must' => [],
                'filter' => [],
                'should' => [],
                'must_not' => []
            ]
        ];

        // Основной текстовый поиск
        if (!empty($query)) {
            $boolQuery['bool']['must'][] = $this->buildTextQuery($query);
        }

        // Фильтры
        if (!empty($filters)) {
            $boolQuery['bool']['filter'] = array_merge(
                $boolQuery['bool']['filter'],
                $this->buildFilters($filters)
            );
        }

        // Геопоиск
        if ($location && isset($location['lat'], $location['lng'])) {
            $boolQuery['bool']['filter'][] = $this->buildGeoFilter($location);
        }

        // Базовые фильтры (активные записи)
        $boolQuery['bool']['filter'][] = ['term' => ['is_active' => true]];

        return $boolQuery;
    }

    /**
     * Построить текстовый запрос
     */
    protected function buildTextQuery(string $query): array
    {
        return [
            'multi_match' => [
                'query' => $query,
                'fields' => $this->getSearchFields(),
                'type' => 'best_fields',
                'fuzziness' => 'AUTO',
                'operator' => 'or',
                'boost' => 1.0
            ]
        ];
    }

    /**
     * Построить фильтры
     */
    protected function buildFilters(array $filters): array
    {
        $filterQueries = [];

        foreach ($filters as $field => $value) {
            if (empty($value)) continue;

            switch ($field) {
                case 'category':
                case 'categories':
                    $filterQueries[] = $this->buildCategoryFilter($value);
                    break;

                case 'price_range':
                    $filterQueries[] = $this->buildPriceRangeFilter($value);
                    break;

                case 'rating':
                    $filterQueries[] = $this->buildRatingFilter($value);
                    break;

                case 'specializations':
                    $filterQueries[] = $this->buildSpecializationsFilter($value);
                    break;

                case 'metro_stations':
                    $filterQueries[] = $this->buildMetroFilter($value);
                    break;

                case 'working_hours':
                    $filterQueries[] = $this->buildWorkingHoursFilter($value);
                    break;

                case 'verification_level':
                    $filterQueries[] = $this->buildVerificationFilter($value);
                    break;

                default:
                    if (is_array($value)) {
                        $filterQueries[] = ['terms' => [$field => $value]];
                    } else {
                        $filterQueries[] = ['term' => [$field => $value]];
                    }
            }
        }

        return $filterQueries;
    }

    /**
     * Построить сортировку
     */
    protected function buildSort(SortBy $sortBy, ?array $location = null): array
    {
        switch ($sortBy) {
            case SortBy::RELEVANCE:
                return ['_score' => ['order' => 'desc']];

            case SortBy::RATING:
                return [
                    'rating' => ['order' => 'desc'],
                    '_score' => ['order' => 'desc']
                ];

            case SortBy::PRICE_LOW:
                return [
                    'price_min' => ['order' => 'asc'],
                    '_score' => ['order' => 'desc']
                ];

            case SortBy::PRICE_HIGH:
                return [
                    'price_max' => ['order' => 'desc'],
                    '_score' => ['order' => 'desc']
                ];

            case SortBy::DISTANCE:
                if ($location && isset($location['lat'], $location['lng'])) {
                    return [
                        '_geo_distance' => [
                            'location' => [
                                'lat' => $location['lat'],
                                'lon' => $location['lng']
                            ],
                            'order' => 'asc',
                            'unit' => 'km'
                        ]
                    ];
                }
                return ['_score' => ['order' => 'desc']];

            case SortBy::NEWEST:
                return [
                    'created_at' => ['order' => 'desc'],
                    '_score' => ['order' => 'desc']
                ];

            case SortBy::POPULARITY:
                return [
                    'views_count' => ['order' => 'desc'],
                    'rating' => ['order' => 'desc'],
                    '_score' => ['order' => 'desc']
                ];

            default:
                return ['_score' => ['order' => 'desc']];
        }
    }

    /**
     * Построить аггрегации
     */
    protected function buildAggregations(): array
    {
        return [
            'categories' => [
                'terms' => ['field' => 'category', 'size' => 20]
            ],
            'price_ranges' => [
                'range' => [
                    'field' => 'price_min',
                    'ranges' => [
                        ['to' => 1000],
                        ['from' => 1000, 'to' => 3000],
                        ['from' => 3000, 'to' => 5000],
                        ['from' => 5000, 'to' => 10000],
                        ['from' => 10000]
                    ]
                ]
            ],
            'ratings' => [
                'range' => [
                    'field' => 'rating',
                    'ranges' => [
                        ['from' => 4.5],
                        ['from' => 4.0, 'to' => 4.5],
                        ['from' => 3.5, 'to' => 4.0],
                        ['from' => 3.0, 'to' => 3.5]
                    ]
                ]
            ],
            'specializations' => [
                'terms' => ['field' => 'specializations', 'size' => 15]
            ]
        ];
    }

    /**
     * Получить поля для поиска в зависимости от типа
     */
    protected function getSearchFields(): array
    {
        switch ($this->searchType) {
            case SearchType::MASTERS:
                return [
                    'name^3',
                    'specialty^2',
                    'about',
                    'specializations^2',
                    'skills'
                ];

            case SearchType::ADS:
                return [
                    'title^3',
                    'description^2',
                    'category^2',
                    'services'
                ];

            default:
                return ['_all'];
        }
    }

    /**
     * Фильтр по категориям
     */
    private function buildCategoryFilter($value): array
    {
        return is_array($value) 
            ? ['terms' => ['category' => $value]]
            : ['term' => ['category' => $value]];
    }

    /**
     * Фильтр по ценовому диапазону
     */
    private function buildPriceRangeFilter(array $range): array
    {
        $rangeQuery = [];
        
        if (isset($range['from'])) {
            $rangeQuery['gte'] = $range['from'];
        }
        
        if (isset($range['to'])) {
            $rangeQuery['lte'] = $range['to'];
        }

        return ['range' => ['price_min' => $rangeQuery]];
    }

    /**
     * Фильтр по рейтингу
     */
    private function buildRatingFilter(float $minRating): array
    {
        return ['range' => ['rating' => ['gte' => $minRating]]];
    }

    /**
     * Фильтр по специализациям
     */
    private function buildSpecializationsFilter($value): array
    {
        return is_array($value)
            ? ['terms' => ['specializations' => $value]]
            : ['term' => ['specializations' => $value]];
    }

    /**
     * Фильтр по станциям метро
     */
    private function buildMetroFilter($value): array
    {
        return is_array($value)
            ? ['terms' => ['metro_stations' => $value]]
            : ['term' => ['metro_stations' => $value]];
    }

    /**
     * Фильтр по рабочим часам
     */
    private function buildWorkingHoursFilter(array $hours): array
    {
        // Упрощенная реализация
        return ['term' => ['available_now' => true]];
    }

    /**
     * Фильтр по уровню верификации
     */
    private function buildVerificationFilter($value): array
    {
        return is_array($value)
            ? ['terms' => ['verification_level' => $value]]
            : ['term' => ['verification_level' => $value]];
    }

    /**
     * Построить гео-фильтр
     */
    private function buildGeoFilter(array $location): array
    {
        $distance = $location['radius'] ?? '10km';
        
        return [
            'geo_distance' => [
                'distance' => $distance,
                'location' => [
                    'lat' => $location['lat'],
                    'lon' => $location['lng']
                ]
            ]
        ];
    }

    /**
     * Получить имя индекса
     */
    private function getIndexName(): string
    {
        return match($this->searchType) {
            SearchType::MASTERS => 'masters',
            SearchType::ADS => 'ads',
            default => 'general'
        };
    }
}