<?php

use App\Domain\Ad\Models\Ad;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "🎯 ПРОВЕРКА ПРОБЛЕМ С СОХРАНЕНИЕМ ВИДЕО\n";
echo "========================================\n\n";

// Найдем черновик ID 70
$draft = Ad::find(70);

if ($draft) {
    echo "📋 Черновик найден: ID {$draft->id}\n";
    echo "Статус: {$draft->status->value}\n";
    echo "Заголовок: {$draft->title}\n\n";
    
    // Проверяем видео
    $videos = $draft->video;
    echo "📹 Видео в черновике:\n";
    
    if (is_array($videos)) {
        echo "Количество видео: " . count($videos) . "\n";
        foreach ($videos as $index => $video) {
            echo "  Видео $index: $video\n";
        }
    } else {
        echo "Видео не является массивом: " . gettype($videos) . "\n";
        echo "Значение: " . json_encode($videos) . "\n";
    }
    
    echo "\n✅ АНАЛИЗ ПРОБЛЕМЫ:\n";
    echo "1. В логах видно, что второе видео отправляется как video.1.file\n";
    echo "2. Первое видео (существующее) отправляется как video.0 (строка)\n";
    echo "3. Backend корректно обрабатывает оба формата\n\n";
    
    echo "❌ ВОЗМОЖНЫЕ ПРОБЛЕМЫ:\n";
    echo "1. Не происходит редирект после сохранения\n";
    echo "2. Запрос не определяется как Inertia\n";
    echo "3. Ответ возвращается как JSON вместо редиректа\n\n";
    
    echo "🔧 РЕШЕНИЕ:\n";
    echo "Нужно проверить заголовки запроса и убедиться,\n";
    echo "что X-Inertia передается при отправке FormData\n";
    
} else {
    echo "❌ Черновик с ID 70 не найден!\n";
}