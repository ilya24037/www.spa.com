<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Симулируем работу контроллера
$ad = App\Domain\Ad\Models\Ad::find(52);
if(!$ad) {
    die('Ad not found');
}

// Готовим данные как в контроллере
$masterData = [
    'id' => $ad->id,
    'name' => $ad->title ?? $ad->name ?? 'Мастер',
    'avatar' => $ad->avatar ?? null,
    'specialty' => $ad->specialty ?? 'Массаж',
    'description' => $ad->description,
    'rating' => $ad->rating ?? 4.5,
    'reviews_count' => $ad->reviews_count ?? 0,
    'services' => [],
    'photos' => [],
    'location' => $ad->address ?? $ad->district ?? 'Москва',
    'price' => $ad->price ?? $ad->price_from ?? 2000,
    'phone' => $ad->phone,
    'whatsapp' => $ad->whatsapp,
    'telegram' => $ad->telegram,
    'experience' => $ad->experience ?? '5+ лет',
    'completion_rate' => '98%',
    'geo' => $ad->geo,
    'parameters' => $ad->parameters,
    'amenities' => $ad->amenities,
    'comfort' => $ad->comfort,
];

// Выводим для проверки
echo 'Master Data:' . PHP_EOL;
echo 'name: ' . $masterData['name'] . PHP_EOL;
echo 'description: ' . substr($masterData['description'] ?? '', 0, 100) . PHP_EOL;
echo 'location: ' . $masterData['location'] . PHP_EOL;
echo 'specialty: ' . $masterData['specialty'] . PHP_EOL;

// Проверяем JSON кодировку
$json = json_encode($masterData);
if (json_last_error() === JSON_ERROR_NONE) {
    echo PHP_EOL . 'JSON encode: OK' . PHP_EOL;
    // Проверяем что после декодирования данные не теряются
    $decoded = json_decode($json, true);
    echo 'После JSON decode, name: ' . $decoded['name'] . PHP_EOL;
} else {
    echo PHP_EOL . 'JSON encode error: ' . json_last_error_msg() . PHP_EOL;
}
