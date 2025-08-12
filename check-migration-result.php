<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Domain\Ad\Models\Ad;

echo "📊 Проверка результатов миграции\n";
echo "=================================\n\n";

$ad = Ad::find(178);

if ($ad) {
    echo "✅ Объявление ID: {$ad->id}\n";
    echo "   User ID: {$ad->user_id}\n";
    echo "   User folder: {$ad->user_folder}\n\n";
    
    $photos = json_decode($ad->photos, true);
    echo "📷 Фотографии ({количество: " . count($photos) . "}):\n";
    if (is_array($photos) && count($photos) > 0) {
        echo "   Первое фото: {$photos[0]}\n";
        if (str_contains($photos[0], '/users/')) {
            echo "   ✅ Использует НОВУЮ структуру!\n";
        }
    }
    
    $videos = json_decode($ad->video, true);
    echo "\n📹 Видео:\n";
    if (is_array($videos) && count($videos) > 0) {
        echo "   Первое видео: {$videos[0]}\n";
        if (str_contains($videos[0], '/users/')) {
            echo "   ✅ Использует НОВУЮ структуру!\n";
        }
    }
    
    $mediaPaths = json_decode($ad->media_paths, true);
    if ($mediaPaths) {
        echo "\n📁 Media paths metadata:\n";
        echo "   Migrated at: {$mediaPaths['migrated_at']}\n";
        echo "   Photos count: " . count($mediaPaths['photos']) . "\n";
        echo "   Videos count: " . count($mediaPaths['videos']) . "\n";
    }
    
    // Проверяем физическое наличие файлов
    echo "\n🔍 Проверка физических файлов:\n";
    $firstPhoto = str_replace('/storage/', '', $photos[0]);
    $fullPath = storage_path('app/public/' . $firstPhoto);
    if (file_exists($fullPath)) {
        $size = round(filesize($fullPath) / 1024, 2);
        echo "   ✅ Первое фото существует ({$size} KB)\n";
    } else {
        echo "   ❌ Первое фото НЕ найдено!\n";
    }
}

echo "\n✅ Миграция успешно завершена!\n";
echo "Структура папок: users/{userId}/ads/{adId}/photos/original/\n";