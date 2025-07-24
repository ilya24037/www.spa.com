<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

// Настройка базы данных
$capsule = new DB;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'spa_massagist',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Создаем менеджер изображений
$imageManager = new ImageManager(new Driver());

/**
 * Функция для добавления фотографий мастера
 */
function addMasterPhotos($masterId, $photosPaths, $isMain = false) {
    global $imageManager;
    
    // Получаем информацию о мастере
    $master = DB::table('master_profiles')->where('id', $masterId)->first();
    if (!$master) {
        echo "❌ Мастер с ID {$masterId} не найден\n";
        return false;
    }
    
    // Создаем папку мастера если не существует
    $folderName = getFolderName($master->first_name, $master->last_name, $master->id);
    $masterDir = "storage/app/private/masters/{$folderName}";
    $photosDir = "{$masterDir}/photos";
    
    if (!is_dir($photosDir)) {
        mkdir($photosDir, 0755, true);
        echo "📁 Создана папка: {$photosDir}\n";
    }
    
    $uploadedCount = 0;
    $currentOrder = DB::table('master_photos')->where('master_profile_id', $masterId)->max('sort_order') ?? 0;
    
    foreach ($photosPaths as $photoPath) {
        if (!file_exists($photoPath)) {
            echo "❌ Файл не найден: {$photoPath}\n";
            continue;
        }
        
        try {
            // Генерируем уникальное имя файла
            $extension = pathinfo($photoPath, PATHINFO_EXTENSION);
            $filename = 'photo_' . (time() + $uploadedCount) . '.' . $extension;
            $targetPath = "{$photosDir}/{$filename}";
            
            // Копируем оригинал
            copy($photoPath, $targetPath);
            
            // Получаем размеры изображения
            $imageInfo = getimagesize($photoPath);
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $mimeType = $imageInfo['mime'];
            $fileSize = filesize($photoPath);
            
            // Создаем миниатюру (300x300)
            $thumbFilename = 'thumb_' . $filename;
            $thumbPath = "{$photosDir}/{$thumbFilename}";
            
            $image = $imageManager->read($photoPath);
            $image->cover(300, 300);
            $image->save($thumbPath);
            
            // Создаем средний размер (800px по большей стороне)
            $mediumFilename = 'medium_' . $filename;
            $mediumPath = "{$photosDir}/{$mediumFilename}";
            
            $image = $imageManager->read($photoPath);
            $image->scale(width: 800, height: 800);
            $image->save($mediumPath);
            
            // Добавляем запись в базу данных
            $currentOrder++;
            DB::table('master_photos')->insert([
                'master_profile_id' => $masterId,
                'filename' => $filename,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'width' => $width,
                'height' => $height,
                'is_main' => $isMain && $uploadedCount === 0,
                'sort_order' => $currentOrder,
                'is_approved' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            echo "✅ Загружено: {$filename} (размер: {$width}x{$height}, {$fileSize} байт)\n";
            $uploadedCount++;
            
        } catch (Exception $e) {
            echo "❌ Ошибка обработки {$photoPath}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "📊 Всего загружено: {$uploadedCount} фотографий для мастера {$master->first_name} {$master->last_name}\n";
    return $uploadedCount > 0;
}

/**
 * Функция для получения имени папки мастера
 */
function getFolderName($firstName, $lastName, $id) {
    $name = $firstName . ($lastName ? ' ' . $lastName : '');
    
    $translitMap = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
    ];
    
    $transliterated = strtr(mb_strtolower($name), $translitMap);
    $slug = preg_replace('/[^a-z0-9\-]/', '', str_replace(' ', '-', $transliterated));
    
    return $slug . '-' . $id;
}

/**
 * Функция для получения текущего времени
 */
function now() {
    return date('Y-m-d H:i:s');
}

// Примеры использования
echo "🚀 Скрипт для добавления фотографий мастера\n";
echo "==========================================\n\n";

// Пример 1: Добавляем фотографии для мастера ID 3 (Елена Сидорова)
echo "📸 Добавляем фотографии для Елены Сидоровой (ID: 3):\n";
$elenaPhotos = [
    'public/images/masters/elena1.jpg',
    'public/images/masters/elena2.jpg',
    'public/images/masters/elena3.jpg',
    'public/images/masters/elena4.jpg',
    'public/images/masters/elena5.jpg',
    'public/images/masters/elena6.jpg'
];
addMasterPhotos(3, $elenaPhotos, true);

echo "\n";

// Пример 2: Добавляем фотографии для другого мастера
// Раскомментируйте и измените под ваши нужды:

/*
echo "📸 Добавляем фотографии для Анны Петровой (ID: 1):\n";
$annaPhotos = [
    'path/to/anna1.jpg',
    'path/to/anna2.jpg',
    'path/to/anna3.jpg'
];
addMasterPhotos(1, $annaPhotos, true);
*/

echo "\n✅ Готово! Проверьте результат на странице мастера.\n";
echo "🌐 Ссылка: http://127.0.0.1:8000/masters/elena-sidorova-3\n"; 