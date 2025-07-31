<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags Configuration
    |--------------------------------------------------------------------------
    |
    | Конфигурация функциональных флагов для постепенного внедрения новых функций
    |
    */

    // Модульная архитектура
    'use_modern_booking_service' => [
        'enabled' => env('FEATURE_MODERN_BOOKING', false),
        'percentage' => 0, // Процент пользователей
        'environments' => ['local', 'staging'],
        'description' => 'Использовать новый модульный BookingService'
    ],

    'use_modern_search' => [
        'enabled' => env('FEATURE_MODERN_SEARCH', false),
        'percentage' => 0,
        'environments' => ['local', 'staging'],
        'description' => 'Использовать новый поисковый движок'
    ],

    'use_adapters' => [
        'enabled' => env('FEATURE_USE_ADAPTERS', true),
        'description' => 'Использовать адаптеры для постепенной миграции'
    ],

    // Logging и мониторинг
    'log_legacy_calls' => [
        'enabled' => env('FEATURE_LOG_LEGACY', true),
        'description' => 'Логировать вызовы legacy методов'
    ],

    'detailed_performance_monitoring' => [
        'enabled' => env('FEATURE_PERFORMANCE_MONITORING', false),
        'roles' => ['admin', 'developer'],
        'description' => 'Детальный мониторинг производительности'
    ],

    // Кеширование
    'aggressive_caching' => [
        'enabled' => env('FEATURE_AGGRESSIVE_CACHE', false),
        'percentage' => 50,
        'description' => 'Агрессивное кеширование данных'
    ],

    'redis_cache' => [
        'enabled' => env('FEATURE_REDIS_CACHE', true),
        'description' => 'Использовать Redis для кеширования'
    ],

    // UI функции
    'new_booking_ui' => [
        'enabled' => false,
        'percentage' => 10,
        'users' => [], // ID конкретных пользователей
        'description' => 'Новый интерфейс бронирования'
    ],

    'advanced_search_filters' => [
        'enabled' => true,
        'roles' => ['premium', 'vip'],
        'description' => 'Расширенные фильтры поиска'
    ],

    // API версии
    'api_v2' => [
        'enabled' => false,
        'percentage' => 5,
        'environments' => ['staging'],
        'description' => 'Новая версия API'
    ],

    // Экспериментальные функции
    'ai_recommendations' => [
        'enabled' => false,
        'percentage' => 1,
        'description' => 'AI рекомендации мастеров'
    ],

    'real_time_availability' => [
        'enabled' => false,
        'users' => [], // Тестовые пользователи
        'description' => 'Доступность мастеров в реальном времени'
    ],

    // Безопасность
    'two_factor_auth' => [
        'enabled' => true,
        'roles' => ['admin', 'master'],
        'description' => 'Двухфакторная аутентификация'
    ],

    'rate_limiting_strict' => [
        'enabled' => env('FEATURE_STRICT_RATE_LIMIT', false),
        'environments' => ['production'],
        'description' => 'Строгие лимиты на API запросы'
    ],

    // Оптимизация
    'lazy_loading_images' => [
        'enabled' => true,
        'description' => 'Ленивая загрузка изображений'
    ],

    'service_worker' => [
        'enabled' => env('FEATURE_SERVICE_WORKER', false),
        'environments' => ['production'],
        'description' => 'Service Worker для offline режима'
    ],

    // Платежи
    'new_payment_gateway' => [
        'enabled' => false,
        'percentage' => 0,
        'description' => 'Новый платежный шлюз'
    ],

    'subscription_model' => [
        'enabled' => false,
        'roles' => ['master'],
        'description' => 'Модель подписки для мастеров'
    ],

    // Уведомления
    'push_notifications' => [
        'enabled' => false,
        'percentage' => 25,
        'description' => 'Push уведомления'
    ],

    'email_batching' => [
        'enabled' => true,
        'environments' => ['production'],
        'description' => 'Группировка email уведомлений'
    ],

    // Аналитика
    'advanced_analytics' => [
        'enabled' => false,
        'roles' => ['admin', 'analyst'],
        'description' => 'Расширенная аналитика'
    ],

    'user_behavior_tracking' => [
        'enabled' => false,
        'percentage' => 10,
        'description' => 'Отслеживание поведения пользователей'
    ],
];