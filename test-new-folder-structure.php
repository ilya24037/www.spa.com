<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Infrastructure\Media\PathGenerator;
use App\Domain\User\Models\User;
use App\Support\Helpers\Transliterator;

echo "🧪 Тестирование новой структуры папок\n";
echo "=====================================\n\n";

// Тест 1: Проверка транслитерации
echo "1️⃣ Тест транслитерации имен:\n";
$testNames = [
    'Анна Петрова' => 1,
    'Иван Сидоров' => 2, 
    'Мария Козлова' => 3,
    'Александр Васильев' => 4,
    'Екатерина Николаева' => 5,
    'Владимир Путин' => 6,
    '张三' => 7, // Китайское имя
    '' => 8, // Пустое имя
];

foreach ($testNames as $name => $id) {
    $folderName = Transliterator::generateUserFolderName($name, $id);
    echo "   {$name} → {$folderName}\n";
}

echo "\n2️⃣ Тест PathGenerator:\n";

// Для пользователя Anna (ID=1)
$userId = 1;
$adId = 178;

// Генерация путей
$photoPath = PathGenerator::adPhotoPath($userId, $adId, 'jpg', 'original');
echo "   Путь к фото: {$photoPath}\n";

$thumbPath = PathGenerator::adPhotoPath($userId, $adId, 'jpg', 'thumb');
echo "   Путь к миниатюре: {$thumbPath}\n";

$videoPath = PathGenerator::adVideoPath($userId, $adId, 'mp4');
echo "   Путь к видео: {$videoPath}\n";

$profilePhotoPath = PathGenerator::userProfilePhotoPath($userId, 'jpg');
echo "   Путь к фото профиля: {$profilePhotoPath}\n";

echo "\n3️⃣ Тест извлечения ID из путей:\n";

$testPaths = [
    '/storage/users/anna-1/ads/178/photos/original/uuid.jpg',
    '/storage/users/ivan-2/ads/250/videos/uuid.mp4',
    '/storage/users/maria-3/ads/350/photos/thumb/uuid.jpg',
    '/storage/users/1/ads/178/photos/original/uuid.jpg', // Старый формат
];

foreach ($testPaths as $path) {
    $ids = PathGenerator::extractIdsFromPath($path);
    if ($ids) {
        echo "   {$path}\n";
        echo "      → user_id: {$ids['user_id']}, ad_id: {$ids['ad_id']}\n";
    } else {
        echo "   {$path} → НЕ РАСПОЗНАН\n";
    }
}

echo "\n4️⃣ Проверка существующих пользователей:\n";

$users = User::limit(5)->get();
foreach ($users as $user) {
    // Генерируем имя папки, если его нет
    if (empty($user->folder_name)) {
        $folderName = Transliterator::generateUserFolderName($user->name, $user->id);
        $user->update(['folder_name' => $folderName]);
    } else {
        $folderName = $user->folder_name;
    }
    
    echo "   User ID {$user->id}: {$user->name} → папка: {$folderName}\n";
    
    // Проверяем физическое наличие папки
    $folderPath = storage_path("app/public/users/{$folderName}");
    if (is_dir($folderPath)) {
        echo "      ✅ Папка существует\n";
    } else {
        echo "      ❌ Папка не существует\n";
    }
}

echo "\n✅ Тестирование завершено!\n";