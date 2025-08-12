<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;

// Ищем пользователей с именем Анна
$users = User::where('name', 'like', '%Анна%')
    ->orWhere('name', 'like', '%Anna%')
    ->get();

echo "=== ПОЛЬЗОВАТЕЛИ С ИМЕНЕМ АННА ===\n\n";

foreach ($users as $user) {
    echo "Пользователь: {$user->name} (ID: {$user->id}, Email: {$user->email})\n";
    echo "----------------------------------------\n";
    
    // Получаем объявления пользователя
    $ads = Ad::where('user_id', $user->id)->get();
    echo "Всего объявлений: {$ads->count()}\n\n";
    
    foreach ($ads as $ad) {
        echo "  Объявление ID {$ad->id}: {$ad->title}\n";
        echo "  Статус: {$ad->status->value}\n";
        
        // Фотографии
        if ($ad->photos) {
            $photos = json_decode($ad->photos, true);
            if ($photos && count($photos) > 0) {
                $photoCount = is_array($photos) ? count($photos) : 0;
                echo "  Фото ({$photoCount} шт.):\n";
                foreach ($photos as $i => $photo) {
                    $photoPath = is_array($photo) ? json_encode($photo) : $photo;
                    echo "    " . ($i + 1) . ". {$photoPath}\n";
                    
                    // Проверяем существование файла
                    if (!is_array($photo)) {
                        $fullPath = storage_path('app/public' . str_replace('/storage', '', $photo));
                        if (file_exists($fullPath)) {
                            $size = round(filesize($fullPath) / 1024, 2);
                            echo "       ✓ Файл существует ({$size} KB)\n";
                        } else {
                            echo "       ✗ Файл не найден!\n";
                        }
                    }
                }
            } else {
                echo "  Фото: нет\n";
            }
        }
        
        // Видео
        if ($ad->video) {
            $videos = json_decode($ad->video, true);
            if ($videos && count($videos) > 0) {
                $videoCount = is_array($videos) ? count($videos) : 0;
                echo "  Видео ({$videoCount} шт.):\n";
                foreach ($videos as $i => $video) {
                    $videoPath = is_array($video) ? json_encode($video) : $video;
                    echo "    " . ($i + 1) . ". {$videoPath}\n";
                    
                    // Проверяем существование файла
                    if (!is_array($video)) {
                        $fullPath = storage_path('app/public' . str_replace('/storage', '', $video));
                        if (file_exists($fullPath)) {
                            $size = round(filesize($fullPath) / 1024 / 1024, 2);
                            echo "       ✓ Файл существует ({$size} MB)\n";
                        } else {
                            echo "       ✗ Файл не найден!\n";
                        }
                    }
                }
            }
        }
        
        echo "\n";
    }
    
    echo "========================================\n\n";
}

// Статистика по всем медиа файлам
echo "\n=== ОБЩАЯ СТАТИСТИКА МЕДИА ФАЙЛОВ ===\n";

$photosPath = storage_path('app/public/ads/photos');
$videosPath = storage_path('app/public/ads/videos');

if (is_dir($photosPath)) {
    $photoFiles = array_diff(scandir($photosPath), ['.', '..']);
    echo "Всего фото файлов на диске: " . count($photoFiles) . "\n";
    
    $totalPhotoSize = 0;
    foreach ($photoFiles as $file) {
        $totalPhotoSize += filesize($photosPath . '/' . $file);
    }
    echo "Общий размер фото: " . round($totalPhotoSize / 1024 / 1024, 2) . " MB\n";
}

if (is_dir($videosPath)) {
    $videoFiles = array_diff(scandir($videosPath), ['.', '..']);
    echo "Всего видео файлов на диске: " . count($videoFiles) . "\n";
    
    $totalVideoSize = 0;
    foreach ($videoFiles as $file) {
        $totalVideoSize += filesize($videosPath . '/' . $file);
    }
    echo "Общий размер видео: " . round($totalVideoSize / 1024 / 1024, 2) . " MB\n";
}