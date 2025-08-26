<?php

use App\Domain\Ad\Models\Ad;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "🎬 ФИНАЛЬНЫЙ ТЕСТ ВИДЕО ФУНКЦИОНАЛА\n";
echo "=====================================\n\n";

// Проверяем черновик ID 70
$draft = Ad::find(70);
if (!$draft) {
    echo "❌ Черновик ID 70 не найден!\n";
    exit;
}

echo "📋 Черновик найден: ID {$draft->id}, Title: {$draft->title}\n\n";

// ТЕСТ 1: Проверка текущего состояния
echo "📊 ТЕСТ 1: Текущее состояние видео\n";
echo "-----------------------------------\n";
$videoRaw = $draft->getAttributes()['video'] ?? null;
echo "  Сырое значение из БД: " . var_export($videoRaw, true) . "\n";
echo "  Тип сырого значения: " . gettype($videoRaw) . "\n";

$videoField = $draft->video;
echo "  Значение через accessor: " . var_export($videoField, true) . "\n";
echo "  Тип через accessor: " . gettype($videoField) . "\n";

if (is_array($videoField)) {
    echo "  ✅ Поле video корректно декодируется в массив\n";
    echo "  Количество видео: " . count($videoField) . "\n";
    foreach ($videoField as $index => $video) {
        echo "    Видео {$index}: {$video}\n";
    }
} else {
    echo "  ❌ Поле video НЕ декодируется в массив!\n";
    
    // Пробуем декодировать вручную
    if (is_string($videoField)) {
        $decoded = json_decode($videoField, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "  Ручное декодирование успешно, видео: " . count($decoded) . "\n";
        }
    }
}

echo "\n";

// ТЕСТ 2: Проверка casts
echo "📊 ТЕСТ 2: Проверка casts модели\n";
echo "--------------------------------\n";
$casts = $draft->getCasts();
if (isset($casts['video'])) {
    echo "  ✅ Поле 'video' есть в casts: " . $casts['video'] . "\n";
} else {
    echo "  ❌ Поле 'video' НЕТ в casts!\n";
}

// Проверяем jsonFields
$reflection = new ReflectionClass($draft);
if ($reflection->hasProperty('jsonFields')) {
    $property = $reflection->getProperty('jsonFields');
    $property->setAccessible(true);
    $jsonFields = $property->getValue($draft);
    
    if (in_array('video', $jsonFields)) {
        echo "  ✅ Поле 'video' есть в \$jsonFields\n";
    } else {
        echo "  ❌ Поле 'video' НЕТ в \$jsonFields!\n";
    }
}

echo "\n";

// ТЕСТ 3: Проверка JsonFieldsTrait методов
echo "📊 ТЕСТ 3: Методы JsonFieldsTrait\n";
echo "---------------------------------\n";
if (method_exists($draft, 'getJsonField')) {
    $videoViaMethod = $draft->getJsonField('video', []);
    echo "  getJsonField('video'): " . var_export($videoViaMethod, true) . "\n";
    echo "  Тип: " . gettype($videoViaMethod) . "\n";
    
    if (is_array($videoViaMethod)) {
        echo "  ✅ getJsonField возвращает массив\n";
    } else {
        echo "  ❌ getJsonField НЕ возвращает массив\n";
    }
} else {
    echo "  ❌ Метод getJsonField не существует\n";
}

echo "\n";

// ТЕСТ 4: Проверка сохранения нового видео
echo "📊 ТЕСТ 4: Сохранение нового видео URL\n";
echo "--------------------------------------\n";
$testVideoUrl = '/storage/videos/test/test_' . time() . '.mp4';
$currentVideos = is_array($draft->video) ? $draft->video : [];
$currentVideos[] = $testVideoUrl;

echo "  Добавляем тестовое видео: {$testVideoUrl}\n";
$draft->video = $currentVideos;
$draft->save();

// Перезагружаем из БД
$draft->refresh();

echo "  После сохранения:\n";
$videoAfterSave = $draft->video;
echo "    Тип: " . gettype($videoAfterSave) . "\n";

if (is_array($videoAfterSave)) {
    echo "    ✅ Видео корректно сохранилось как массив\n";
    echo "    Количество видео: " . count($videoAfterSave) . "\n";
    
    if (in_array($testVideoUrl, $videoAfterSave)) {
        echo "    ✅ Новое видео успешно добавлено\n";
    } else {
        echo "    ❌ Новое видео НЕ найдено в массиве\n";
    }
} else {
    echo "    ❌ Видео НЕ сохранилось как массив\n";
}

echo "\n";

// ИТОГОВЫЙ РЕЗУЛЬТАТ
echo "🎯 ИТОГОВЫЙ РЕЗУЛЬТАТ\n";
echo "====================\n";

$problems = [];

if (!is_array($draft->video)) {
    $problems[] = "JsonFieldsTrait не работает для поля video";
}

if (!isset($casts['video']) || $casts['video'] !== 'array') {
    $problems[] = "Поле video не имеет правильного cast";
}

if (count($problems) > 0) {
    echo "❌ ОБНАРУЖЕНЫ ПРОБЛЕМЫ:\n";
    foreach ($problems as $problem) {
        echo "  - {$problem}\n";
    }
    echo "\n🔧 РЕКОМЕНДАЦИИ:\n";
    echo "  1. Проверьте что initializeJsonFieldsTrait вызывается\n";
    echo "  2. Проверьте что нет переопределения getCasts() в модели\n";
    echo "  3. Проверьте что нет мутатора setVideoAttribute\n";
} else {
    echo "✅ ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО!\n";
    echo "  Видео функционал работает корректно\n";
}

echo "\n📝 ДОПОЛНИТЕЛЬНАЯ ИНФОРМАЦИЯ:\n";
echo "  Модель: " . get_class($draft) . "\n";
echo "  Используемые трейты: " . implode(', ', class_uses($draft)) . "\n";
echo "  ID черновика: {$draft->id}\n";
echo "  Текущие видео в БД: " . json_encode($draft->video) . "\n";