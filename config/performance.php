<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Конфигурация производительности
    |--------------------------------------------------------------------------
    */

    'cache' => [
        // Время жизни кеша для разных типов данных (в секундах)
        'ttl' => [
            'masters_list' => 300,          // 5 минут
            'master_profile' => 600,        // 10 минут
            'services' => 3600,             // 1 час
            'bookings' => 60,               // 1 минута
            'reviews' => 1800,              // 30 минут
            'search_results' => 180,        // 3 минуты
            'statistics' => 900,            // 15 минут
            'configuration' => 86400,       // 24 часа
            'cities' => 86400,              // 24 часа
        ],

        // Стратегия прогрева кеша
        'warmup' => [
            'enabled' => env('CACHE_WARMUP_ENABLED', true),
            'schedule' => '0 */6 * * *',    // Каждые 6 часов
            'items' => [
                'top_masters',
                'popular_services',
                'active_cities',
                'site_statistics',
            ],
        ],

        // Настройки Redis
        'redis' => [
            'prefix' => env('CACHE_PREFIX', 'spa_platform'),
            'ttl_variance' => 0.1,          // 10% вариация TTL для предотвращения stampede
        ],
    ],

    'database' => [
        // Пул соединений
        'connections' => [
            'pool_size' => env('DB_POOL_SIZE', 10),
            'max_lifetime' => 300,          // 5 минут
        ],

        // Настройки запросов
        'queries' => [
            'slow_query_threshold' => 1000, // мс
            'log_slow_queries' => env('LOG_SLOW_QUERIES', true),
            'explain_threshold' => 100,     // мс
        ],

        // Eager loading
        'eager_loading' => [
            'auto_detect_n_plus_one' => env('APP_DEBUG', false),
            'throw_on_n_plus_one' => false,
        ],
    ],

    'optimization' => [
        // Сжатие ответов
        'compression' => [
            'enabled' => env('RESPONSE_COMPRESSION', true),
            'level' => 6,                   // 1-9
            'min_length' => 1024,           // байт
        ],

        // Минификация
        'minification' => [
            'html' => env('MINIFY_HTML', true),
            'css' => env('MINIFY_CSS', true),
            'js' => env('MINIFY_JS', true),
        ],

        // CDN
        'cdn' => [
            'enabled' => env('CDN_ENABLED', false),
            'url' => env('CDN_URL'),
            'assets' => [
                'images',
                'css',
                'js',
                'fonts',
            ],
        ],
    ],

    'monitoring' => [
        // APM (Application Performance Monitoring)
        'apm' => [
            'enabled' => env('APM_ENABLED', false),
            'service' => env('APM_SERVICE', 'newrelic'), // newrelic, datadog, etc
        ],

        // Метрики
        'metrics' => [
            'collect_db_metrics' => true,
            'collect_cache_metrics' => true,
            'collect_queue_metrics' => true,
        ],

        // Алерты
        'alerts' => [
            'slow_response_threshold' => 2000, // мс
            'high_memory_threshold' => 80,     // %
            'high_cpu_threshold' => 80,        // %
        ],
    ],

    'queue' => [
        // Настройки очередей
        'workers' => [
            'default' => env('QUEUE_WORKERS', 4),
            'high' => env('QUEUE_HIGH_WORKERS', 2),
            'low' => env('QUEUE_LOW_WORKERS', 1),
        ],

        // Таймауты
        'timeouts' => [
            'default' => 60,
            'long_running' => 300,
            'bulk_operations' => 600,
        ],

        // Попытки
        'retries' => [
            'default' => 3,
            'critical' => 5,
        ],
    ],

    'images' => [
        // Оптимизация изображений
        'optimization' => [
            'enabled' => true,
            'quality' => 85,
            'max_width' => 1920,
            'max_height' => 1080,
            'formats' => ['webp', 'jpg'],
        ],

        // Lazy loading
        'lazy_loading' => [
            'enabled' => true,
            'threshold' => 100,             // px от viewport
            'placeholder' => 'blur',        // blur, color, none
        ],

        // Размеры превью
        'thumbnails' => [
            'small' => [150, 150],
            'medium' => [300, 300],
            'large' => [600, 600],
        ],
    ],

    'api' => [
        // Rate limiting
        'rate_limiting' => [
            'enabled' => true,
            'default_limit' => 60,          // запросов в минуту
            'authenticated_limit' => 120,
        ],

        // Пагинация
        'pagination' => [
            'default_per_page' => 20,
            'max_per_page' => 100,
        ],

        // Кеширование ответов
        'response_cache' => [
            'enabled' => true,
            'ttl' => 60,                    // секунд
            'vary_by_auth' => true,
        ],
    ],

    'frontend' => [
        // Настройки Vue.js
        'vue' => [
            'lazy_components' => true,
            'prefetch_links' => true,
            'preload_critical' => true,
        ],

        // Чанки
        'chunks' => [
            'vendor_split' => true,
            'async_routes' => true,
            'max_size' => 244,              // КБ
        ],

        // Service Worker
        'service_worker' => [
            'enabled' => env('SW_ENABLED', true),
            'cache_strategy' => 'network-first',
            'offline_page' => '/offline',
        ],
    ],
];