<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Проверка исправления ошибки clients.join...\n";

// Найдем любой черновик
$ad = \App\Domain\Ad\Models\Ad::where('status', 'draft')->first();

if (!$ad) {
    echo "Черновики не найдены\n";
    
    // Посмотрим все объявления
    $allAds = \App\Domain\Ad\Models\Ad::select('id', 'status', 'title')->get();
    echo "Все объявления:\n";
    foreach ($allAds as $a) {
        echo "ID: {$a->id}, Статус: {$a->status}, Название: {$a->title}\n";
    }
    exit(1);
}

echo "ID черновика: {$ad->id}\n";
echo "Статус: {$ad->status->value}\n";

// Проверяем поле clients
echo "Clients (raw): " . var_export($ad->clients, true) . "\n";

// Проверяем JSON декодирование
if (is_string($ad->clients)) {
    $decoded = json_decode($ad->clients, true);
    echo "Clients (decoded): " . var_export($decoded, true) . "\n";
    echo "JSON decode error: " . json_last_error_msg() . "\n";
} else {
    echo "Clients не является строкой\n";
}

// Проверяем видео
echo "Video (raw): " . var_export($ad->video, true) . "\n";

if (is_string($ad->video)) {
    $decoded = json_decode($ad->video, true);
    echo "Video (decoded): " . var_export($decoded, true) . "\n";
    echo "JSON decode error: " . json_last_error_msg() . "\n";
} else {
    echo "Video не является строкой\n";
}

echo "\nТест завершён\n";