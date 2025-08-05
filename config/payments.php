<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Основные настройки платежей
    |--------------------------------------------------------------------------
    */

    'default_currency' => 'RUB',
    'min_amount' => 100,
    'max_amount' => 500000,

    /*
    |--------------------------------------------------------------------------
    | YooKassa (ЮKassa) - основной шлюз
    |--------------------------------------------------------------------------
    */

    'yookassa' => [
        'enabled' => env('YOOKASSA_ENABLED', true),
        'shop_id' => env('YOOKASSA_SHOP_ID'),
        'secret_key' => env('YOOKASSA_SECRET_KEY'),
        'test_mode' => env('YOOKASSA_TEST_MODE', true),
        'webhook_secret' => env('YOOKASSA_WEBHOOK_SECRET'),
        
        // Поддерживаемые методы оплаты
        'payment_methods' => [
            'bank_card' => true,
            'yoo_money' => true,
            'qiwi' => true,
            'webmoney' => true,
            'alfabank' => true,
            'tinkoff_bank' => true,
            'sberbank' => true,
            'sbp' => true,
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Система быстрых платежей (СБП)
    |--------------------------------------------------------------------------
    */

    'sbp' => [
        'enabled' => env('SBP_ENABLED', true),
        'merchant_id' => env('SBP_MERCHANT_ID'),
        'api_key' => env('SBP_API_KEY'),
        'test_mode' => env('SBP_TEST_MODE', true),
        
        // Банки-участники СБП
        'banks' => [
            '100000000111' => 'Сбербанк',
            '044525225' => 'Тинькофф',
            '044525593' => 'Альфа-банк',
            '044525974' => 'ВТБ',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | WebMoney
    |--------------------------------------------------------------------------
    */

    'webmoney' => [
        'enabled' => env('WEBMONEY_ENABLED', false),
        'purse' => env('WEBMONEY_PURSE'),
        'secret_key' => env('WEBMONEY_SECRET_KEY'),
        'test_mode' => env('WEBMONEY_TEST_MODE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Робокасса
    |--------------------------------------------------------------------------
    */

    'robokassa' => [
        'enabled' => env('ROBOKASSA_ENABLED', false),
        'login' => env('ROBOKASSA_LOGIN'),
        'password1' => env('ROBOKASSA_PASSWORD1'),
        'password2' => env('ROBOKASSA_PASSWORD2'),
        'test_mode' => env('ROBOKASSA_TEST_MODE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe (для международных платежей)
    |--------------------------------------------------------------------------
    */

    'stripe' => [
        'enabled' => env('STRIPE_ENABLED', false),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'test_mode' => env('STRIPE_TEST_MODE', true),
        'supported_currencies' => ['USD', 'EUR', 'RUB', 'GBP'],
        
        // Настройки комиссий по валютам
        'fees' => [
            'usd' => ['percent' => 2.9, 'fixed' => 30], // 2.9% + $0.30
            'eur' => ['percent' => 2.9, 'fixed' => 25], // 2.9% + €0.25
            'gbp' => ['percent' => 2.9, 'fixed' => 20], // 2.9% + £0.20
            'rub' => ['percent' => 2.9, 'fixed' => 1500], // 2.9% + 15₽
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Paypal (для международных платежей)
    |--------------------------------------------------------------------------
    */

    'paypal' => [
        'enabled' => env('PAYPAL_ENABLED', false),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'sandbox' => env('PAYPAL_SANDBOX', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки комиссий
    |--------------------------------------------------------------------------
    */

    'fees' => [
        // Комиссия платформы (%)
        'platform_fee_percent' => env('PLATFORM_FEE_PERCENT', 5),
        
        // Комиссии платежных систем
        'gateway_fees' => [
            'yookassa' => 2.9,
            'sbp' => 0.4,
            'webmoney' => 0.8,
            'robokassa' => 3.5,
            'paypal' => 3.4,
        ],
        
        // Кто платит комиссию: 'customer', 'merchant', 'split'
        'fee_payer' => env('PAYMENT_FEE_PAYER', 'customer'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Лимиты и ограничения
    |--------------------------------------------------------------------------
    */

    'limits' => [
        // Лимиты по суммам (в рублях)
        'daily_limit' => 100000,
        'monthly_limit' => 2000000,
        'single_payment_limit' => 500000,
        
        // Лимиты по количеству операций
        'daily_operations_limit' => 50,
        'hourly_operations_limit' => 10,
        
        // Автоматические ограничения
        'auto_block_suspicious' => true,
        'max_failed_attempts' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Настройки возвратов
    |--------------------------------------------------------------------------
    */

    'refunds' => [
        'enabled' => true,
        'auto_refund_period' => 24, // часов
        'partial_refunds' => true,
        'refund_fee' => 50, // фиксированная комиссия за возврат
        
        // Правила возврата
        'rules' => [
            'ad_placement' => [
                'period_hours' => 24,
                'partial_allowed' => false,
            ],
            'service_booking' => [
                'period_hours' => 2,
                'partial_allowed' => true,
            ],
            'balance_top_up' => [
                'period_hours' => 1,
                'partial_allowed' => true,
            ],
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Безопасность
    |--------------------------------------------------------------------------
    */

    'security' => [
        // IP адреса платежных систем
        'allowed_ips' => [
            'yookassa' => [
                '185.71.76.0/27',
                '185.71.77.0/27',
                '77.75.153.0/25',
                '77.75.156.11',
                '77.75.156.35',
                '2a02:5180:0:1509::1'
            ],
            'stripe' => [
                '54.187.174.169',
                '54.187.205.235',
                '54.187.216.72',
                '54.241.31.99',
                '54.241.31.102',
                '54.241.34.107',
            ],
            'webmoney' => [
                '195.24.90.0/24',
                '195.24.91.0/24'
            ]
        ],
        
        // Шифрование данных
        'encrypt_sensitive_data' => true,
        'hash_algorithm' => 'sha256',
        
        // Проверка подписей
        'verify_signatures' => true,
        'signature_timeout' => 300, // секунд
    ],

    /*
    |--------------------------------------------------------------------------
    | Логирование и мониторинг
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'log_all_requests' => env('LOG_PAYMENT_REQUESTS', true),
        'log_webhooks' => env('LOG_PAYMENT_WEBHOOKS', true),
        'log_level' => env('PAYMENT_LOG_LEVEL', 'info'),
        'separate_log_file' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Тестовые данные
    |--------------------------------------------------------------------------
    */

    'test' => [
        'enabled' => env('PAYMENT_TEST_MODE', true),
        'simulate_delays' => true,
        'success_rate' => 90, // процент успешных тестовых платежей
        
        'test_cards' => [
            'success' => '4111111111111111',
            'decline' => '4000000000000002',
            'insufficient_funds' => '4000000000000119',
            '3ds_required' => '4000000000000416',
        ]
    ]

]; 