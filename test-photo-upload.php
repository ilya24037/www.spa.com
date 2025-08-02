<?php
/**
 * Тестовый скрипт для проверки новой архитектуры загрузки фото
 * Запустите: php test-photo-upload.php
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\AdMediaService;
use App\Infrastructure\Media\MediaService;

// Проверка 1: Загрузка существующего объявления
echo "=== ПРОВЕРКА 1: Загрузка объявления ===\n";
$ad = Ad::find(166);
if ($ad) {
    echo "Объявление найдено: ID={$ad->id}\n";
    echo "Текущие фото: " . json_encode($ad->photos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "Объявление не найдено!\n";
    exit(1);
}

// Проверка 2: MediaService
echo "\n=== ПРОВЕРКА 2: MediaService ===\n";
try {
    $mediaService = app(MediaService::class);
    echo "MediaService загружен успешно\n";
    
    // Проверяем наличие методов
    if (method_exists($mediaService, 'processPhotoForStorage')) {
        echo "✓ Метод processPhotoForStorage существует\n";
    } else {
        echo "✗ Метод processPhotoForStorage НЕ найден!\n";
    }
} catch (Exception $e) {
    echo "Ошибка загрузки MediaService: " . $e->getMessage() . "\n";
}

// Проверка 3: AdMediaService
echo "\n=== ПРОВЕРКА 3: AdMediaService ===\n";
try {
    $adMediaService = app(AdMediaService::class);
    echo "AdMediaService загружен успешно\n";
    
    // Получаем статистику
    $stats = $adMediaService->getMediaStats($ad);
    echo "Статистика медиа:\n";
    echo json_encode($stats, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Ошибка загрузки AdMediaService: " . $e->getMessage() . "\n";
}

// Проверка 4: Тестовые данные фото
echo "\n=== ПРОВЕРКА 4: Добавление тестового фото ===\n";
$testPhotoData = [
    [
        'id' => 'test-' . uniqid(),
        'filename' => 'test-photo.jpg',
        'paths' => [
            'thumbnail' => 'public/ads/photos/thumbnail/test-photo.jpg',
            'medium' => 'public/ads/photos/medium/test-photo.jpg',
            'large' => 'public/ads/photos/large/test-photo.jpg'
        ],
        'preview' => '/storage/ads/photos/medium/test-photo.jpg',
        'size' => 1024000,
        'mime_type' => 'image/jpeg',
        'original_name' => 'test-photo.jpg',
        'uploaded_at' => now()->toIso8601String()
    ]
];

try {
    $adMediaService->syncPhotos($ad, $testPhotoData);
    echo "Тестовое фото добавлено успешно\n";
    
    // Перезагружаем объявление
    $ad->refresh();
    echo "Фото после добавления: " . json_encode($ad->photos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (Exception $e) {
    echo "Ошибка добавления фото: " . $e->getMessage() . "\n";
}

echo "\n=== ТЕСТ ЗАВЕРШЕН ===\n";