<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Redis Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the Redis connections below you wish
    | to use as your default connection when using the Redis facade.
    | This saves having to call a specific connection each time.
    |
    */

    'default' => env('REDIS_CLIENT', 'phpredis'),

    /*
    |--------------------------------------------------------------------------
    | Redis Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the Redis connections setup for your application.
    | Of course, examples of configuring each available driver is shown
    | below. You can add or remove connections as required.
    |
    */

    'connections' => [

        'default' => [
            'driver' => env('REDIS_CLIENT', 'phpredis'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DB', 0),
            'read_write_timeout' => 60,
            'context' => [
                // 'auth' => ['username', 'secret'],
                // 'stream' => ['verify_peer' => false],
            ],
        ],

        'cache' => [
            'driver' => env('REDIS_CLIENT', 'phpredis'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_CACHE_DB', 1),
            'serializer' => 'php',
            'compression' => 'lz4',
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),
        ],

        'session' => [
            'driver' => env('REDIS_CLIENT', 'phpredis'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_SESSION_DB', 2),
            'serializer' => 'php',
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_session_'),
        ],

        'queue' => [
            'driver' => env('REDIS_CLIENT', 'phpredis'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_QUEUE_DB', 3),
            'serializer' => 'php',
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_queue_'),
        ],

        'analytics' => [
            'driver' => env('REDIS_CLIENT', 'phpredis'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_ANALYTICS_DB', 4),
            'serializer' => 'php',
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_analytics_'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Sentinel
    |--------------------------------------------------------------------------
    |
    | Here you can configure Redis Sentinel which provides high
    | availability for Redis. Add the sentinels configuration
    | below with their host and port.
    |
    */

    'sentinel' => [
        'servers' => explode(',', env('REDIS_SENTINELS', '')),
        'options' => [
            'service' => env('REDIS_SENTINEL_SERVICE', 'mymaster'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Cluster
    |--------------------------------------------------------------------------
    |
    | Here you can configure Redis Cluster which provides automatic
    | sharding of data across multiple Redis nodes. When using
    | clusters, you may not use the "database" configuration option.
    |
    */

    'clusters' => [

        'default' => [
            [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'password' => env('REDIS_PASSWORD'),
                'port' => env('REDIS_PORT', 6379),
                'database' => 0,
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Options
    |--------------------------------------------------------------------------
    |
    | Here you can configure global Redis options used by your application.
    | These will be applied to all Redis connections unless overridden
    | in the specific connection configuration.
    |
    */

    'options' => [
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        
        // Performance optimizations for SPA Platform
        'serializer' => 'php',
        'compression' => 'lz4',
        'read_write_timeout' => 60,
        
        // Connection pooling
        'persistent' => true,
        'connection_timeout' => 5.0,
        
        // Memory optimization
        'tcp_keepalive' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration for SPA Platform
    |--------------------------------------------------------------------------
    |
    | Custom cache settings optimized for high-performance caching
    | of ads, masters, bookings and other frequent data.
    |
    */

    'cache_settings' => [
        
        // Different TTL for different data types
        'ttl' => [
            'ads' => [
                'single' => 300,        // 5 minutes
                'list' => 180,          // 3 minutes
                'search' => 120,        // 2 minutes
                'popular' => 900,       // 15 minutes
            ],
            'masters' => [
                'single' => 600,        // 10 minutes
                'list' => 300,          // 5 minutes
                'schedule' => 180,      // 3 minutes
                'slots' => 60,          // 1 minute
            ],
            'bookings' => [
                'single' => 60,         // 1 minute
                'list' => 30,           // 30 seconds
            ],
            'static' => [
                'config' => 86400,      // 24 hours
                'services' => 3600,     // 1 hour
                'categories' => 7200,   // 2 hours
            ],
        ],
        
        // Cache warming settings
        'warm_cache' => [
            'enabled' => env('CACHE_WARM_ENABLED', true),
            'schedule' => [
                'popular_ads' => '0 */6 * * *',      // Every 6 hours
                'top_masters' => '0 */4 * * *',      // Every 4 hours  
                'categories' => '0 0 * * *',         // Daily
            ],
        ],
        
        // Memory limits
        'memory' => [
            'max_memory' => env('REDIS_MAX_MEMORY', '256mb'),
            'eviction_policy' => env('REDIS_EVICTION_POLICY', 'allkeys-lru'),
        ],
    ],

];