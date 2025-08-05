<?php

namespace App\Infrastructure\Search\Indexers;

use App\Domain\Ad\Models\Ad;
use App\Infrastructure\Search\ElasticsearchClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Индексатор объявлений для Elasticsearch
 * 
 * Отвечает за преобразование модели Ad в документ Elasticsearch
 * и управление индексацией объявлений
 */
class AdIndexer
{
    protected ElasticsearchClient $client;
    protected string $indexName = 'ads';
    
    public function __construct(ElasticsearchClient $client)
    {
        $this->client = $client;
    }

    /**
     * Создать индекс для объявлений
     */
    public function createIndex(): void
    {
        $settings = [
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

        $mappings = [
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

        $this->client->createIndex($this->indexName, $settings, $mappings);
        
        Log::info("Created Elasticsearch index for ads");
    }

    /**
     * Индексировать одно объявление
     */
    public function index(Ad $ad): void
    {
        $document = $this->transformToDocument($ad);
        
        $this->client->index($this->indexName, $ad->id, $document);
        
        Log::debug("Indexed ad", ['id' => $ad->id]);
    }

    /**
     * Массовая индексация объявлений
     */
    public function bulkIndex(Collection $ads): void
    {
        if ($ads->isEmpty()) {
            return;
        }

        $documents = [];
        
        foreach ($ads as $ad) {
            $documents[$ad->id] = $this->transformToDocument($ad);
        }

        $response = $this->client->bulkIndex($this->indexName, $documents);
        
        if ($response['errors']) {
            Log::warning("Some ads failed to index", [
                'total' => $ads->count(),
                'errors' => $this->extractBulkErrors($response)
            ]);
        } else {
            Log::info("Bulk indexed ads", ['count' => $ads->count()]);
        }
    }

    /**
     * Обновить объявление в индексе
     */
    public function update(Ad $ad, array $fields = []): void
    {
        if (empty($fields)) {
            // Полное обновление документа
            $this->index($ad);
            return;
        }

        // Частичное обновление
        $document = [];
        $fullDocument = $this->transformToDocument($ad);
        
        foreach ($fields as $field) {
            if (isset($fullDocument[$field])) {
                $document[$field] = $fullDocument[$field];
            }
        }

        $this->client->update($this->indexName, $ad->id, $document);
        
        Log::debug("Updated ad", ['id' => $ad->id, 'fields' => $fields]);
    }

    /**
     * Удалить объявление из индекса
     */
    public function delete(int $adId): void
    {
        $this->client->delete($this->indexName, (string)$adId);
        
        Log::debug("Deleted ad from index", ['id' => $adId]);
    }

    /**
     * Переиндексировать все объявления
     */
    public function reindexAll(int $batchSize = 1000): void
    {
        Log::info("Starting full reindexing of ads");
        
        $processedCount = 0;
        
        Ad::query()
            ->where('status', 'active')
            ->where('is_published', true)
            ->with(['user', 'media'])
            ->chunk($batchSize, function ($ads) use (&$processedCount) {
                $this->bulkIndex($ads);
                $processedCount += $ads->count();
                
                Log::info("Reindexing progress", ['processed' => $processedCount]);
            });
            
        // Обновляем индекс для применения изменений
        $this->client->refresh($this->indexName);
        
        Log::info("Completed full reindexing", ['total' => $processedCount]);
    }

    /**
     * Переиндексировать объявления по условию
     */
    public function reindexWhere(array $conditions, int $batchSize = 1000): void
    {
        $query = Ad::query();
        
        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }
        
        $processedCount = 0;
        
        $query->with(['user', 'media'])
            ->chunk($batchSize, function ($ads) use (&$processedCount) {
                $this->bulkIndex($ads);
                $processedCount += $ads->count();
            });
            
        Log::info("Reindexed ads by condition", [
            'conditions' => $conditions,
            'total' => $processedCount
        ]);
    }

    /**
     * Синхронизировать с базой данных
     */
    public function syncWithDatabase(int $lastMinutes = 60): void
    {
        $since = now()->subMinutes($lastMinutes);
        
        // Обновленные объявления
        $updated = Ad::query()
            ->where('updated_at', '>=', $since)
            ->where('status', 'active')
            ->where('is_published', true)
            ->with(['user', 'media'])
            ->get();
            
        if ($updated->isNotEmpty()) {
            $this->bulkIndex($updated);
            Log::info("Synced updated ads", ['count' => $updated->count()]);
        }
        
        // Удаленные или деактивированные объявления
        $deleted = Ad::query()
            ->where('updated_at', '>=', $since)
            ->where(function ($query) {
                $query->where('status', '!=', 'active')
                    ->orWhere('is_published', false);
            })
            ->pluck('id');
            
        foreach ($deleted as $id) {
            try {
                $this->delete($id);
            } catch (\Exception $e) {
                // Игнорируем ошибки удаления несуществующих документов
            }
        }
        
        if ($deleted->isNotEmpty()) {
            Log::info("Removed inactive ads from index", ['count' => $deleted->count()]);
        }
    }

    /**
     * Преобразовать модель в документ для индексации
     */
    protected function transformToDocument(Ad $ad): array
    {
        $ad->load(['user', 'media']);
        
        return [
            // Основная информация
            'id' => $ad->id,
            'title' => $ad->title,
            'description' => strip_tags($ad->description),
            'specialty' => $ad->specialty,
            'additional_features' => $ad->additional_features,
            
            // Цена
            'price' => $ad->price,
            'price_currency' => $ad->price_currency ?? 'RUB',
            'price_type' => $ad->price_type ?? 'fixed',
            
            // Локация
            'city' => $ad->city,
            'region' => $ad->region,
            'metro_station' => $ad->metro_station,
            'address' => $ad->address,
            'location' => $this->getGeoPoint($ad),
            
            // Информация о мастере
            'master' => [
                'id' => $ad->user->id,
                'name' => $ad->user->name,
                'rating' => $ad->user->rating ?? 0,
                'reviews_count' => $ad->user->reviews_count ?? 0,
                'experience_years' => $ad->user->experience_years ?? 0,
                'is_verified' => $ad->user->is_verified ?? false,
                'is_premium' => $ad->user->is_premium ?? false,
                'avatar_url' => $ad->user->avatar_url
            ],
            
            // Статус и флаги
            'status' => $ad->status,
            'is_published' => $ad->is_published,
            'is_available' => $ad->is_available ?? true,
            'is_premium' => $ad->is_premium ?? false,
            'ad_type' => $ad->ad_type ?? 'standard',
            'work_format' => $ad->work_format,
            
            // Медиа
            'media_count' => $ad->media->count(),
            'has_photos' => $ad->media->where('type', 'image')->isNotEmpty(),
            'has_videos' => $ad->media->where('type', 'video')->isNotEmpty(),
            'media_urls' => $ad->media->pluck('url')->toArray(),
            
            // Даты
            'created_at' => $ad->created_at->toIso8601String(),
            'updated_at' => $ad->updated_at->toIso8601String(),
            'published_at' => $ad->published_at?->toIso8601String(),
            
            // Статистика
            'views_count' => $ad->views_count ?? 0,
            'favorites_count' => $ad->favorites_count ?? 0,
            'bookings_count' => $ad->bookings_count ?? 0,
            
            // Дополнительные поля для поиска
            'tags' => $this->extractTags($ad),
            'categories' => $this->extractCategories($ad),
            'services' => $this->extractServices($ad),
            
            // Для ранжирования
            'boost_score' => $this->calculateBoostScore($ad),
            'relevance_score' => 0 // Будет вычисляться при поиске
        ];
    }

    /**
     * Получить гео-точку для объявления
     */
    protected function getGeoPoint(Ad $ad): ?array
    {
        if ($ad->latitude && $ad->longitude) {
            return [
                'lat' => $ad->latitude,
                'lon' => $ad->longitude
            ];
        }
        
        return null;
    }

    /**
     * Извлечь теги из объявления
     */
    protected function extractTags(Ad $ad): array
    {
        $tags = [];
        
        // Извлекаем теги из описания и дополнительных функций
        if ($ad->tags) {
            $tags = array_merge($tags, $ad->tags);
        }
        
        // Добавляем специальность как тег
        if ($ad->specialty) {
            $tags[] = $ad->specialty;
        }
        
        // Добавляем формат работы
        if ($ad->work_format) {
            $tags[] = $ad->work_format;
        }
        
        return array_unique($tags);
    }

    /**
     * Извлечь категории
     */
    protected function extractCategories(Ad $ad): array
    {
        $categories = [];
        
        if ($ad->category_id) {
            $categories[] = $ad->category_id;
        }
        
        if ($ad->subcategory_id) {
            $categories[] = $ad->subcategory_id;
        }
        
        return $categories;
    }

    /**
     * Извлечь услуги
     */
    protected function extractServices(Ad $ad): array
    {
        if ($ad->services) {
            return $ad->services->pluck('id')->toArray();
        }
        
        return [];
    }

    /**
     * Вычислить boost score для ранжирования
     */
    protected function calculateBoostScore(Ad $ad): float
    {
        $score = 1.0;
        
        // Премиум объявления
        if ($ad->is_premium) {
            $score += 0.5;
        }
        
        // Верифицированный мастер
        if ($ad->user->is_verified) {
            $score += 0.3;
        }
        
        // Рейтинг мастера
        if ($ad->user->rating >= 4.5) {
            $score += 0.4;
        } elseif ($ad->user->rating >= 4.0) {
            $score += 0.2;
        }
        
        // Количество отзывов
        if ($ad->user->reviews_count >= 50) {
            $score += 0.3;
        } elseif ($ad->user->reviews_count >= 20) {
            $score += 0.2;
        } elseif ($ad->user->reviews_count >= 10) {
            $score += 0.1;
        }
        
        // Наличие медиа
        if ($ad->media->count() >= 5) {
            $score += 0.2;
        } elseif ($ad->media->count() >= 3) {
            $score += 0.1;
        }
        
        // Полнота профиля
        if ($ad->description && strlen($ad->description) > 200) {
            $score += 0.1;
        }
        
        // Активность
        if ($ad->updated_at->isAfter(now()->subDays(7))) {
            $score += 0.2;
        } elseif ($ad->updated_at->isAfter(now()->subDays(30))) {
            $score += 0.1;
        }
        
        return round($score, 2);
    }

    /**
     * Извлечь ошибки из bulk ответа
     */
    protected function extractBulkErrors(array $response): array
    {
        $errors = [];
        
        foreach ($response['items'] as $item) {
            if (isset($item['index']['error'])) {
                $errors[] = [
                    'id' => $item['index']['_id'],
                    'error' => $item['index']['error']['reason'] ?? 'Unknown error'
                ];
            }
        }
        
        return $errors;
    }

    /**
     * Обновить boost score для всех объявлений
     */
    public function updateBoostScores(): void
    {
        Log::info("Updating boost scores for all ads");
        
        Ad::query()
            ->where('status', 'active')
            ->where('is_published', true)
            ->with(['user', 'media'])
            ->chunk(1000, function ($ads) {
                foreach ($ads as $ad) {
                    $boostScore = $this->calculateBoostScore($ad);
                    
                    $this->client->update($this->indexName, $ad->id, [
                        'boost_score' => $boostScore
                    ]);
                }
            });
            
        Log::info("Completed updating boost scores");
    }
}