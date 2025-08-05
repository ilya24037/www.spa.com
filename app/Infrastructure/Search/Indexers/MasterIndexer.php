<?php

namespace App\Infrastructure\Search\Indexers;

use App\Domain\Master\Models\MasterProfile;
use App\Infrastructure\Search\ElasticsearchClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Индексатор мастеров для Elasticsearch
 * 
 * Отвечает за преобразование модели MasterProfile в документ Elasticsearch
 * и управление индексацией мастеров
 */
class MasterIndexer
{
    protected ElasticsearchClient $client;
    protected string $indexName = 'masters';
    
    public function __construct(ElasticsearchClient $client)
    {
        $this->client = $client;
    }

    /**
     * Создать индекс для мастеров
     */
    public function createIndex(): void
    {
        $settings = [
            'number_of_shards' => 2,
            'number_of_replicas' => 1,
            'max_result_window' => 10000,
            'analysis' => [
                'analyzer' => [
                    'master_analyzer' => [
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
                    'name_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => [
                            'lowercase',
                            'word_delimiter'
                        ]
                    ]
                ],
                'filter' => [
                    'synonym_filter' => [
                        'type' => 'synonym',
                        'synonyms' => [
                            'массажист,массажистка => массажист',
                            'спа мастер,spa мастер => спа-мастер',
                            'косметолог,эстетист => косметолог',
                            'специалист,мастер,профессионал => специалист'
                        ]
                    ]
                ]
            ]
        ];

        $mappings = [
            'properties' => [
                // Основная информация
                'id' => ['type' => 'integer'],
                'user_id' => ['type' => 'integer'],
                'name' => [
                    'type' => 'text',
                    'analyzer' => 'name_analyzer',
                    'fields' => [
                        'keyword' => ['type' => 'keyword'],
                        'autocomplete' => [
                            'type' => 'text',
                            'analyzer' => 'autocomplete_analyzer',
                            'search_analyzer' => 'autocomplete_search'
                        ]
                    ]
                ],
                'slug' => ['type' => 'keyword'],
                'about' => [
                    'type' => 'text',
                    'analyzer' => 'master_analyzer'
                ],
                'specialty' => [
                    'type' => 'text',
                    'analyzer' => 'master_analyzer',
                    'fields' => [
                        'keyword' => ['type' => 'keyword']
                    ]
                ],
                'specializations' => ['type' => 'keyword'],
                
                // Контактная информация
                'phone' => ['type' => 'keyword', 'index' => false],
                'email' => ['type' => 'keyword', 'index' => false],
                'website' => ['type' => 'keyword', 'index' => false],
                
                // Локация
                'city' => [
                    'type' => 'text',
                    'fields' => [
                        'keyword' => ['type' => 'keyword']
                    ]
                ],
                'region' => [
                    'type' => 'text',
                    'fields' => [
                        'keyword' => ['type' => 'keyword']
                    ]
                ],
                'metro_stations' => ['type' => 'keyword'],
                'address' => [
                    'type' => 'text',
                    'analyzer' => 'master_analyzer'
                ],
                'location' => ['type' => 'geo_point'],
                'work_radius' => ['type' => 'integer'],
                
                // Рейтинг и статистика
                'rating' => ['type' => 'float'],
                'reviews_count' => ['type' => 'integer'],
                'experience_years' => ['type' => 'integer'],
                'completed_orders' => ['type' => 'integer'],
                'repeat_clients_percent' => ['type' => 'float'],
                
                // Статус и флаги
                'is_active' => ['type' => 'boolean'],
                'is_verified' => ['type' => 'boolean'],
                'is_premium' => ['type' => 'boolean'],
                'is_online' => ['type' => 'boolean'],
                'verification_level' => ['type' => 'keyword'],
                
                // Услуги и цены
                'services' => [
                    'type' => 'nested',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'text', 'analyzer' => 'master_analyzer'],
                        'category' => ['type' => 'keyword'],
                        'price' => ['type' => 'integer'],
                        'duration' => ['type' => 'integer']
                    ]
                ],
                'price_min' => ['type' => 'integer'],
                'price_max' => ['type' => 'integer'],
                'average_price' => ['type' => 'integer'],
                
