<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Systems Configuration
    |--------------------------------------------------------------------------
    |
    | Конфигурация платёжных систем для SPA Platform
    | Интеграция с DigiSeller, WebMoney, и другими системами
    |
    */

    // WebMoney Web Merchant Interface
    'webmoney' => [
        'purse' => env('WEBMONEY_PURSE', 'Z123456789012'), // WebMoney кошелёк
        'secret_key' => env('WEBMONEY_SECRET_KEY', 'your_secret_key'),
        'test_mode' => env('WEBMONEY_TEST_MODE', true),
        'merchant_url' => 'https://merchant.webmoney.com/lmi/payment_utf.asp',
        
        // Настройки безопасности
        'verify_signature' => true,
        'allowed_ips' => [
            '91.200.29.0/24',   // WebMoney IP диапазоны
            '195.28.254.0/24',
        ]
    ],

    // DigiSeller интеграция
    'digiseller' => [
        'seller_id' => env('DIGISELLER_SELLER_ID'),
        'api_key' => env('DIGISELLER_API_KEY'),
        'test_mode' => env('DIGISELLER_TEST_MODE', true),
        'commission' => 1.5, // 1.5% комиссия DigiSeller
        
        // URLs
        'api_url' => 'https://api.digiseller.com/api',
        'payment_url' => 'https://shop.digiseller.com/xml',
    ],

    // Система скидок (как в DigiSeller)
    'discounts' => [
        // Скидки по объёму покупки
        'volume_discounts' => [
            25000 => 45,   // При покупке от 25000₽ - скидка 45%
            10000 => 25,   // При покупке от 10000₽ - скидка 25%
            5000  => 10,   // При покупке от 5000₽ - скидка 10%
            1000  => 5,    // При покупке от 1000₽ - скидка 5%
        ],
        
        // Скидки по уровню лояльности
        'loyalty_discounts' => [
            'platinum' => ['min_spent' => 250000, 'discount' => 45],
            'gold'     => ['min_spent' => 50000,  'discount' => 25],
            'silver'   => ['min_spent' => 10000,  'discount' => 10],
            'bronze'   => ['min_spent' => 0,      'discount' => 0],
        ]
    ],

    // Тарифные планы пополнения (как в DigiSeller)
    'top_up_plans' => [
        [
            'amount' => 1000,
            'price' => 950,
            'discount_percent' => 5,
            'description' => 'Код оплаты номиналом 1 000 руб. (за 950 руб.)'
        ],
        [
            'amount' => 2000,
            'price' => 1750,
            'discount_percent' => 12.5,
            'description' => 'Код оплаты номиналом 2 000 руб. (за 1 750 руб.)'
        ],
        [
            'amount' => 5000,
            'price' => 3750,
            'discount_percent' => 25,
            'description' => 'Код оплаты номиналом 5 000 руб. (за 3 750 руб.)'
        ],
        [
            'amount' => 10000,
            'price' => 7500,
            'discount_percent' => 25,
            'description' => 'Код оплаты номиналом 10 000 руб. (за 7 500 руб.)'
        ],
        [
            'amount' => 15000,
            'price' => 11000,
            'discount_percent' => 27,
            'description' => 'Код оплаты номиналом 15 000 руб. (за 11 000 руб.)'
        ],
    ],

    // Поддерживаемые способы оплаты
    'payment_methods' => [
        'webmoney' => [
            'name' => 'WebMoney',
            'icon' => 'webmoney-icon.svg',
            'enabled' => true,
            'description' => 'Оплата через кошелёк WebMoney'
        ],
        'bank_card' => [
            'name' => 'Банковская карта',
            'icon' => 'card-icon.svg', 
            'enabled' => true,
            'description' => 'Visa, MasterCard, МИР'
        ],
        'bitcoin' => [
            'name' => 'Bitcoin',
            'icon' => 'bitcoin-icon.svg',
            'enabled' => true,
            'description' => 'Оплата в криптовалюте Bitcoin'
        ],
        'ethereum' => [
            'name' => 'Ethereum',
            'icon' => 'ethereum-icon.svg',
            'enabled' => true,
            'description' => 'Оплата в криптовалюте Ethereum'
        ],
        'qiwi' => [
            'name' => 'QIWI Кошелёк',
            'icon' => 'qiwi-icon.svg',
            'enabled' => true,
            'description' => 'Оплата через QIWI'
        ],
        'yandex_money' => [
            'name' => 'ЮMoney',
            'icon' => 'yandex-money-icon.svg',
            'enabled' => true,
            'description' => 'Оплата через ЮMoney (бывш. Яндекс.Деньги)'
        ]
    ],

    // Настройки активационных кодов
    'activation_codes' => [
        'length' => 16,                    // Длина кода
        'prefix' => 'SPA',                 // Префикс для кодов
        'expires_days' => 365,             // Срок действия кода в днях
        'max_uses' => 1,                   // Максимальное количество использований
    ],

    // Безопасность
    'security' => [
        'encrypt_codes' => true,           // Шифровать ли коды в БД
        'rate_limit' => [
            'activation_attempts' => 5,    // Максимум попыток активации в час
            'payment_attempts' => 10,      // Максимум попыток оплаты в час
        ]
    ]
]; 