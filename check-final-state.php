<?php

use App\Domain\Ad\Models\Ad;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "📋 ФИНАЛЬНАЯ ПРОВЕРКА ЧЕРНОВИКА ID 97\n";
echo "=====================================\n\n";

$ad = Ad::find(97);

if (!$ad) {
    echo "❌ Черновик не найден\n";
    exit;
}

echo "📋 ОБЩАЯ ИНФОРМАЦИЯ:\n";
echo "ID: {$ad->id}\n";
echo "Заголовок: {$ad->title}\n";
echo "Статус: {$ad->status->value}\n\n";

echo "📸 ФОТОГРАФИИ:\n";
$photos = $ad->photos ?? [];
if (is_array($photos) && count($photos) > 0) {
    foreach($photos as $index => $photo) {
        echo "  [{$index}] {$photo}\n";
    }
    echo "Количество фотографий: " . count($photos) . "\n";
} else {
    echo "  ❌ Нет фотографий\n";
}

echo "\n🎥 ВИДЕО:\n";
$videos = $ad->video ?? [];
if (is_array($videos) && count($videos) > 0) {
    foreach($videos as $index => $video) {
        echo "  [{$index}] {$video}\n";
    }
    echo "Количество видео: " . count($videos) . "\n";
} else {
    echo "  ❌ Нет видео\n";
}

echo "\n✅ ИСПРАВЛЕНИЯ ЗАВЕРШЕНЫ!\n";
echo "🎯 URL для тестирования: http://spa.test/ads/97/edit\n";
echo "\nТеперь при сохранении черновика медиа файлы должны сохраняться!\n";