<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Domain\Ad\Models\Ad;

echo "=== Проверка видео в объявлениях ===\n\n";

// Найдём объявления с видео
$ads = Ad::whereNotNull('video')
    ->where('video', '!=', '[]')
    ->select('id', 'title', 'status', 'video')
    ->limit(5)
    ->get();

if ($ads->isEmpty()) {
    echo "Объявлений с видео не найдено\n";
    exit;
}

echo "Найдено объявлений с видео: " . $ads->count() . "\n\n";

foreach ($ads as $ad) {
    echo "ID: {$ad->id}\n";
    echo "Название: {$ad->title}\n";
    echo "Статус: {$ad->status->value}\n";
    echo "Видео (raw JSON): " . substr($ad->video, 0, 200) . "...\n";
    
    $videos = json_decode($ad->video, true);
    if ($videos && is_array($videos)) {
        echo "Количество видео: " . count($videos) . "\n";
        foreach ($videos as $index => $video) {
            echo "  Видео " . ($index + 1) . ": " . $video . "\n";
            
            // Проверяем существование файла
            $fullPath = public_path(ltrim($video, '/'));
            if (file_exists($fullPath)) {
                echo "    ✓ Файл существует\n";
            } else {
                echo "    ✗ Файл НЕ найден по пути: " . $fullPath . "\n";
            }
        }
    } else {
        echo "Ошибка декодирования JSON\n";
    }
    
    echo str_repeat("-", 50) . "\n\n";
}

// Проверим черновики
echo "\n=== Проверка черновиков ===\n";
$drafts = Ad::where('status', 'draft')
    ->whereNotNull('video')
    ->where('video', '!=', '[]')
    ->count();
    
echo "Черновиков с видео: " . $drafts . "\n";