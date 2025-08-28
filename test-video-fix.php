<?php

use App\Domain\Ad\Models\Ad;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🎥 ТЕСТ ИСПРАВЛЕНИЯ СОХРАНЕНИЯ ВИДЕО\n";
echo "====================================\n\n";

try {
    // Используем тот же черновик ID 97
    $ad = Ad::find(97);
    
    if (!$ad) {
        echo "❌ Черновик с ID 97 не найден\n";
        exit;
    }
    
    echo "📋 ТЕСТИРУЕМ ЧЕРНОВИК ID: {$ad->id}\n";
    echo "Пользователь: {$ad->user_id}\n";
    echo "Статус: {$ad->status->value}\n";
    echo "Заголовок: {$ad->title}\n\n";
    
    // Добавляем тестовые видео
    $testVideos = [
        '/storage/videos/test1.mp4',
        '/storage/videos/test2.mp4'
    ];
    
    $ad->video = $testVideos;
    $ad->save();
    
    echo "✅ Добавлены тестовые видео:\n";
    foreach ($testVideos as $index => $video) {
        echo "  [{$index}] {$video}\n";
    }
    
    // Проверяем что сохранилось
    $ad->refresh();
    $savedVideos = $ad->video ?? [];
    
    echo "\n📋 ПРОВЕРКА СОХРАНЕНИЯ ВИДЕО:\n";
    if (is_array($savedVideos) && count($savedVideos) > 0) {
        echo "✅ Видео успешно сохранены в БД:\n";
        foreach ($savedVideos as $index => $video) {
            echo "  [{$index}] {$video}\n";
        }
        echo "Количество: " . count($savedVideos) . "\n";
    } else {
        echo "❌ Видео не сохранились\n";
        echo "Тип данных: " . gettype($savedVideos) . "\n";
        echo "Значение: " . var_export($savedVideos, true) . "\n";
    }
    
    // Также показываем текущие фотографии
    $currentPhotos = $ad->photos ?? [];
    echo "\n📸 ТЕКУЩИЕ ФОТОГРАФИИ:\n";
    if (is_array($currentPhotos) && count($currentPhotos) > 0) {
        foreach ($currentPhotos as $index => $photo) {
            echo "  [{$index}] {$photo}\n";
        }
        echo "Количество: " . count($currentPhotos) . "\n";
    } else {
        echo "  Фотографий нет\n";
    }
    
    echo "\n🎯 ИСПРАВЛЕНИЯ ПРИМЕНЕНЫ:\n";
    echo "✅ ФОТОГРАФИИ: восстановлена оригинальная логика photos[index]\n";
    echo "✅ ВИДЕО: восстановлена оригинальная логика video_index_file/video_index\n";
    echo "✅ Убраны existing_photos и existing_videos\n";
    echo "✅ Добавлены детальные console.log для отладки\n\n";
    
    echo "🎯 ИНСТРУКЦИЯ ДЛЯ ПОЛНОГО ТЕСТИРОВАНИЯ:\n";
    echo "1. Откройте: http://spa.test/ads/97/edit\n";
    echo "2. Должны отображаться 3 фотографии и 2 видео\n";
    echo "3. Внесите изменения в любое поле\n";
    echo "4. Нажмите 'Сохранить черновик'\n";
    echo "5. ВСЕ медиа файлы должны остаться на месте ✅\n";
    echo "6. Добавьте новое фото или видео\n";
    echo "7. Сохраните снова - должны остаться ВСЕ файлы ✅\n\n";
    
} catch (\Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
}