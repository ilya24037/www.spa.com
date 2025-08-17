<?php

use App\Domain\Ad\Models\Ad;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "=== ИСПРАВЛЕНИЕ ПУСТЫХ ОБЪЕКТОВ В PHOTOS ===\n\n";

// Находим все объявления
$ads = Ad::all();
$fixed = 0;

foreach ($ads as $ad) {
    if (!$ad->photos) continue;
    
    $photos = is_string($ad->photos) ? json_decode($ad->photos, true) : $ad->photos;
    
    if (!is_array($photos)) continue;
    
    $hasEmptyObjects = false;
    $validPhotos = [];
    
    foreach ($photos as $photo) {
        if (is_array($photo) && empty($photo)) {
            // Это пустой объект {}
            $hasEmptyObjects = true;
        } elseif (is_array($photo) && !empty($photo)) {
            // Объект с данными - извлекаем URL
            $url = $photo['preview'] ?? $photo['url'] ?? $photo['src'] ?? null;
            if ($url) {
                $validPhotos[] = $url;
            }
        } elseif (is_string($photo) && !empty($photo)) {
            // Обычная строка URL
            $validPhotos[] = $photo;
        }
    }
    
    if ($hasEmptyObjects) {
        echo "ID {$ad->id}: {$ad->title}\n";
        echo "  Было: " . substr($ad->photos, 0, 100) . "\n";
        
        // Сохраняем только валидные фото или пустой массив
        $ad->photos = json_encode($validPhotos);
        $ad->save();
        
        echo "  Стало: " . substr($ad->photos, 0, 100) . "\n\n";
        $fixed++;
    }
}

echo "✅ Исправлено объявлений: $fixed\n";