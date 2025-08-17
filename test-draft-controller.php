<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Тестирование контроллера showDraft...\n";

// Имитируем авторизацию
\Auth::login(\App\Domain\User\Models\User::find(1));

// Получаем черновик 200
$ad = \App\Domain\Ad\Models\Ad::find(200);

if (!$ad) {
    echo "Черновик 200 не найден\n";
    exit(1);
}

echo "Исходные данные из БД:\n";
echo "ID: {$ad->id}\n";
echo "Clients (raw): {$ad->clients}\n";
echo "Video (raw): {$ad->video}\n";

// Имитируем обработку данных как в showDraft методе
$adData = $ad->toArray();

$jsonFields = ['clients', 'service_location', 'service_provider', 'is_starting_price', 
              'photos', 'video', 'show_photos_in_gallery', 'allow_download_photos', 'watermark_photos', 
              'custom_travel_areas', 'working_days', 'working_hours', 'services', 'features', 'schedule', 
              'additional_services', 'geo', 'media_settings', 'prices'];

foreach ($jsonFields as $field) {
    if (isset($adData[$field]) && is_string($adData[$field])) {
        $decoded = json_decode($adData[$field], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $adData[$field] = $decoded;
        } else {
            echo "JSON error for field {$field}: " . json_last_error_msg() . "\n";
            $adData[$field] = [];
        }
    } elseif (!isset($adData[$field])) {
        $adData[$field] = [];
    }
}

echo "\nОбработанные данные для frontend:\n";
echo "Clients: " . var_export($adData['clients'], true) . "\n";
echo "Video: " . var_export($adData['video'], true) . "\n";

// Проверим что join() теперь работает
if (is_array($adData['clients'])) {
    echo "Clients join: " . implode(', ', $adData['clients']) . "\n";
} else {
    echo "Clients не является массивом!\n";
}

if (is_array($adData['video'])) {
    echo "Video count: " . count($adData['video']) . "\n";
    if (count($adData['video']) > 0) {
        echo "First video URL: {$adData['video'][0]}\n";
    }
} else {
    echo "Video не является массивом!\n";
}

echo "\nТест завершён\n";