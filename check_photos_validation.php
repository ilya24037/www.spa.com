<?php
require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Domain\Ad\Models\Ad;

// Загрузка Laravel приложения
try {
    $app = require_once __DIR__.'/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
} catch (\Throwable $e) {
    echo "Ошибка загрузки Laravel приложения: " . $e->getMessage() . "\n";
    exit(1);
}

echo "🔍 ДИАГНОСТИКА ПРОБЛЕМЫ С ФОТОГРАФИЯМИ\n";
echo "=====================================\n\n";

// Находим последнее объявление
$latestAd = Ad::latest()->first();

if (!$latestAd) {
    echo "❌ Объявления не найдены.\n";
    exit(0);
}

echo "📋 ID объявления: {$latestAd->id}\n";
echo "📋 Статус: " . $latestAd->status->value . "\n\n";

echo "🖼️ ПРОВЕРКА ПОЛЯ PHOTOS В БД:\n";
echo "------------------------------\n";

// Получаем RAW данные из БД для поля photos
$rawData = DB::table('ads')->where('id', $latestAd->id)->value('photos');
echo "RAW данные photos: " . (is_string($rawData) ? $rawData : json_encode($rawData)) . "\n";

$decodedPhotos = null;
if (is_string($rawData)) {
    $decodedPhotos = json_decode($rawData, true);
} elseif (is_array($rawData)) {
    $decodedPhotos = $rawData;
}

if (json_last_error() === JSON_ERROR_NONE && is_array($decodedPhotos)) {
    echo "JSON декодирование: УСПЕШНО\n";
    echo "Количество фото: " . count($decodedPhotos) . "\n";
    echo "Структура фото:\n";
    foreach ($decodedPhotos as $index => $photo) {
        echo "  photos[{$index}]: " . json_encode($photo) . "\n";
        echo "  photos[{$index}] type: " . gettype($photo) . "\n";
        echo "  photos[{$index}] is_array: " . (is_array($photo) ? 'true' : 'false') . "\n";
        if (is_array($photo)) {
            echo "  photos[{$index}] is_empty: " . (empty($photo) ? 'true' : 'false') . "\n";
        }
        echo "\n";
    }
} else {
    echo "JSON декодирование: ОШИБКА или НЕ МАССИВ\n";
    echo "  Ошибка: " . json_last_error_msg() . "\n";
}

echo "🔍 ПРОВЕРКА ВАЛИДАЦИИ:\n";
echo "----------------------\n";

// Создаем массив для тестирования валидации
$testData = [
    'photos' => $decodedPhotos ?? []
];

echo "Данные для валидации:\n";
echo "  photos: " . json_encode($testData['photos']) . "\n";
echo "  photos type: " . gettype($testData['photos']) . "\n";
echo "  photos is_array: " . (is_array($testData['photos']) ? 'true' : 'false') . "\n";

// Проверяем каждое фото
if (is_array($testData['photos'])) {
    foreach ($testData['photos'] as $index => $photo) {
        echo "  photos[{$index}]: " . json_encode($photo) . "\n";
        echo "  photos[{$index}] type: " . gettype($photo) . "\n";
        
        // Проверяем валидацию
        if (is_string($photo)) {
            echo "  photos[{$index}] validation: ✅ СТРОКА (OK)\n";
        } elseif (is_array($photo) && empty($photo)) {
            echo "  photos[{$index}] validation: ❌ ПУСТОЙ МАССИВ (ERROR)\n";
        } else {
            echo "  photos[{$index}] validation: ❌ НЕ СТРОКА (ERROR)\n";
        }
        echo "\n";
    }
}

echo "✅ ДИАГНОСТИКА ЗАВЕРШЕНА\n";
