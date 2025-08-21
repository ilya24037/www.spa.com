<?php

require_once __DIR__ . '/vendor/autoload.php';

// Загружаем приложение Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "🎬 DEBUG: Анализ проблемы с видео\n\n";

// Имитируем то, что отправляет фронтенд
$videoData = [
    'url' => 'data:video/webm;base64,GkXfo59ChoEBQveBAULygQRC84EIQoKEd2VibUKHgQRChYECGFOAZwEAAAAABcz+EU2bdLpNu4tTq4QVSalmU6yBbk27i1OrhBZUrmtTrIGTTbuLU6uEH0O2dVOsgcNNu41Tq4QcU7trU6yDBczs7K0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAFUSWZQSAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA...'
];

echo "📋 ТИП ДАННЫХ ВИДЕО:\n";
echo "  Тип: " . gettype($videoData) . "\n";
echo "  Содержит 'url': " . (isset($videoData['url']) ? 'ДА' : 'НЕТ') . "\n";
echo "  Содержит 'file': " . (isset($videoData['file']) ? 'ДА' : 'НЕТ') . "\n";
echo "  URL начинается с 'data:': " . (str_starts_with($videoData['url'], 'data:') ? 'ДА' : 'НЕТ') . "\n\n";

echo "🔍 ПРОВЕРКА ЛОГИКИ hasVideoFiles:\n";
$testVideo = $videoData;

// Проверяем логику из adFormModel.ts
$isFile = $testVideo instanceof File;
$isObjectWithFile = ($testVideo && is_array($testVideo) && isset($testVideo['file']) && $testVideo['file'] instanceof File);
$isObjectWithUrl = ($testVideo && is_array($testVideo) && isset($testVideo['url']));

echo "  instanceof File: " . ($isFile ? 'ДА' : 'НЕТ') . "\n";
echo "  object с file: " . ($isObjectWithFile ? 'ДА' : 'НЕТ') . "\n";
echo "  object с url: " . ($isObjectWithUrl ? 'ДА' : 'НЕТ') . "\n";
echo "  hasVideoFiles результат: " . (($isFile || $isObjectWithFile || $isObjectWithUrl) ? 'ДА' : 'НЕТ') . "\n\n";

echo "⚠️ ПРОБЛЕМА:\n";
echo "Data URL видео НЕ является File объектом!\n";
echo "Это строка в формате 'data:video/webm;base64,...'\n";
echo "Laravel не может сохранить это как файл напрямую.\n\n";

echo "💡 РЕШЕНИЕ:\n";
echo "1. Декодировать base64 данные\n";
echo "2. Создать временный файл\n";
echo "3. Сохранить его в storage как обычный файл\n";
echo "4. Вернуть URL на сохраненный файл\n\n";

echo "🔧 КОД ДЛЯ ИСПРАВЛЕНИЯ DraftController:\n";
echo "if (str_starts_with(\$videoValue, 'data:video/')) {\n";
echo "    // Декодируем data URL и сохраняем как файл\n";
echo "    \$base64Data = explode(',', \$videoValue, 2)[1];\n";
echo "    \$binaryData = base64_decode(\$base64Data);\n";
echo "    \$extension = 'webm'; // или получить из mime type\n";
echo "    \$fileName = uniqid() . '_' . time() . '.' . \$extension;\n";
echo "    \$path = 'videos/' . Auth::id() . '/' . \$fileName;\n";
echo "    Storage::disk('public')->put(\$path, \$binaryData);\n";
echo "    \$finalVideos[] = '/storage/' . \$path;\n";
echo "}\n";