                // Расписание
                'working_hours' => [
                    'type' => 'object',
                    'properties' => [
                        'monday' => ['type' => 'keyword'],
                        'tuesday' => ['type' => 'keyword'],
                        'wednesday' => ['type' => 'keyword'],
                        'thursday' => ['type' => 'keyword'],
                        'friday' => ['type' => 'keyword'],
                        'saturday' => ['type' => 'keyword'],
                        'sunday' => ['type' => 'keyword']
                    ]
                ],
                'available_now' => ['type' => 'boolean'],
                'next_available_slot' => ['type' => 'date'],
                
                // Медиа
                'avatar_url' => ['type' => 'keyword', 'index' => false],
                'photos_count' => ['type' => 'integer'],
                'videos_count' => ['type' => 'integer'],
                'has_portfolio' => ['type' => 'boolean'],
                
                // Сертификаты и образование
                'education' => [
                    'type' => 'nested',
                    'properties' => [
                        'institution' => ['type' => 'text'],
                        'degree' => ['type' => 'keyword'],
                        'year' => ['type' => 'integer']
                    ]
                ],
                'certificates' => [
                    'type' => 'nested',
                    'properties' => [
                        'name' => ['type' => 'text'],
                        'issuer' => ['type' => 'text'],
                        'year' => ['type' => 'integer']
                    ]
                ],
                
                // Даты
                'created_at' => ['type' => 'date'],
                'updated_at' => ['type' => 'date'],
                'last_active_at' => ['type' => 'date'],
                'verified_at' => ['type' => 'date'],
                
                // SEO и мета-данные
                'meta_title' => ['type' => 'text', 'index' => false],
                'meta_description' => ['type' => 'text', 'index' => false],
                'meta_keywords' => ['type' => 'keyword'],
                
                // Дополнительные поля для поиска
                'tags' => ['type' => 'keyword'],
                'skills' => ['type' => 'keyword'],
                'languages' => ['type' => 'keyword'],
                'work_formats' => ['type' => 'keyword'],
                
