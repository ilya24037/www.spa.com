<?php

namespace App\Infrastructure\Search\Indexers;

/**
 * Настройки и маппинги индекса для объявлений
 */
class AdIndexMappings
{
    /**
     * Получить настройки индекса
     */
    public function getSettings(): array
    {
        return [
            'number_of_shards' => 2,
            'number_of_replicas' => 1,
            'max_result_window' => 10000,
            'analysis' => [
                'analyzer' => [
                    'ad_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => [
                            'lowercase',
                            'russian_stop',
                            'russian_stemmer',
                            'word_delimiter',
                            'synonym_filter'
                        ]
                    ],
                    'keyword_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'keyword',
                        'filter' => ['lowercase']
                    ],
                    'search_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => [
                            'lowercase',
                            'russian_stop',
                            'russian_stemmer'
                        ]
                    ]
                ],
                'filter' => [
                    'synonym_filter' => [
                        'type' => 'synonym',
                        'synonyms' => [
                            'массаж,масаж => массаж',
                            'спа,spa => спа',
                            'релакс,релаксация => релакс',
                            'тайский,thai => тайский',
                            'классический,шведский => классический'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Получить маппинги полей
     */
    public function getMappings(): array
    {
        return [
            'properties' => [
                // Основная информация
                'id' => ['type' => 'integer'],
                'title' => [
                    'type' => 'text',
                    'analyzer' => 'ad_analyzer',
                    'search_analyzer' => 'search_analyzer',
                    'fields' => [
                        'keyword' => [
                            'type' => 'keyword',
                            'ignore_above' => 256
                        ],
                        'autocomplete' => [
                            'type' => 'text',
                            'analyzer' => 'autocomplete_analyzer',
                            'search_analyzer' => 'autocomplete_search'
                        ]
                    ]
                ],
                'description' => [
                    'type' => 'text',
                    'analyzer' => 'ad_analyzer',
                    'search_analyzer' => 'search_analyzer'
                ],
                'specialty' => [
                    'type' => 'text',
                    'analyzer' => 'ad_analyzer',
                    'fields' => [
                        'keyword' => ['type' => 'keyword']
                    ]
                ],
                'additional_features' => [
                    'type' => 'text',
                    'analyzer' => 'ad_analyzer'
                ],
                
                // Цена и валюта
                'price' => ['type' => 'integer'],
                'price_currency' => ['type' => 'keyword'],
                'price_type' => ['type' => 'keyword'],
                
                // Локация
                'city' => [
                    'type' => 'text',
                    'analyzer' => 'keyword_analyzer',
                    'fields' => [
                        'keyword' => ['type' => 'keyword']
                    ]
                ],
                'region' => [
                    'type' => 'text',
                    'analyzer' => 'keyword_analyzer',
                    'fields' => [
                        'keyword' => ['type' => 'keyword']
                    ]
                ],
                'metro_station' => [
                    'type' => 'text',
                    'analyzer' => 'keyword_analyzer',
                    'fields' => [
                        'keyword' => ['type' => 'keyword']
                    ]
                ],
                'address' => [
                    'type' => 'text',
                    'analyzer' => 'ad_analyzer'
                ],
                'location' => [
                    'type' => 'geo_point'
                ],
                
                // Информация о мастере
                'master' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => [
                            'type' => 'text',
                            'analyzer' => 'ad_analyzer',
                            'fields' => [
                                'keyword' => ['type' => 'keyword']
                            ]
                        ],
                        'rating' => ['type' => 'float'],
                        'reviews_count' => ['type' => 'integer'],
                        'experience_years' => ['type' => 'integer'],
                        'is_verified' => ['type' => 'boolean'],
                        'is_premium' => ['type' => 'boolean'],
                        'avatar_url' => ['type' => 'keyword', 'index' => false]
                    ]
                ],
                
                // Статус и флаги
                'status' => ['type' => 'keyword'],
                'is_published' => ['type' => 'boolean'],
                'is_available' => ['type' => 'boolean'],
                'is_premium' => ['type' => 'boolean'],
                'ad_type' => ['type' => 'keyword'],
                'work_format' => ['type' => 'keyword'],
                
                // Медиа
                'media_count' => ['type' => 'integer'],
                'has_photos' => ['type' => 'boolean'],
                'has_videos' => ['type' => 'boolean'],
                'media_urls' => ['type' => 'keyword', 'index' => false],
                
                // Даты
                'created_at' => ['type' => 'date'],
                'updated_at' => ['type' => 'date'],
                'published_at' => ['type' => 'date'],
                
                // Статистика
                'views_count' => ['type' => 'integer'],
                'favorites_count' => ['type' => 'integer'],
                'bookings_count' => ['type' => 'integer'],
                
                // Для поиска и фильтрации
                'tags' => ['type' => 'keyword'],
                'categories' => ['type' => 'keyword'],
                'services' => ['type' => 'keyword'],
                
                // Для сортировки
                'boost_score' => ['type' => 'float'],
                'relevance_score' => ['type' => 'float']
            ]
        ];
    }
}