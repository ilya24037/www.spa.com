<?php
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Инициализация Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 ПРОВЕРКА ФОТОГРАФИЙ В БД\n";
echo "============================\n\n";

// Получаем последнее объявление
$latestAd = DB::table('ads')
    ->orderBy('id', 'desc')
    ->first();

if (!$latestAd) {
    echo "❌ Объявления не найдены\n";
    exit;
}

echo "📋 ID объявления: {$latestAd->id}\n";
echo "📋 Статус: " . ($latestAd->status ?? 'NULL') . "\n\n";

// Проверяем поле photos
echo "🖼️ ПРОВЕРКА ПОЛЯ PHOTOS:\n";
echo "------------------------\n";

$photosRaw = $latestAd->photos;
echo "RAW данные photos: " . ($photosRaw ?: 'NULL') . "\n";

if ($photosRaw) {
    $photosDecoded = json_decode($photosRaw, true);
    echo "JSON декодирование: " . (json_last_error() === JSON_ERROR_NONE ? 'УСПЕШНО' : 'ОШИБКА: ' . json_last_error_msg()) . "\n";
    
    if ($photosDecoded) {
        echo "Количество фото: " . count($photosDecoded) . "\n";
        echo "Структура фото:\n";
        foreach ($photosDecoded as $index => $photo) {
            echo "  Фото " . ($index + 1) . ":\n";
            if (is_array($photo)) {
                foreach ($photo as $key => $value) {
                    echo "    {$key}: " . (is_string($value) ? $value : json_encode($value)) . "\n";
                }
            } else {
                echo "    Значение: " . $photo . "\n";
            }
        }
    } else {
        echo "❌ Не удалось декодировать JSON\n";
    }
} else {
    echo "❌ Поле photos пустое или NULL\n";
}

echo "\n🔍 ПРОВЕРКА ДРУГИХ МЕДИА ПОЛЕЙ:\n";
echo "--------------------------------\n";

$mediaFields = ['video', 'verification_photo', 'verification_video'];
foreach ($mediaFields as $field) {
    $value = $latestAd->$field ?? 'NULL';
    echo "{$field}: " . ($value ?: 'NULL') . "\n";
}

echo "\n✅ ПРОВЕРКА ЗАВЕРШЕНА\n";