                // Для ранжирования
                'profile_completeness' => ['type' => 'float'],
                'activity_score' => ['type' => 'float'],
                'quality_score' => ['type' => 'float'],
                'boost_score' => ['type' => 'float']
            ]
        ];

        $this->client->createIndex($this->indexName, $settings, $mappings);
        
        Log::info("Created Elasticsearch index for masters");
    }

    /**
     * Индексировать одного мастера
     */
    public function index(MasterProfile $master): void
    {
        $document = $this->transformToDocument($master);
        
        $this->client->index($this->indexName, $master->id, $document);
        
        Log::debug("Indexed master", ['id' => $master->id]);
    }

    /**
     * Массовая индексация мастеров
     */
    public function bulkIndex(Collection $masters): void
    {
        if ($masters->isEmpty()) {
            return;
        }

        $documents = [];
        
        foreach ($masters as $master) {
            $documents[$master->id] = $this->transformToDocument($master);
        }

        $response = $this->client->bulkIndex($this->indexName, $documents);
        
        if ($response['errors']) {
            Log::warning("Some masters failed to index", [
                'total' => $masters->count(),
                'errors' => $this->extractBulkErrors($response)
            ]);
        } else {
            Log::info("Bulk indexed masters", ['count' => $masters->count()]);
        }
    }

    /**
     * Обновить мастера в индексе
     */
    public function update(MasterProfile $master, array $fields = []): void
    {
        if (empty($fields)) {
            // Полное обновление документа
            $this->index($master);
            return;
        }

        // Частичное обновление
        $document = [];
        $fullDocument = $this->transformToDocument($master);
        
        foreach ($fields as $field) {
            if (isset($fullDocument[$field])) {
                $document[$field] = $fullDocument[$field];
            }
        }

        $this->client->update($this->indexName, $master->id, $document);
        
        Log::debug("Updated master", ['id' => $master->id, 'fields' => $fields]);
    }

    /**
     * Удалить мастера из индекса
     */
    public function delete(int $masterId): void
    {
        $this->client->delete($this->indexName, (string)$masterId);
        
        Log::debug("Deleted master from index", ['id' => $masterId]);
    }

    /**
     * Переиндексировать всех мастеров
     */
    public function reindexAll(int $batchSize = 1000): void
    {
        Log::info("Starting full reindexing of masters");
        
        $processedCount = 0;
        
        MasterProfile::query()
            ->where('is_active', true)
            ->with(['user', 'services', 'schedule', 'media', 'reviews'])
            ->chunk($batchSize, function ($masters) use (&$processedCount) {
                $this->bulkIndex($masters);
                $processedCount += $masters->count();
                
                Log::info("Reindexing progress", ['processed' => $processedCount]);
            });
            
        // Обновляем индекс для применения изменений
        $this->client->refresh($this->indexName);
        
        Log::info("Completed full reindexing", ['total' => $processedCount]);
    }

    /**
     * Синхронизировать с базой данных
     */
    public function syncWithDatabase(int $lastMinutes = 60): void
    {
        $since = now()->subMinutes($lastMinutes);
        
        // Обновленные мастера
        $updated = MasterProfile::query()
            ->where('updated_at', '>=', $since)
            ->where('is_active', true)
            ->with(['user', 'services', 'schedule'])
            ->get();
            
        if ($updated->isNotEmpty()) {
            $this->bulkIndex($updated);
            Log::info("Synced updated masters", ['count' => $updated->count()]);
        }
        
        // Деактивированные мастера
        $deactivated = MasterProfile::query()
            ->where('updated_at', '>=', $since)
            ->where('is_active', false)
            ->pluck('id');
            
        foreach ($deactivated as $id) {
            try {
                $this->delete($id);
            } catch (\Exception $e) {
                // Игнорируем ошибки удаления несуществующих документов
            }
        }
        
        if ($deactivated->isNotEmpty()) {
            Log::info("Removed inactive masters from index", ['count' => $deactivated->count()]);
        }
    }

    /**
     * Преобразовать модель в документ для индексации
     */
    protected function transformToDocument(MasterProfile $master): array
    {
        $master->load(['user', 'services', 'schedule', 'media', 'reviews']);
        
        return [
            // Основная информация
            'id' => $master->id,
            'user_id' => $master->user_id,
            'name' => $master->user->name,
            'slug' => $master->slug,
            'about' => strip_tags($master->about),
            'specialty' => $master->specialty,
            'specializations' => $this->extractSpecializations($master),
            
            // Контакты
            'phone' => $master->phone,
            'email' => $master->user->email,
            'website' => $master->website,
            
            // Локация
            'city' => $master->city,
            'region' => $master->region,
            'metro_stations' => $this->extractMetroStations($master),
            'address' => $master->address,
            'location' => $this->getGeoPoint($master),
            'work_radius' => $master->work_radius ?? 10,
            
            // Рейтинг и статистика
            'rating' => $master->rating ?? 0,
            'reviews_count' => $master->reviews_count ?? 0,
            'experience_years' => $master->experience_years ?? 0,
            'completed_orders' => $master->completed_orders ?? 0,
            'repeat_clients_percent' => $master->repeat_clients_percent ?? 0,
            
            // Статус
            'is_active' => $master->is_active,
            'is_verified' => $master->is_verified ?? false,
            'is_premium' => $master->is_premium ?? false,
            'is_online' => $this->isOnline($master),
            'verification_level' => $master->verification_level ?? 'none',
            
            // Услуги и цены
            'services' => $this->transformServices($master),
            'price_min' => $this->getMinPrice($master),
            'price_max' => $this->getMaxPrice($master),
            'average_price' => $this->getAveragePrice($master),
            
            // Расписание
            'working_hours' => $this->transformWorkingHours($master),
            'available_now' => $this->isAvailableNow($master),
            'next_available_slot' => $this->getNextAvailableSlot($master),
            
            // Медиа
            'avatar_url' => $master->user->avatar_url,
            'photos_count' => $master->media->where('type', 'image')->count(),
            'videos_count' => $master->media->where('type', 'video')->count(),
            'has_portfolio' => $master->media->isNotEmpty(),
            
            // Образование и сертификаты
            'education' => $this->transformEducation($master),
            'certificates' => $this->transformCertificates($master),
            
            // Даты
            'created_at' => $master->created_at->toIso8601String(),
            'updated_at' => $master->updated_at->toIso8601String(),
            'last_active_at' => $master->last_active_at?->toIso8601String(),
            'verified_at' => $master->verified_at?->toIso8601String(),
            
            // SEO
            'meta_title' => $master->meta_title,
            'meta_description' => $master->meta_description,
            'meta_keywords' => $this->extractKeywords($master),
            
            // Дополнительные поля
            'tags' => $this->extractTags($master),
            'skills' => $this->extractSkills($master),
            'languages' => $master->languages ?? ['русский'],
            'work_formats' => $this->extractWorkFormats($master),
            
            // Скоринг
            'profile_completeness' => $this->calculateProfileCompleteness($master),
            'activity_score' => $this->calculateActivityScore($master),
            'quality_score' => $this->calculateQualityScore($master),
            'boost_score' => $this->calculateBoostScore($master)
        ];
    }

    /**
     * Извлечь специализации
     */
    protected function extractSpecializations(MasterProfile $master): array
    {
        $specializations = [];
        
        if ($master->specializations) {
            $specializations = is_array($master->specializations) 
                ? $master->specializations 
                : json_decode($master->specializations, true) ?? [];
        }
        
        if ($master->specialty && !in_array($master->specialty, $specializations)) {
            $specializations[] = $master->specialty;
        }
        
        return $specializations;
    }

    /**
     * Извлечь станции метро
     */
    protected function extractMetroStations(MasterProfile $master): array
    {
        if ($master->metro_stations) {
            return is_array($master->metro_stations)
                ? $master->metro_stations
                : json_decode($master->metro_stations, true) ?? [];
        }
        
        return [];
    }

    /**
     * Получить гео-точку
     */
    protected function getGeoPoint(MasterProfile $master): ?array
    {
        if ($master->latitude && $master->longitude) {
            return [
                'lat' => $master->latitude,
                'lon' => $master->longitude
            ];
        }
        
        return null;
    }

    /**
     * Проверить онлайн статус
     */
    protected function isOnline(MasterProfile $master): bool
    {
        return $master->last_active_at && 
               $master->last_active_at->isAfter(now()->subMinutes(15));
    }

    /**
     * Преобразовать услуги
     */
    protected function transformServices(MasterProfile $master): array
    {
        return $master->services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'category' => $service->category->name ?? null,
                'price' => $service->pivot->price ?? $service->price,
                'duration' => $service->pivot->duration ?? $service->duration
            ];
        })->toArray();
    }

    /**
     * Получить минимальную цену
     */
    protected function getMinPrice(MasterProfile $master): int
    {
        return $master->services->min('pivot.price') ?? 0;
    }

    /**
     * Получить максимальную цену
     */
    protected function getMaxPrice(MasterProfile $master): int
    {
        return $master->services->max('pivot.price') ?? 0;
    }

    /**
     * Получить среднюю цену
     */
    protected function getAveragePrice(MasterProfile $master): int
    {
        $avg = $master->services->avg('pivot.price');
        return $avg ? (int)round($avg) : 0;
    }

    /**
     * Преобразовать рабочие часы
     */
    protected function transformWorkingHours(MasterProfile $master): array
    {
        if (!$master->schedule) {
            return [];
        }
        
        $hours = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        foreach ($days as $day) {
            if ($master->schedule->{$day . '_enabled'}) {
                $hours[$day] = sprintf(
                    '%s-%s',
                    $master->schedule->{$day . '_from'},
                    $master->schedule->{$day . '_to'}
                );
            }
        }
        
        return $hours;
    }

    /**
     * Проверить доступность сейчас
     */
    protected function isAvailableNow(MasterProfile $master): bool
    {
        if (!$master->schedule) {
            return false;
        }
        
        $now = now();
        $dayOfWeek = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');
        
        if ($master->schedule->{$dayOfWeek . '_enabled'}) {
            $from = $master->schedule->{$dayOfWeek . '_from'};
            $to = $master->schedule->{$dayOfWeek . '_to'};
            
            return $currentTime >= $from && $currentTime <= $to;
        }
        
        return false;
    }

    /**
     * Получить следующий доступный слот
     */
    protected function getNextAvailableSlot(MasterProfile $master): ?string
    {
        // Здесь должна быть логика поиска следующего доступного слота
        // На основе расписания и существующих бронирований
        return null;
    }

    /**
     * Преобразовать образование
     */
    protected function transformEducation(MasterProfile $master): array
    {
        if ($master->education) {
            $education = is_array($master->education)
                ? $master->education
                : json_decode($master->education, true) ?? [];
                
            return array_map(function ($edu) {
                return [
                    'institution' => $edu['institution'] ?? null,
                    'degree' => $edu['degree'] ?? null,
                    'year' => $edu['year'] ?? null
                ];
            }, $education);
        }
        
        return [];
    }

    /**
     * Преобразовать сертификаты
     */
    protected function transformCertificates(MasterProfile $master): array
    {
        if ($master->certificates) {
            $certificates = is_array($master->certificates)
                ? $master->certificates
                : json_decode($master->certificates, true) ?? [];
                
            return array_map(function ($cert) {
                return [
                    'name' => $cert['name'] ?? null,
                    'issuer' => $cert['issuer'] ?? null,
                    'year' => $cert['year'] ?? null
                ];
            }, $certificates);
        }
        
        return [];
    }

    /**
     * Извлечь ключевые слова
     */
    protected function extractKeywords(MasterProfile $master): array
    {
        $keywords = [];
        
        if ($master->meta_keywords) {
            $keywords = array_map('trim', explode(',', $master->meta_keywords));
        }
        
        return $keywords;
    }

    /**
     * Извлечь теги
     */
    protected function extractTags(MasterProfile $master): array
    {
        $tags = [];
        
        // Из специальности
        if ($master->specialty) {
            $tags[] = $master->specialty;
        }
        
        // Из услуг
        foreach ($master->services as $service) {
            $tags[] = $service->name;
        }
        
        // Из навыков
        $tags = array_merge($tags, $this->extractSkills($master));
        
        return array_unique($tags);
    }

    /**
     * Извлечь навыки
     */
    protected function extractSkills(MasterProfile $master): array
    {
        if ($master->skills) {
            return is_array($master->skills)
                ? $master->skills
                : json_decode($master->skills, true) ?? [];
        }
        
        return [];
    }

    /**
     * Извлечь форматы работы
     */
    protected function extractWorkFormats(MasterProfile $master): array
    {
        $formats = [];
        
        if ($master->work_at_salon) {
            $formats[] = 'salon';
        }
        
        if ($master->work_at_home) {
            $formats[] = 'home';
        }
        
        if ($master->work_on_location) {
            $formats[] = 'mobile';
        }
        
        if ($master->work_online) {
            $formats[] = 'online';
        }
        
        return $formats;
    }

    /**
     * Вычислить полноту профиля
     */
    protected function calculateProfileCompleteness(MasterProfile $master): float
    {
        $score = 0;
        $fields = [
            'about' => 15,
            'phone' => 10,
            'services' => 20,
            'schedule' => 15,
            'media' => 15,
            'education' => 10,
            'certificates' => 10,
            'location' => 5
        ];
        
        if ($master->about && strlen($master->about) > 100) {
            $score += $fields['about'];
        }
        
        if ($master->phone) {
            $score += $fields['phone'];
        }
        
        if ($master->services->count() >= 3) {
            $score += $fields['services'];
        }
        
        if ($master->schedule) {
            $score += $fields['schedule'];
        }
        
        if ($master->media->count() >= 3) {
            $score += $fields['media'];
        }
        
        if ($master->education) {
            $score += $fields['education'];
        }
        
        if ($master->certificates) {
            $score += $fields['certificates'];
        }
        
        if ($master->latitude && $master->longitude) {
            $score += $fields['location'];
        }
        
        return round($score / 100, 2);
    }

    /**
     * Вычислить скор активности
     */
    protected function calculateActivityScore(MasterProfile $master): float
    {
        $score = 0;
        
        // Последняя активность
        if ($master->last_active_at) {
            if ($master->last_active_at->isAfter(now()->subHours(24))) {
                $score += 0.4;
            } elseif ($master->last_active_at->isAfter(now()->subDays(7))) {
                $score += 0.2;
            } elseif ($master->last_active_at->isAfter(now()->subDays(30))) {
                $score += 0.1;
            }
        }
        
        // Обновление профиля
        if ($master->updated_at->isAfter(now()->subDays(7))) {
            $score += 0.3;
        } elseif ($master->updated_at->isAfter(now()->subDays(30))) {
            $score += 0.1;
        }
        
        // Количество выполненных заказов за месяц
        $recentOrders = $master->bookings()
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays(30))
            ->count();
            
        if ($recentOrders >= 20) {
            $score += 0.3;
        } elseif ($recentOrders >= 10) {
            $score += 0.2;
        } elseif ($recentOrders >= 5) {
            $score += 0.1;
        }
        
        return round($score, 2);
    }

    /**
     * Вычислить скор качества
     */
    protected function calculateQualityScore(MasterProfile $master): float
    {
        $score = 0;
        
        // Рейтинг
        if ($master->rating >= 4.8) {
            $score += 0.4;
        } elseif ($master->rating >= 4.5) {
            $score += 0.3;
        } elseif ($master->rating >= 4.0) {
            $score += 0.2;
        } elseif ($master->rating >= 3.5) {
            $score += 0.1;
        }
        
        // Количество отзывов
        if ($master->reviews_count >= 100) {
            $score += 0.3;
        } elseif ($master->reviews_count >= 50) {
            $score += 0.2;
        } elseif ($master->reviews_count >= 20) {
            $score += 0.1;
        }
        
        // Процент повторных клиентов
        if ($master->repeat_clients_percent >= 70) {
            $score += 0.2;
        } elseif ($master->repeat_clients_percent >= 50) {
            $score += 0.1;
        }
        
        // Верификация
        if ($master->is_verified) {
            $score += 0.1;
        }
        
        return round($score, 2);
    }

    /**
     * Вычислить boost score
     */
    protected function calculateBoostScore(MasterProfile $master): float
    {
        $score = 1.0;
        
        // Премиум статус
        if ($master->is_premium) {
            $score += 0.5;
        }
        
        // Верификация
        if ($master->is_verified) {
            $score += 0.3;
        }
        
        // Полнота профиля
        $score += $this->calculateProfileCompleteness($master) * 0.5;
        
        // Качество
        $score += $this->calculateQualityScore($master) * 0.8;
        
        // Активность
        $score += $this->calculateActivityScore($master) * 0.4;
        
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
}