<?php

use App\Domain\Ad\Models\Ad;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "📸 ДОБАВЛЕНИЕ ТЕСТОВЫХ ФОТОГРАФИЙ К ЧЕРНОВИКУ\n";
echo "=============================================\n\n";

try {
    // Находим черновик ID 97
    $ad = Ad::find(97);
    
    if (!$ad) {
        echo "❌ Черновик с ID 97 не найден\n";
        exit;
    }
    
    echo "📋 Найден черновик ID: {$ad->id}\n";
    echo "Статус: {$ad->status->value}\n";
    echo "Заголовок: {$ad->title}\n\n";
    
    // Добавляем тестовые фотографии
    $testPhotos = [
        '/storage/photos/test1.jpg',
        '/storage/photos/test2.jpg',
        '/storage/photos/test3.jpg'
    ];
    
    $ad->photos = $testPhotos;
    $ad->save();
    
    echo "✅ Добавлены тестовые фотографии:\n";
    foreach ($testPhotos as $index => $photo) {
        echo "  [{$index}] {$photo}\n";
    }
    
    // Проверяем что сохранилось
    $ad->refresh();
    $savedPhotos = $ad->photos ?? [];
    
    echo "\n📋 ПРОВЕРКА СОХРАНЕНИЯ:\n";
    if (is_array($savedPhotos) && count($savedPhotos) > 0) {
        echo "✅ Фотографии успешно сохранены в БД:\n";
        foreach ($savedPhotos as $index => $photo) {
            echo "  [{$index}] {$photo}\n";
        }
        echo "Количество: " . count($savedPhotos) . "\n";
        
        echo "\n🎯 ТЕПЕРЬ МОЖНО ТЕСТИРОВАТЬ:\n";
        echo "1. Откройте: http://spa.test/ads/97/edit\n";
        echo "2. Должны отобразиться 3 тестовые фотографии\n";
        echo "3. Внесите изменения в описание и сохраните\n";
        echo "4. Фотографии должны остаться на месте ✅\n\n";
    } else {
        echo "❌ Фотографии не сохранились\n";
        echo "Тип данных: " . gettype($savedPhotos) . "\n";
        echo "Значение: " . var_export($savedPhotos, true) . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
}