<?php

use App\Domain\Ad\Models\Ad;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "🎯 ТЕСТ СОХРАНЕНИЯ ВИДЕО В БД\n";
echo "==============================\n\n";

// Проверим черновик ID 70
$draft = Ad::find(70);

if ($draft) {
    echo "📋 Черновик ID {$draft->id} найден\n";
    echo "Заголовок: {$draft->title}\n\n";
    
    // Проверяем video поле
    $videoField = $draft->video;
    echo "📹 Поле video в БД:\n";
    echo "  Тип: " . gettype($videoField) . "\n";
    
    if (is_string($videoField)) {
        echo "  Значение (строка): {$videoField}\n";
        
        // Пробуем распарсить
        $decoded = json_decode($videoField, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "  ✅ Успешно декодировано в массив\n";
            echo "  Количество видео: " . count($decoded) . "\n";
            foreach ($decoded as $index => $video) {
                echo "    Видео $index: $video\n";
            }
        } else {
            echo "  ❌ Ошибка декодирования: " . json_last_error_msg() . "\n";
        }
    } elseif (is_array($videoField)) {
        echo "  ✅ Уже массив (JsonFieldsTrait работает)\n";
        echo "  Количество видео: " . count($videoField) . "\n";
        foreach ($videoField as $index => $video) {
            echo "    Видео $index: $video\n";
        }
    } else {
        echo "  ❌ Неизвестный тип данных\n";
    }
    
    echo "\n❌ ПРОБЛЕМА:\n";
    echo "Видео сохраняется как JSON строка, а не массив.\n";
    echo "Это значит, что JsonFieldsTrait не работает правильно.\n\n";
    
    echo "🔧 ВОЗМОЖНЫЕ ПРИЧИНЫ:\n";
    echo "1. Поле 'video' не в массиве \$jsonFields модели\n";
    echo "2. Неправильная обработка при сохранении\n";
    echo "3. Двойное кодирование JSON\n";
    
    // Проверим модель
    echo "\n📊 Проверка модели:\n";
    $reflection = new ReflectionClass($draft);
    $property = $reflection->getProperty('jsonFields');
    $property->setAccessible(true);
    $jsonFields = $property->getValue($draft);
    
    if (in_array('video', $jsonFields)) {
        echo "  ✅ Поле 'video' есть в \$jsonFields\n";
    } else {
        echo "  ❌ Поле 'video' НЕТ в \$jsonFields!\n";
    }
    
} else {
    echo "❌ Черновик с ID 70 не найден!\n";
}