<?php

namespace App\Infrastructure\Cache;

/**
 * Утилита для оптимизации запросов и устранения N+1 проблем
 */
class QueryOptimizer
{
    /**
     * Рекомендованные загрузки связей для объявлений
     */
    public static function getAdEagerLoads(): array
    {
        return [
            'basic' => ['user:id,name,email'],
            'detailed' => [
                'user:id,name,email',
                'masterProfile:id,user_id,display_name,avatar,rating',
                'photos:id,ad_id,path,is_main',
                'services:id,name,category_id'
            ],
            'full' => [
                'user:id,name,email',
                'masterProfile:id,user_id,display_name,avatar,rating',
                'photos:id,ad_id,path,is_main,order',
                'videos:id,ad_id,path,thumbnail,duration',
                'services:id,name,category_id,price_from,price_to',
                'favorites:id,user_id',
                'reviews' => function($query) {
                    $query->latest()->limit(3)->with('user:id,name');
                }
            ]
        ];
    }

    /**
     * Рекомендованные загрузки связей для мастеров
     */
    public static function getMasterEagerLoads(): array
    {
        return [
            'basic' => ['user:id,name,email'],
            'detailed' => [
                'user:id,name,email',
                'photos:id,master_profile_id,path,is_main',
                'services:id,name,category_id,price_from',
                'schedule' => function($query) {
                    $query->where('date', '>=', now()->format('Y-m-d'));
                }
            ],
            'full' => [
                'user:id,name,email',
                'photos:id,master_profile_id,path,is_main,order',
                'videos:id,master_profile_id,path,thumbnail',
                'services:id,name,category_id,price_from,price_to,duration',
                'schedule' => function($query) {
                    $query->where('date', '>=', now()->format('Y-m-d'))
                          ->orderBy('date')
                          ->limit(30);
                },
                'reviews' => function($query) {
                    $query->latest()
                          ->limit(5)
                          ->with('user:id,name');
                },
                'ads' => function($query) {
                    $query->where('status', 'active')
                          ->latest()
                          ->limit(10);
                }
            ]
        ];
    }

    /**
     * Рекомендованные загрузки связей для бронирований
     */
    public static function getBookingEagerLoads(): array
    {
        return [
            'basic' => [
                'client:id,name,email',
                'master:id,name,email',
                'service:id,name,duration,price'
            ],
            'detailed' => [
                'client:id,name,email',
                'master:id,name,email',
                'masterProfile:id,user_id,display_name,avatar',
                'service:id,name,category_id,duration,price',
                'payments:id,booking_id,amount,status,payment_method'
            ],
            'full' => [
                'client:id,name,email',
                'master:id,name,email',
                'masterProfile:id,user_id,display_name,avatar,phone',
                'service:id,name,category_id,duration,price,description',
                'payments:id,booking_id,amount,status,payment_method,created_at',
                'review:id,rating,comment,created_at'
            ]
        ];
    }

    /**
     * Оптимизация запроса с автоматическим определением нужных связей
     */
    public static function optimizeQuery($query, string $model, string $level = 'detailed')
    {
        switch ($model) {
            case 'Ad':
                $eagerLoads = self::getAdEagerLoads()[$level] ?? [];
                break;
            case 'Master':
                $eagerLoads = self::getMasterEagerLoads()[$level] ?? [];
                break;
            case 'Booking':
                $eagerLoads = self::getBookingEagerLoads()[$level] ?? [];
                break;
            default:
                return $query;
        }

        return $query->with($eagerLoads);
    }

    /**
     * Анализ N+1 проблем в коллекции
     */
    public static function analyzeN1Problems($collection, string $model): array
    {
        $stats = [
            'total_queries' => 0,
            'n1_risks' => [],
            'recommendations' => []
        ];

        // Получаем количество выполненных запросов до анализа
        $queriesCount = count(\DB::getQueryLog());
        
        // Анализируем загруженные связи
        if ($collection->isNotEmpty()) {
            $item = $collection->first();
            $loadedRelations = $item->getRelations();
            
            // Проверяем основные связи которые должны быть загружены
            $requiredRelations = self::getRequiredRelations($model);
            
            foreach ($requiredRelations as $relation) {
                if (!array_key_exists($relation, $loadedRelations)) {
                    $stats['n1_risks'][] = "Relation '{$relation}' not loaded - potential N+1 problem";
                    $stats['recommendations'][] = "Add '{$relation}' to eager loading";
                }
            }
        }

        $stats['total_queries'] = $queriesCount;
        
        return $stats;
    }

    /**
     * Получить обязательные связи для модели
     */
    private static function getRequiredRelations(string $model): array
    {
        $relations = [
            'Ad' => ['user', 'masterProfile'],
            'Master' => ['user', 'photos'],
            'Booking' => ['client', 'master', 'service'],
            'Review' => ['user', 'reviewable']
        ];

        return $relations[$model] ?? [];
    }

    /**
     * Создать оптимизированный запрос для списка с пагинацией
     */
    public static function createOptimizedListQuery($model, array $filters = [], string $level = 'basic')
    {
        $query = $model::query();

        // Добавляем eager loading
        $eagerLoads = match (get_class($model)) {
            \App\Domain\Ad\Models\Ad::class => self::getAdEagerLoads()[$level] ?? [],
            \App\Domain\Master\Models\MasterProfile::class => self::getMasterEagerLoads()[$level] ?? [],
            \App\Domain\Booking\Models\Booking::class => self::getBookingEagerLoads()[$level] ?? [],
            default => []
        };

        if (!empty($eagerLoads)) {
            $query->with($eagerLoads);
        }

        // Применяем фильтры
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query;
    }

    /**
     * Рекомендации по индексам для оптимизации
     */
    public static function getIndexRecommendations(): array
    {
        return [
            'ads' => [
                'INDEX(user_id, status, created_at)',
                'INDEX(category, status)',
                'INDEX(status, published_at)',
                'INDEX(master_profile_id, status)'
            ],
            'master_profiles' => [
                'INDEX(user_id, status)',
                'INDEX(status, is_premium, is_verified)',
                'INDEX(rating, status)',
                'INDEX(created_at, status)'
            ],
            'bookings' => [
                'INDEX(client_id, status)',
                'INDEX(master_id, status)',
                'INDEX(master_profile_id, status)',
                'INDEX(booking_date, status)',
                'INDEX(status, created_at)'
            ],
            'reviews' => [
                'INDEX(reviewable_type, reviewable_id)',
                'INDEX(user_id, created_at)',
                'INDEX(rating, created_at)'
            ],
            'media' => [
                'INDEX(mediable_type, mediable_id)',
                'INDEX(is_main, order)'
            ]
        ];
    }

    /**
     * Генерация SQL для создания рекомендованных индексов
     */
    public static function generateIndexSQL(string $table): array
    {
        $recommendations = self::getIndexRecommendations();
        $indexes = $recommendations[$table] ?? [];
        
        $sql = [];
        foreach ($indexes as $index) {
            $indexName = 'idx_' . $table . '_' . str_replace(['(', ')', ', ', '_id'], ['', '', '_', ''], $index);
            $sql[] = "CREATE INDEX {$indexName} ON {$table} {$index};";
        }
        
        return $sql;
    }
}