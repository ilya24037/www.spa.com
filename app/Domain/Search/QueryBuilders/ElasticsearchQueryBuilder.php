<?php

namespace App\Domain\Search\QueryBuilders;

use App\Domain\Search\Enums\SortBy;

/**
 * Построитель запросов для Elasticsearch
 */
class ElasticsearchQueryBuilder
{
    /**
     * Построить поисковый запрос
     */
    public function buildSearchQuery(
        string $query,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20,
        ?array $location = null
    ): array {
        $from = ($page - 1) * $perPage;
        
        $esQuery = [
            'from' => $from,
            'size' => $perPage,
            'query' => $this->buildQuery($query, $filters),
            'sort' => $this->buildSort($sortBy, $location)
        ];
        
        if ($location) {
            $esQuery['query'] = $this->addLocationFilter($esQuery['query'], $location);
        }
        
        return $esQuery;
    }
    
    /**
     * Построить запрос
     */
    private function buildQuery(string $query, array $filters): array
    {
        $must = [];
        
        if (!empty($query)) {
            $must[] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => ['title^3', 'description^2', 'content'],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO'
                ]
            ];
        }
        
        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                if (is_array($value)) {
                    $must[] = ['terms' => [$field => $value]];
                } else {
                    $must[] = ['term' => [$field => $value]];
                }
            }
        }
        
        return empty($must) ? ['match_all' => new \stdClass()] : ['bool' => ['must' => $must]];
    }
    
    /**
     * Построить сортировку
     */
    private function buildSort(SortBy $sortBy, ?array $location): array
    {
        switch ($sortBy) {
            case SortBy::PRICE_ASC:
                return [['price' => 'asc'], '_score'];
            case SortBy::PRICE_DESC:
                return [['price' => 'desc'], '_score'];
            case SortBy::DATE:
                return [['created_at' => 'desc'], '_score'];
            case SortBy::RATING:
                return [['rating' => 'desc'], '_score'];
            case SortBy::DISTANCE:
                if ($location) {
                    return [
                        [
                            '_geo_distance' => [
                                'location' => [
                                    'lat' => $location['lat'],
                                    'lon' => $location['lon']
                                ],
                                'order' => 'asc',
                                'unit' => 'km'
                            ]
                        ]
                    ];
                }
                // Fallback to relevance
            case SortBy::RELEVANCE:
            default:
                return ['_score'];
        }
    }
    
    /**
     * Добавить фильтр по местоположению
     */
    private function addLocationFilter(array $query, array $location): array
    {
        $geoFilter = [
            'geo_distance' => [
                'distance' => $location['radius'] ?? '10km',
                'location' => [
                    'lat' => $location['lat'],
                    'lon' => $location['lon']
                ]
            ]
        ];
        
        if (isset($query['bool'])) {
            $query['bool']['filter'] = $query['bool']['filter'] ?? [];
            $query['bool']['filter'][] = $geoFilter;
        } else {
            $query = [
                'bool' => [
                    'must' => [$query],
                    'filter' => [$geoFilter]
                ]
            ];
        }
        
        return $query;
    }
}