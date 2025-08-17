<?php

use App\Domain\Ad\Models\Ad;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "=== ПРОВЕРКА ФОТО В ЧЕРНОВИКАХ И АКТИВНЫХ ОБЪЯВЛЕНИЯХ ===\n\n";

// Проверяем черновики
echo "ЧЕРНОВИКИ (draft):\n";
$drafts = Ad::where('status', 'draft')
    ->whereNotNull('photos')
    ->select(['id', 'title', 'photos', 'status'])
    ->limit(3)
    ->get();

foreach ($drafts as $ad) {
    echo "  ID {$ad->id}: {$ad->title}\n";
    
    $photos = is_string($ad->photos) ? json_decode($ad->photos, true) : $ad->photos;
    if (is_array($photos) && !empty($photos)) {
        $firstPhoto = $photos[0];
        echo "    Первое фото тип: " . gettype($firstPhoto) . "\n";
        if (is_array($firstPhoto)) {
            echo "    Ключи в фото: " . implode(', ', array_keys($firstPhoto)) . "\n";
        } elseif (is_string($firstPhoto)) {
            echo "    Строка URL: $firstPhoto\n";
        }
    } else {
        echo "    Фото пусто или не массив\n";
    }
    echo "\n";
}

// Проверяем активные
echo "\nАКТИВНЫЕ (active):\n";
$active = Ad::where('status', 'active')
    ->whereNotNull('photos')
    ->select(['id', 'title', 'photos', 'status'])
    ->limit(3)
    ->get();

foreach ($active as $ad) {
    echo "  ID {$ad->id}: {$ad->title}\n";
    
    $photos = is_string($ad->photos) ? json_decode($ad->photos, true) : $ad->photos;
    if (is_array($photos) && !empty($photos)) {
        $firstPhoto = $photos[0];
        echo "    Первое фото тип: " . gettype($firstPhoto) . "\n";
        if (is_array($firstPhoto)) {
            echo "    Ключи в фото: " . implode(', ', array_keys($firstPhoto)) . "\n";
        } elseif (is_string($firstPhoto)) {
            echo "    Строка URL: $firstPhoto\n";
        }
    } else {
        echo "    Фото пусто или не массив\n";
    }
    echo "\n";
}

echo "✅ Анализ завершен\n";
echo "\nЕсли структура разная, проблема в процессе публикации.\n";
echo "Если одинаковая, проблема в отображении во frontend.\n";