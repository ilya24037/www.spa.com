<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Elasticsearch
    |--------------------------------------------------------------------------
    |
    | Глобальный переключатель для включения/отключения Elasticsearch
    |
    */
    'enabled' => env('ELASTICSEARCH_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Hosts
    |--------------------------------------------------------------------------
    |
    | Список хостов Elasticsearch. Может быть строкой для одного хоста
    | или массивом для кластера.
    |
    */
    'hosts' => env('ELASTICSEARCH_HOSTS', 'localhost:9200'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    |
    | Префикс для всех индексов. Полезно для разделения индексов
    | разных окружений на одном кластере.
    |
    */
    'index_prefix' => env('ELASTICSEARCH_INDEX_PREFIX', 'spa_'),

    /*
    |--------------------------------------------------------------------------
    | Connection Settings
    |--------------------------------------------------------------------------
    |
    | Настройки подключения к Elasticsearch
    |
    */
    'connection_pool' => '\Elasticsearch\ConnectionPool\StaticNoPingConnectionPool',
    'retries' => env('ELASTICSEARCH_RETRIES', 2),
    'connection_timeout' => env('ELASTICSEARCH_CONNECTION_TIMEOUT', 10),
    'timeout' => env('ELASTICSEARCH_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Index Settings
    |--------------------------------------------------------------------------
    |
    | Настройки по умолчанию для создаваемых индексов
    |
    */
    'number_of_shards' => env('ELASTICSEARCH_SHARDS', 1),
    'number_of_replicas' => env('ELASTICSEARCH_REPLICAS', 0),

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | Настройки аутентификации для Elasticsearch
    |
    */
    'auth' => [
        'enabled' => env('ELASTICSEARCH_AUTH_ENABLED', false),
        'username' => env('ELASTICSEARCH_USERNAME'),
        'password' => env('ELASTICSEARCH_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SSL Settings
    |--------------------------------------------------------------------------
    |
    | Настройки SSL для безопасного подключения
    |
    */
    'ssl' => [
        'enabled' => env('ELASTICSEARCH_SSL_ENABLED', false),
        'verify' => env('ELASTICSEARCH_SSL_VERIFY', true),
        'cert' => env('ELASTICSEARCH_SSL_CERT'),
        'cert_password' => env('ELASTICSEARCH_SSL_CERT_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Настройки логирования операций Elasticsearch
    |
    */
    'logging' => [
        'enabled' => env('ELASTICSEARCH_LOGGING_ENABLED', true),
        'level' => env('ELASTICSEARCH_LOGGING_LEVEL', 'info'),
        'channel' => env('ELASTICSEARCH_LOGGING_CHANNEL', 'elasticsearch'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Настройки для асинхронной индексации через очереди
    |
    */
    'queue' => [
        'enabled' => env('ELASTICSEARCH_QUEUE_ENABLED', true),
        'connection' => env('ELASTICSEARCH_QUEUE_CONNECTION', 'redis'),
        'queue' => env('ELASTICSEARCH_QUEUE_NAME', 'elasticsearch'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bulk Settings
    |--------------------------------------------------------------------------
    |
    | Настройки для массовых операций
    |
    */
    'bulk' => [
        'size' => env('ELASTICSEARCH_BULK_SIZE', 1000),
        'refresh' => env('ELASTICSEARCH_BULK_REFRESH', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Settings
    |--------------------------------------------------------------------------
    |
    | Настройки поиска по умолчанию
    |
    */
    'search' => [
        'default_size' => env('ELASTICSEARCH_SEARCH_SIZE', 20),
        'max_size' => env('ELASTICSEARCH_SEARCH_MAX_SIZE', 100),
        'default_fuzziness' => env('ELASTICSEARCH_SEARCH_FUZZINESS', 'AUTO'),
        'highlight_enabled' => env('ELASTICSEARCH_HIGHLIGHT_ENABLED', true),
        'highlight_tag' => env('ELASTICSEARCH_HIGHLIGHT_TAG', 'em'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Indices Configuration
    |--------------------------------------------------------------------------
    |
    | Конфигурация для каждого индекса
    |
    */
    'indices' => [
        'ads' => [
            'name' => env('ELASTICSEARCH_INDEX_ADS', 'ads'),
            'settings' => [
                'number_of_shards' => 2,
                'number_of_replicas' => 1,
                'max_result_window' => 10000,
            ],
        ],
        'masters' => [
            'name' => env('ELASTICSEARCH_INDEX_MASTERS', 'masters'),
            'settings' => [
                'number_of_shards' => 2,
                'number_of_replicas' => 1,
                'max_result_window' => 10000,
            ],
        ],
        'services' => [
            'name' => env('ELASTICSEARCH_INDEX_SERVICES', 'services'),
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
                'max_result_window' => 5000,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Synonyms
    |--------------------------------------------------------------------------
    |
    | Синонимы для улучшения поиска
    |
    */
    'synonyms' => [
        'массаж,масаж => массаж',
        'массажист,массажистка => массажист',
        'спа,spa => спа',
        'релакс,релаксация => релакс',
        'тайский,thai => тайский',
        'классический,шведский => классический',
        'косметолог,эстетист => косметолог',
        'маникюр,ногти => маникюр',
        'педикюр,стопы => педикюр',
    ],

    /*
    |--------------------------------------------------------------------------
    | Stop Words
    |--------------------------------------------------------------------------
    |
    | Стоп-слова для русского языка (дополнительные к стандартным)
    |
    */
    'stop_words' => [
        'салон',
        'услуга',
        'мастер',
        'хороший',
        'лучший',
        'недорого',
        'дешево',
        'качественно',
    ],

    /*
    |--------------------------------------------------------------------------
    | Analyzers
    |--------------------------------------------------------------------------
    |
    | Настройки анализаторов текста
    |
    */
    'analyzers' => [
        'autocomplete_min_gram' => env('ELASTICSEARCH_AUTOCOMPLETE_MIN', 2),
        'autocomplete_max_gram' => env('ELASTICSEARCH_AUTOCOMPLETE_MAX', 10),
        'phonetic_encoder' => env('ELASTICSEARCH_PHONETIC_ENCODER', 'metaphone'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Настройки кеширования результатов поиска
    |
    */
    'cache' => [
        'enabled' => env('ELASTICSEARCH_CACHE_ENABLED', true),
        'ttl' => env('ELASTICSEARCH_CACHE_TTL', 300), // 5 минут
        'prefix' => env('ELASTICSEARCH_CACHE_PREFIX', 'es_search_'),
    ],
];