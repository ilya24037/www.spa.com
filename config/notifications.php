<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Уведомления по Email
    |--------------------------------------------------------------------------
    |
    | Настройки для отправки email уведомлений
    |
    */
    
    'email' => [
        'enabled' => env('NOTIFICATIONS_EMAIL_ENABLED', true),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@spa.com'),
        'from_name' => env('MAIL_FROM_NAME', 'SPA Platform'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS уведомления
    |--------------------------------------------------------------------------
    |
    | Настройки для отправки SMS уведомлений
    |
    */
    
    'sms' => [
        'enabled' => env('NOTIFICATIONS_SMS_ENABLED', false),
        'provider' => env('SMS_PROVIDER', 'test'), // test, smsru, smsc
        'api_key' => env('SMS_API_KEY'),
        'sender' => env('SMS_SENDER', 'SPA'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Push уведомления
    |--------------------------------------------------------------------------
    |
    | Настройки для push уведомлений
    |
    */
    
    'push' => [
        'enabled' => env('NOTIFICATIONS_PUSH_ENABLED', false),
        'firebase_server_key' => env('FIREBASE_SERVER_KEY'),
        'vapid_public_key' => env('VAPID_PUBLIC_KEY'),
        'vapid_private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки уведомлений для бронирований
    |--------------------------------------------------------------------------
    */
    
    'booking' => [
        // Когда отправлять напоминания (в часах до события)
        'reminder_hours' => [24, 2],
        
        // Автоматически отправлять запрос отзыва (в часах после завершения)
        'review_request_delay' => 2,
        
        // Уведомления для клиентов
        'client_notifications' => [
            'booking_created' => ['email', 'sms'],
            'booking_confirmed' => ['email', 'sms'],
            'booking_cancelled' => ['email', 'sms'],
            'booking_reminder' => ['email', 'sms'],
            'review_request' => ['email'],
        ],
        
        // Уведомления для мастеров
        'master_notifications' => [
            'booking_created' => ['email', 'sms'],
            'booking_cancelled' => ['email', 'sms'],
            'payment_received' => ['email'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки уведомлений для платежей
    |--------------------------------------------------------------------------
    */
    
    'payment' => [
        'client_notifications' => [
            'payment_pending' => ['email'],
            'payment_completed' => ['email', 'sms'],
            'payment_failed' => ['email', 'sms'],
            'refund_processed' => ['email'],
        ],
        
        'admin_notifications' => [
            'payment_completed' => ['email'],
            'payment_failed' => ['email'],
            'chargeback_received' => ['email'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Общие настройки
    |--------------------------------------------------------------------------
    */
    
    'general' => [
        // Максимальное количество попыток отправки
        'max_attempts' => 3,
        
        // Задержка между попытками (в секундах)
        'retry_delay' => 60,
        
        // Логировать все уведомления
        'log_notifications' => env('LOG_NOTIFICATIONS', true),
        
        // Отключить все уведомления (для тестирования)
        'disable_all' => env('DISABLE_NOTIFICATIONS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Шаблоны уведомлений
    |--------------------------------------------------------------------------
    */
    
    'templates' => [
        'booking_created_client' => 'notifications.booking.created_client',
        'booking_created_master' => 'notifications.booking.created_master',
        'booking_confirmed' => 'notifications.booking.confirmed',
        'booking_cancelled' => 'notifications.booking.cancelled',
        'booking_reminder' => 'notifications.booking.reminder',
        'review_request' => 'notifications.booking.review_request',
        'payment_completed' => 'notifications.payment.completed',
        'payment_failed' => 'notifications.payment.failed',
    ],

]; 