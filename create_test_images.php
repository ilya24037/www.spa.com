<?php

// Создание тестовых изображений для мастера

// Функция для создания простого изображения с текстом
function createTestImage($text, $width = 400, $height = 600, $filename = null) {
    // Создаем изображение
    $image = imagecreate($width, $height);
    
    // Определяем цвета
    $background = imagecolorallocate($image, 240, 240, 240);
    $textColor = imagecolorallocate($image, 80, 80, 80);
    $borderColor = imagecolorallocate($image, 200, 200, 200);
    
    // Заливаем фон
    imagefill($image, 0, 0, $background);
    
    // Рисуем рамку
    imagerectangle($image, 0, 0, $width-1, $height-1, $borderColor);
    
    // Добавляем текст
    $fontSize = 5;
    $textWidth = imagefontwidth($fontSize) * strlen($text);
    $textHeight = imagefontheight($fontSize);
    
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;
    
    imagestring($image, $fontSize, $x, $y, $text, $textColor);
    
    // Сохраняем изображение
    if ($filename) {
        imagejpeg($image, $filename, 90);
        echo "Создано: $filename\n";
    }
    
    // Освобождаем память
    imagedestroy($image);
}

// Создаем папки если их нет
if (!file_exists('public/images/masters')) {
    mkdir('public/images/masters', 0755, true);
}

if (!file_exists('storage/app/public/masters')) {
    mkdir('storage/app/public/masters', 0755, true);
}

// Создаем тестовые изображения
$images = [
    'elena1.jpg' => 'Elena Sidorova - Photo 1',
    'elena2.jpg' => 'Elena Sidorova - Photo 2',
    'elena3.jpg' => 'Elena Sidorova - Photo 3',
    'elena4.jpg' => 'Elena Sidorova - Photo 4',
    'elena5.jpg' => 'Elena Sidorova - Photo 5',
    'elena6.jpg' => 'Elena Sidorova - Photo 6',
];

echo "Создание тестовых изображений...\n";

foreach ($images as $filename => $text) {
    // Создаем в public/images/masters
    createTestImage($text, 400, 600, "public/images/masters/$filename");
    
    // Создаем в storage/app/public/masters
    createTestImage($text, 400, 600, "storage/app/public/masters/$filename");
}

echo "Готово! Созданы тестовые изображения.\n";
echo "Теперь можете добавить их через форму upload_photos.html\n";
?> 