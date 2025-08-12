<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Domain\Ad\Models\Ad;
use App\Infrastructure\Media\PathGenerator;

echo "🔍 Проверка новой структуры папок\n";
echo "==================================\n\n";

// Проверяем существующие объявления
$adsWithPhotos = Ad::whereNotNull('photos')
    ->where('photos', '!=', '[]')
    ->count();

$adsWithVideos = Ad::whereNotNull('video')
    ->where('video', '!=', '[]')
    ->count();

echo "📊 Статистика:\n";
echo "- Объявлений с фото: {$adsWithPhotos}\n";
echo "- Объявлений с видео: {$adsWithVideos}\n\n";

// Проверяем PathGenerator
echo "🧪 Тест PathGenerator:\n";
echo "----------------------\n";

$userId = 1;
$adId = 178;

$photoPath = PathGenerator::adPhotoPath($userId, $adId, 'jpg', 'original');
echo "Путь для фото: {$photoPath}\n";

$thumbPath = PathGenerator::generateVariantPath($photoPath, 'thumb');
echo "Путь для thumb: {$thumbPath}\n";

$videoPath = PathGenerator::adVideoPath($userId, $adId, 'mp4');
echo "Путь для видео: {$videoPath}\n";

$userBasePath = PathGenerator::getUserBasePath($userId);
echo "Базовый путь пользователя: {$userBasePath}\n";

$adBasePath = PathGenerator::getAdBasePath($userId, $adId);
echo "Базовый путь объявления: {$adBasePath}\n\n";

// Проверяем извлечение ID из пути
$testPath = '/storage/users/1/ads/178/photos/original/test.jpg';
$ids = PathGenerator::extractIdsFromPath($testPath);
if ($ids) {
    echo "✅ Извлечение ID из пути работает:\n";
    echo "   User ID: {$ids['user_id']}, Ad ID: {$ids['ad_id']}\n\n";
}

// Проверяем определение типа пути
if (PathGenerator::isAdMediaPath($testPath)) {
    echo "✅ Определение типа пути работает\n\n";
}

// Показываем первое объявление с фото для примера миграции
$firstAd = Ad::whereNotNull('photos')
    ->where('photos', '!=', '[]')
    ->first();

if ($firstAd) {
    echo "📷 Пример объявления для миграции:\n";
    echo "   ID: {$firstAd->id}\n";
    echo "   User ID: {$firstAd->user_id}\n";
    $photos = json_decode($firstAd->photos, true);
    if (is_array($photos) && count($photos) > 0) {
        echo "   Первое фото: {$photos[0]}\n";
        
        // Проверяем, не мигрировано ли уже
        $isNewStructure = str_contains($photos[0], '/users/') && PathGenerator::isAdMediaPath($photos[0]);
        if ($isNewStructure) {
            echo "   ✅ Уже использует новую структуру\n";
        } else {
            echo "   ⚠️  Использует старую структуру (ads/photos/)\n";
            
            // Показываем, как будет выглядеть после миграции
            $extension = pathinfo($photos[0], PATHINFO_EXTENSION);
            $newPath = PathGenerator::adPhotoPath($firstAd->user_id, $firstAd->id, $extension, 'original');
            echo "   Новый путь будет: /storage/{$newPath}\n";
        }
    }
}

echo "\n✅ Тест завершен успешно!\n";