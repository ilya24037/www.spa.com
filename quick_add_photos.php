<?php

// Простой скрипт для добавления фотографий мастера
// Не требует composer зависимостей

// Подключение к базе данных
$host = '127.0.0.1';
$dbname = 'laravel_auth';
$username = 'root';
$password = 'Animatori2025!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Подключение к базе данных успешно\n";
} catch (PDOException $e) {
    die("❌ Ошибка подключения к базе данных: " . $e->getMessage() . "\n");
}

/**
 * Функция для создания миниатюры изображения
 */
function createThumbnail($sourcePath, $targetPath, $width = 300, $height = 300) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }
    
    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    // Создаем изображение из источника
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    
    // Создаем новое изображение
    $targetImage = imagecreatetruecolor($width, $height);
    
    // Для PNG сохраняем прозрачность
    if ($mimeType === 'image/png') {
        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
    }
    
    // Вычисляем размеры для обрезки (crop)
    $sourceRatio = $sourceWidth / $sourceHeight;
    $targetRatio = $width / $height;
    
    if ($sourceRatio > $targetRatio) {
        // Источник шире
        $newWidth = $sourceHeight * $targetRatio;
        $newHeight = $sourceHeight;
        $srcX = ($sourceWidth - $newWidth) / 2;
        $srcY = 0;
    } else {
        // Источник выше
        $newWidth = $sourceWidth;
        $newHeight = $sourceWidth / $targetRatio;
        $srcX = 0;
        $srcY = ($sourceHeight - $newHeight) / 2;
    }
    
    // Копируем и изменяем размер
    imagecopyresampled(
        $targetImage, $sourceImage,
        0, 0, $srcX, $srcY,
        $width, $height, $newWidth, $newHeight
    );
    
    // Сохраняем результат
    $result = false;
    switch ($mimeType) {
        case 'image/jpeg':
            $result = imagejpeg($targetImage, $targetPath, 85);
            break;
        case 'image/png':
            $result = imagepng($targetImage, $targetPath, 6);
            break;
        case 'image/gif':
            $result = imagegif($targetImage, $targetPath);
            break;
    }
    
    // Освобождаем память
    imagedestroy($sourceImage);
    imagedestroy($targetImage);
    
    return $result;
}

/**
 * Функция для изменения размера изображения
 */
function resizeImage($sourcePath, $targetPath, $maxWidth = 800, $maxHeight = 800) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }
    
    $sourceWidth = $imageInfo[0];
    $sourceHeight = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    // Если изображение меньше максимального размера, просто копируем
    if ($sourceWidth <= $maxWidth && $sourceHeight <= $maxHeight) {
        return copy($sourcePath, $targetPath);
    }
    
    // Вычисляем новые размеры
    $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
    $newWidth = round($sourceWidth * $ratio);
    $newHeight = round($sourceHeight * $ratio);
    
    // Создаем изображение из источника
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    
    // Создаем новое изображение
    $targetImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Для PNG сохраняем прозрачность
    if ($mimeType === 'image/png') {
        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
    }
    
    // Изменяем размер
    imagecopyresampled(
        $targetImage, $sourceImage,
        0, 0, 0, 0,
        $newWidth, $newHeight, $sourceWidth, $sourceHeight
    );
    
    // Сохраняем результат
    $result = false;
    switch ($mimeType) {
        case 'image/jpeg':
            $result = imagejpeg($targetImage, $targetPath, 85);
            break;
        case 'image/png':
            $result = imagepng($targetImage, $targetPath, 6);
            break;
        case 'image/gif':
            $result = imagegif($targetImage, $targetPath);
            break;
    }
    
    // Освобождаем память
    imagedestroy($sourceImage);
    imagedestroy($targetImage);
    
    return $result;
}

/**
 * Функция для добавления фотографий мастера
 */
