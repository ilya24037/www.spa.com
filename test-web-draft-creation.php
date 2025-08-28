<?php

echo "🎯 ТЕСТ СОЗДАНИЯ ЧЕРНОВИКА ЧЕРЕЗ WEB ИНТЕРФЕЙС\n";
echo "=============================================\n\n";

// Симуляция данных как они приходят из frontend
$postData = [
    'status' => 'draft',
    'specialty' => 'массаж',
    'work_format' => 'individual',
    'experience' => '',
    'description' => '',
    'title' => '',
    'category' => 'relax',
    
    // JSON поля как строки (как приходят из FormData)
    'prices' => '[]',
    'services' => '{"hygiene_amenities":{"shower_before":{"enabled":false,"price_comment":""}}}',
    'clients' => '["men"]',
    'service_provider' => '["women"]',
    'features' => '[]',
    'schedule' => '[]',
    'geo' => '[]',
    
    // Простые поля
    'phone' => '',
    'whatsapp' => '',
    'telegram' => '',
    'contact_method' => '',
    'vk' => '',
    'instagram' => '',
    'address' => '',
    'radius' => '0',
    'is_remote' => 'false',
    'age' => '',
    'height' => '',
    'weight' => '',
    'breast_size' => '',
    'hair_color' => '',
    'eye_color' => '',
    'nationality' => '',
    'bikini_zone' => '',
    'appearance' => '',
    'additional_features' => '',
    'discount' => '0',
    'new_client_discount' => '0',
    'min_duration' => '0',
    'contacts_per_hour' => '0',
    'gift' => '',
    'has_girlfriend' => 'false',
    'online_booking' => 'false',
    'is_starting_price' => 'false',
];

// Конвертируем в query string для POST запроса
$queryString = http_build_query($postData);

// Выполняем HTTP POST запрос к /draft эндпоинту
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/x-www-form-urlencoded',
            'X-Requested-With: XMLHttpRequest',
            'Accept: application/json'
        ],
        'content' => $queryString
    ]
]);

echo "📤 Отправляем POST запрос к /draft...\n";
$response = file_get_contents('http://spa.test/draft', false, $context);

if ($response === false) {
    echo "❌ Ошибка отправки запроса\n";
    echo "HTTP ошибка: " . error_get_last()['message'] . "\n";
} else {
    echo "✅ Запрос отправлен\n";
    echo "📋 Ответ: " . substr($response, 0, 200) . "...\n";
}

echo "\n📋 Проверьте логи Laravel для отладочных данных\n";