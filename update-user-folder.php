<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;

echo "📝 Обновление данных после переименования папки\n";
echo "===============================================\n\n";

// Обновляем пользователя
$user = User::find(1);
$user->update(['folder_name' => 'anna-1']);
echo "✅ User folder_name обновлен: {$user->folder_name}\n\n";

// Обновляем пути в объявлениях
$ads = Ad::where('user_id', 1)->get();
echo "📌 Найдено объявлений: " . $ads->count() . "\n\n";

foreach($ads as $ad) {
    $updated = false;
    
    // Обновляем photos
    if($ad->photos) {
        $photos = json_decode($ad->photos, true);
        if(is_array($photos)) {
            $newPhotos = array_map(function($path) {
                return str_replace('/users/1/', '/users/anna-1/', $path);
            }, $photos);
            $ad->photos = json_encode($newPhotos);
            $updated = true;
            echo "  📷 Обновлены пути к фото\n";
        }
    }
    
    // Обновляем video
    if($ad->video) {
        $videos = json_decode($ad->video, true);
        if(is_array($videos)) {
            $newVideos = array_map(function($path) {
                return str_replace('/users/1/', '/users/anna-1/', $path);
            }, $videos);
            $ad->video = json_encode($newVideos);
            $updated = true;
            echo "  📹 Обновлены пути к видео\n";
        }
    }
    
    // Обновляем user_folder
    $ad->user_folder = 'anna-1';
    $ad->save();
    
    echo "✅ Ad {$ad->id} обновлен\n";
}

echo "\n✅ Все данные успешно обновлены!\n";