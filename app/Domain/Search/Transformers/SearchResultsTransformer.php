<?php

namespace App\Domain\Search\Transformers;

use App\Domain\Search\Enums\SearchType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

/**
 * Трансформер результатов поиска
 */
class SearchResultsTransformer
{
    protected SearchType $searchType;

    public function __construct(SearchType $searchType)
    {
        $this->searchType = $searchType;
    }

    /**
     * Трансформировать результаты Elasticsearch в пагинатор
     */
    public function transformResults(array $response, int $page, int $perPage): LengthAwarePaginator
    {
        $hits = $response['hits']['hits'] ?? [];
        $total = $response['hits']['total']['value'] ?? 0;
        $aggregations = $response['aggregations'] ?? [];

        $items = collect($hits)->map(function ($hit) {
            return $this->transformHit($hit);
        });

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );

        // Добавляем аггрегации как мета-информацию
        $paginator->appends('aggregations', $this->transformAggregations($aggregations));
        
        return $paginator;
    }

    /**
     * Трансформировать отдельный результат
     */
    protected function transformHit(array $hit): array
    {
        $source = $hit['_source'];
        $score = $hit['_score'] ?? 0;
        
        // Базовая структура результата
        $result = [
            'id' => $hit['_id'],
            'score' => $score,
            'type' => $this->searchType->value,
            'data' => $source
        ];

        // Добавляем highlights если есть
        if (isset($hit['highlight'])) {
            $result['highlights'] = $hit['highlight'];
        }

        // Специфичная обработка в зависимости от типа
        return match($this->searchType) {
            SearchType::MASTERS => $this->transformMasterResult($result),
            SearchType::ADS => $this->transformAdResult($result),
            default => $result
        };
    }

    /**
     * Трансформировать результат мастера
     */
    protected function transformMasterResult(array $result): array
    {
        $data = $result['data'];
        
        return [
            'id' => $result['id'],
            'type' => 'master',
            'score' => $result['score'],
            'name' => $data['name'] ?? '',
            'slug' => $data['slug'] ?? '',
            'specialty' => $data['specialty'] ?? '',
            'about' => $data['about'] ?? '',
            'rating' => $data['rating'] ?? 0,
            'reviews_count' => $data['reviews_count'] ?? 0,
            'price_min' => $data['price_min'] ?? 0,
            'price_max' => $data['price_max'] ?? 0,
            'location' => [
                'city' => $data['city'] ?? '',
                'district' => $data['district'] ?? '',
                'address' => $data['address'] ?? '',
                'coordinates' => $data['location'] ?? null
            ],
            'avatar_url' => $data['avatar_url'] ?? null,
            'is_online' => $data['is_online'] ?? false,
            'is_verified' => $data['is_verified'] ?? false,
            'is_premium' => $data['is_premium'] ?? false,
            'services' => $data['services'] ?? [],
            'specializations' => $data['specializations'] ?? [],
            'metro_stations' => $data['metro_stations'] ?? [],
            'working_hours' => $data['working_hours'] ?? [],
            'available_now' => $data['available_now'] ?? false,
            'next_available_slot' => $data['next_available_slot'] ?? null,
            'highlights' => $result['highlights'] ?? [],
            'scores' => [
                'profile_completeness' => $data['profile_completeness'] ?? 0,
                'activity_score' => $data['activity_score'] ?? 0,
                'quality_score' => $data['quality_score'] ?? 0,
                'boost_score' => $data['boost_score'] ?? 0,
                'overall_score' => $data['overall_score'] ?? 0
            ]
        ];
    }

    /**
     * Трансформировать результат объявления
     */
    protected function transformAdResult(array $result): array
    {
        $data = $result['data'];
        
        return [
            'id' => $result['id'],
            'type' => 'ad',
            'score' => $result['score'],
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'category' => $data['category'] ?? '',
            'subcategory' => $data['subcategory'] ?? '',
            'price_from' => $data['price_from'] ?? null,
            'price_to' => $data['price_to'] ?? null,
            'price_fixed' => $data['price_fixed'] ?? null,
            'price_currency' => $data['price_currency'] ?? 'RUB',
            'location' => [
                'address' => $data['address'] ?? '',
                'coordinates' => $data['location'] ?? null,
                'service_location' => $data['service_location'] ?? ''
            ],
            'photos' => $data['photos'] ?? [],
            'video' => $data['video'] ?? null,
            'master' => [
                'id' => $data['user_id'] ?? null,
                'name' => $data['master_name'] ?? '',
                'rating' => $data['master_rating'] ?? 0,
                'reviews_count' => $data['master_reviews_count'] ?? 0,
                'is_verified' => $data['master_verified'] ?? false
            ],
            'metro_stations' => $data['metro_stations'] ?? [],
            'working_hours' => $data['working_hours'] ?? [],
            'created_at' => $data['created_at'] ?? null,
            'updated_at' => $data['updated_at'] ?? null,
            'highlights' => $result['highlights'] ?? []
        ];
    }

    /**
     * Трансформировать аггрегации
     */
    protected function transformAggregations(array $aggregations): array
    {
        $transformed = [];

        foreach ($aggregations as $name => $aggregation) {
            switch ($name) {
                case 'categories':
                    $transformed['categories'] = $this->transformTermsAggregation($aggregation);
                    break;

                case 'price_ranges':
                    $transformed['price_ranges'] = $this->transformRangeAggregation($aggregation);
                    break;

                case 'ratings':
                    $transformed['ratings'] = $this->transformRangeAggregation($aggregation);
                    break;

                case 'specializations':
                    $transformed['specializations'] = $this->transformTermsAggregation($aggregation);
                    break;

                default:
                    $transformed[$name] = $aggregation;
            }
        }

        return $transformed;
    }

    /**
     * Трансформировать terms аггрегацию
     */
    protected function transformTermsAggregation(array $aggregation): array
    {
        $buckets = $aggregation['buckets'] ?? [];
        
        return collect($buckets)->map(function ($bucket) {
            return [
                'key' => $bucket['key'],
                'count' => $bucket['doc_count'],
                'label' => $this->getDisplayLabel($bucket['key'])
            ];
        })->toArray();
    }

    /**
     * Трансформировать range аггрегацию
     */
    protected function transformRangeAggregation(array $aggregation): array
    {
        $buckets = $aggregation['buckets'] ?? [];
        
        return collect($buckets)->map(function ($bucket) {
            $from = $bucket['from'] ?? null;
            $to = $bucket['to'] ?? null;
            
            return [
                'from' => $from,
                'to' => $to,
                'count' => $bucket['doc_count'],
                'label' => $this->getRangeLabel($from, $to)
            ];
        })->toArray();
    }

    /**
     * Получить человекочитаемый лейбл
     */
    protected function getDisplayLabel(string $key): string
    {
        // Здесь можно добавить маппинг ключей на человекочитаемые названия
        $labels = [
            'massage' => 'Массаж',
            'cosmetology' => 'Косметология',
            'manicure' => 'Маникюр',
            'pedicure' => 'Педикюр',
            'hair' => 'Парикмахерские услуги',
            'makeup' => 'Макияж',
            'spa' => 'СПА процедуры'
        ];

        return $labels[$key] ?? ucfirst($key);
    }

    /**
     * Получить лейбл для диапазона
     */
    protected function getRangeLabel(?float $from, ?float $to): string
    {
        if ($from === null && $to !== null) {
            return "до {$to}";
        }
        
        if ($from !== null && $to === null) {
            return "от {$from}";
        }
        
        if ($from !== null && $to !== null) {
            return "{$from} - {$to}";
        }
        
        return 'Все';
    }
}