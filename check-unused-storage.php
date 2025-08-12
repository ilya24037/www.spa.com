<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Domain\Ad\Models\Ad;
use Illuminate\Support\Facades\Storage;

echo "🔍 Анализ использования storage/app/public\n";
echo "==========================================\n\n";

// 1. Проверка папки ads/
echo "📁 /ads/ (старая структура):\n";
$adsPhotosPath = 'ads/photos';
$adsVideosPath = 'ads/videos';

if (Storage::disk('public')->exists($adsPhotosPath)) {
    $photoFiles = Storage::disk('public')->allFiles($adsPhotosPath);
    $photoSize = 0;
    foreach ($photoFiles as $file) {
        $photoSize += Storage::disk('public')->size($file);
    }
    echo "   📷 /ads/photos/: " . count($photoFiles) . " файлов (" . round($photoSize / 1024 / 1024, 2) . " MB)\n";
} else {
    echo "   📷 /ads/photos/: папка не существует\n";
}

if (Storage::disk('public')->exists($adsVideosPath)) {
    $videoFiles = Storage::disk('public')->allFiles($adsVideosPath);
    $videoSize = 0;
    foreach ($videoFiles as $file) {
        $videoSize += Storage::disk('public')->size($file);
    }
    echo "   📹 /ads/videos/: " . count($videoFiles) . " файлов (" . round($videoSize / 1024 / 1024, 2) . " MB)\n";
} else {
    echo "   📹 /ads/videos/: папка не существует\n";
}

// Проверяем использование в БД
$oldStructureAds = Ad::where(function($query) {
    $query->where('photos', 'LIKE', '%/ads/photos/%')
          ->orWhere('video', 'LIKE', '%/ads/videos/%');
})->get();

echo "   📊 Объявлений со ссылками на /ads/: " . $oldStructureAds->count() . "\n";

if ($oldStructureAds->count() > 0) {
    echo "   ⚠️  Эти объявления все еще используют старую структуру:\n";
    foreach ($oldStructureAds->take(5) as $ad) {
        echo "      - ID {$ad->id}: {$ad->title}\n";
    }
    if ($oldStructureAds->count() > 5) {
        echo "      ... и еще " . ($oldStructureAds->count() - 5) . " объявлений\n";
    }
}

// 2. Проверка папки masters/
echo "\n📁 /masters/ (старая структура мастеров):\n";
$mastersPath = 'masters';

if (Storage::disk('public')->exists($mastersPath)) {
    $mastersFolders = Storage::disk('public')->directories($mastersPath);
    $totalMastersSize = 0;
    $totalMastersFiles = 0;
    
    foreach ($mastersFolders as $folder) {
        $files = Storage::disk('public')->allFiles($folder);
        $totalMastersFiles += count($files);
        foreach ($files as $file) {
            $totalMastersSize += Storage::disk('public')->size($file);
        }
    }
    
    echo "   📂 Папок мастеров: " . count($mastersFolders) . "\n";
    echo "   📄 Всего файлов: {$totalMastersFiles} (" . round($totalMastersSize / 1024, 2) . " KB)\n";
} else {
    echo "   Папка не существует\n";
}

// Проверяем использование в БД
$mastersReferences = Ad::where(function($query) {
    $query->where('photos', 'LIKE', '%/masters/%')
          ->orWhere('video', 'LIKE', '%/masters/%');
})->count();

echo "   📊 Объявлений со ссылками на /masters/: {$mastersReferences}\n";

// 3. Проверка новой структуры users/
echo "\n📁 /users/ (новая структура):\n";
$usersPath = 'users';

if (Storage::disk('public')->exists($usersPath)) {
    $usersFolders = Storage::disk('public')->directories($usersPath);
    $totalUsersSize = 0;
    $totalUsersFiles = 0;
    $totalAds = 0;
    
    foreach ($usersFolders as $userFolder) {
        $adsFolders = Storage::disk('public')->directories($userFolder . '/ads');
        $totalAds += count($adsFolders);
        
        $files = Storage::disk('public')->allFiles($userFolder);
        $totalUsersFiles += count($files);
        foreach ($files as $file) {
            $totalUsersSize += Storage::disk('public')->size($file);
        }
    }
    
    echo "   📂 Папок пользователей: " . count($usersFolders) . "\n";
    echo "   📦 Папок объявлений: {$totalAds}\n";
    echo "   📄 Всего файлов: {$totalUsersFiles} (" . round($totalUsersSize / 1024 / 1024, 2) . " MB)\n";
}

// Проверяем использование новой структуры в БД
$newStructureAds = Ad::where(function($query) {
    $query->where('photos', 'LIKE', '%/users/%')
          ->orWhere('video', 'LIKE', '%/users/%');
})->count();

echo "   📊 Объявлений с новой структурой: {$newStructureAds}\n";

// 4. Итоги
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 ИТОГИ:\n";
echo str_repeat("=", 50) . "\n\n";

echo "🗑️  МОЖНО УДАЛИТЬ:\n";
if (isset($photoFiles) && count($photoFiles) > 0 && $oldStructureAds->count() == 0) {
    echo "   ✅ /ads/photos/ - " . count($photoFiles) . " файлов (" . round($photoSize / 1024 / 1024, 2) . " MB)\n";
    echo "      Эти файлы больше не используются в БД\n";
} elseif (isset($photoFiles) && count($photoFiles) > 0) {
    echo "   ⚠️  /ads/photos/ - нельзя удалить, используется в " . $oldStructureAds->count() . " объявлениях\n";
}

if (isset($videoFiles) && count($videoFiles) > 0 && $oldStructureAds->count() == 0) {
    echo "   ✅ /ads/videos/ - " . count($videoFiles) . " файлов (" . round($videoSize / 1024 / 1024, 2) . " MB)\n";
    echo "      Эти файлы больше не используются в БД\n";
}

if ($mastersReferences == 0 && isset($totalMastersFiles) && $totalMastersFiles > 0) {
    echo "   ✅ /masters/ - " . $totalMastersFiles . " файлов (" . round($totalMastersSize / 1024, 2) . " KB)\n";
    echo "      Эта папка больше не используется\n";
} elseif ($mastersReferences > 0) {
    echo "   ⚠️  /masters/ - нельзя удалить, используется в {$mastersReferences} объявлениях\n";
}

echo "\n💾 НУЖНО СОХРАНИТЬ:\n";
echo "   ✅ /users/ - активная структура с {$newStructureAds} объявлениями\n";

// Проверка других файлов в storage/app
echo "\n📄 ДРУГИЕ ФАЙЛЫ В storage/app:\n";
$rootFiles = [
    'project-report-2025-06-13.txt',
    'project-report-2025-06-14.txt'
];

foreach ($rootFiles as $file) {
    $path = '../' . $file; // Относительно public/
    if (file_exists(storage_path('app/' . $file))) {
        $size = filesize(storage_path('app/' . $file));
        echo "   📄 {$file} (" . round($size / 1024, 2) . " KB) - можно удалить (старые отчеты)\n";
    }
}