function addMasterPhotos($masterId, $photosPaths, $isMain = false) {
    global $pdo;
    
    // Получаем информацию о мастере
    $stmt = $pdo->prepare("SELECT * FROM master_profiles WHERE id = ?");
    $stmt->execute([$masterId]);
    $master = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$master) {
        echo "❌ Мастер с ID {$masterId} не найден\n";
        return false;
    }
    
    // Создаем папку мастера если не существует
    $folderName = getFolderName($master['first_name'], $master['last_name'], $master['id']);
    $masterDir = "storage/app/private/masters/{$folderName}";
    $photosDir = "{$masterDir}/photos";
    
    if (!is_dir($photosDir)) {
        mkdir($photosDir, 0755, true);
        echo "📁 Создана папка: {$photosDir}\n";
    }
    
    $uploadedCount = 0;
    
    // Получаем текущий максимальный порядок
    $stmt = $pdo->prepare("SELECT MAX(sort_order) FROM master_photos WHERE master_profile_id = ?");
    $stmt->execute([$masterId]);
    $currentOrder = $stmt->fetchColumn() ?? 0;
    
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
            if (!copy($photoPath, $targetPath)) {
                echo "❌ Ошибка копирования файла: {$photoPath}\n";
                continue;
            }
            
            // Получаем размеры изображения
            $imageInfo = getimagesize($photoPath);
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $mimeType = $imageInfo['mime'];
            $fileSize = filesize($photoPath);
            
            // Создаем миниатюру (300x300)
            $thumbFilename = 'thumb_' . $filename;
            $thumbPath = "{$photosDir}/{$thumbFilename}";
            
            if (createThumbnail($photoPath, $thumbPath, 300, 300)) {
                echo "✅ Создана миниатюра: {$thumbFilename}\n";
            } else {
                echo "⚠️ Не удалось создать миниатюру для: {$filename}\n";
            }
            
            // Создаем средний размер (800px)
            $mediumFilename = 'medium_' . $filename;
            $mediumPath = "{$photosDir}/{$mediumFilename}";
            
            if (resizeImage($photoPath, $mediumPath, 800, 800)) {
                echo "✅ Создан средний размер: {$mediumFilename}\n";
            } else {
                echo "⚠️ Не удалось создать средний размер для: {$filename}\n";
            }
            
            // Добавляем запись в базу данных
            $currentOrder++;
            $stmt = $pdo->prepare("
                INSERT INTO master_photos 
                (master_profile_id, filename, mime_type, file_size, width, height, is_main, sort_order, is_approved, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
            ");
            
            $stmt->execute([
                $masterId,
                $filename,
                $mimeType,
                $fileSize,
                $width,
                $height,
                $isMain && $uploadedCount === 0,
                $currentOrder
            ]);
            
            echo "✅ Загружено: {$filename} (размер: {$width}x{$height}, {$fileSize} байт)\n";
            $uploadedCount++;
            
        } catch (Exception $e) {
            echo "❌ Ошибка обработки {$photoPath}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "📊 Всего загружено: {$uploadedCount} фотографий для мастера {$master['first_name']} {$master['last_name']}\n";
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

// Основная логика
echo "🚀 Скрипт для добавления фотографий мастера\n";
echo "==========================================\n\n";

// Добавляем фотографии для мастера ID 3 (Елена Сидорова)
echo "📸 Добавляем фотографии для Елены Сидоровой (ID: 3):\n";
$elenaPhotos = [
    'public/images/masters/elena1.jpg',
    'public/images/masters/elena2.jpg',
    'public/images/masters/elena3.jpg',
    'public/images/masters/elena4.jpg',
    'public/images/masters/elena5.jpg',
    'public/images/masters/elena6.jpg'
];

$result = addMasterPhotos(3, $elenaPhotos, true);

if ($result) {
    echo "\n✅ Готово! Проверьте результат на странице мастера:\n";
    echo "🌐 http://127.0.0.1:8000/masters/elena-sidorova-3\n";
} else {
    echo "\n❌ Произошла ошибка при загрузке фотографий\n";
}

echo "\n💡 Для добавления фотографий другого мастера отредактируйте этот файл\n";
echo "   и измените ID мастера и пути к фотографиям.\n"; 