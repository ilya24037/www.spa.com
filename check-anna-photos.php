<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;

echo "🔍 Поиск фото пользователя anna@spa.test\n";
echo "==========================================\n\n";

// Находим пользователя
$user = User::where('email', 'anna@spa.test')->first();

if (!$user) {
    echo "❌ Пользователь anna@spa.test не найден\n";
    exit;
}

echo "✅ Пользователь найден:\n";
echo "   ID: {$user->id}\n";
echo "   Имя: {$user->name}\n";
echo "   Email: {$user->email}\n\n";

// Проверяем объявления пользователя
$ads = Ad::where('user_id', $user->id)->get();

echo "📝 Объявления пользователя: " . $ads->count() . "\n\n";

foreach ($ads as $ad) {
    echo "📌 Объявление ID: {$ad->id}\n";
    echo "   Название: {$ad->title}\n";
    echo "   Статус: " . (is_object($ad->status) ? $ad->status->value : $ad->status) . "\n";
    
    if ($ad->user_folder) {
        echo "   📁 Папка пользователя: {$ad->user_folder}\n";
    }
    
    // Проверяем фото
    if ($ad->photos) {
        $photos = json_decode($ad->photos, true);
        if (is_array($photos) && count($photos) > 0) {
            echo "   📷 Фотографии (" . count($photos) . " шт.):\n";
            
            // Показываем первые 3 фото
            for ($i = 0; $i < min(3, count($photos)); $i++) {
                echo "      " . ($i + 1) . ". {$photos[$i]}\n";
                
                // Определяем структуру
                if (str_contains($photos[$i], '/users/')) {
                    echo "         ✅ Новая структура (users/{$user->id}/ads/{$ad->id}/)\n";
                } elseif (str_contains($photos[$i], '/ads/photos/')) {
                    echo "         ⚠️  Старая структура (ads/photos/)\n";
                }
                
                // Проверяем физическое наличие
                $path = str_replace('/storage/', '', $photos[$i]);
                $fullPath = storage_path('app/public/' . $path);
                if (file_exists($fullPath)) {
                    $size = round(filesize($fullPath) / 1024, 2);
                    echo "         ✅ Файл существует ({$size} KB)\n";
                } else {
                    echo "         ❌ Файл НЕ найден!\n";
                }
            }
            
            if (count($photos) > 3) {
                echo "      ... и еще " . (count($photos) - 3) . " фото\n";
            }
        } else {
            echo "   📷 Нет фотографий\n";
        }
    } else {
        echo "   📷 Нет фотографий\n";
    }
    
    // Проверяем видео
    if ($ad->video) {
        $videos = json_decode($ad->video, true);
        if (is_array($videos) && count($videos) > 0) {
            echo "   📹 Видео (" . count($videos) . " шт.):\n";
            echo "      1. {$videos[0]}\n";
            
            if (str_contains($videos[0], '/users/')) {
                echo "         ✅ Новая структура\n";
            } else {
                echo "         ⚠️  Старая структура\n";
            }
        }
    }
    
    echo "\n";
}

// Проверяем физическое наличие папки пользователя
$userFolderPath = storage_path("app/public/users/{$user->id}");
if (is_dir($userFolderPath)) {
    echo "📂 Папка пользователя существует: users/{$user->id}/\n";
    
    // Показываем структуру папок
    $adsFolders = glob($userFolderPath . '/ads/*', GLOB_ONLYDIR);
    if (count($adsFolders) > 0) {
        echo "   Папки объявлений:\n";
        foreach ($adsFolders as $folder) {
            $adId = basename($folder);
            echo "      - ads/{$adId}/\n";
            
            // Считаем файлы
            $photoCount = count(glob($folder . '/photos/original/*'));
            $videoCount = count(glob($folder . '/videos/*'));
            
            if ($photoCount > 0) {
                echo "        📷 {$photoCount} фото\n";
            }
            if ($videoCount > 0) {
                echo "        📹 {$videoCount} видео\n";
            }
        }
    }
} else {
    echo "📂 Папка пользователя НЕ существует: users/{$user->id}/\n";
    echo "   Возможно, файлы еще не мигрированы в новую структуру\n";
}