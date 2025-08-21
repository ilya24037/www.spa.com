<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "🔍 СРАВНЕНИЕ FEATURES (работает) и MEDIA_SETTINGS (не работает)\n";
echo "================================================================\n\n";

// Проверяем структуру таблицы ads
$columns = DB::select('DESCRIBE ads');

echo "📋 Структура полей в БД:\n";
echo "------------------------\n";

// Features
foreach($columns as $column) {
    if($column->Field === 'features') {
        echo "✅ FEATURES (работает):\n";
        echo "   Поле: {$column->Field}\n";
        echo "   Тип: {$column->Type}\n";
        echo "   Обработка: JSON поле, сохраняется как массив\n\n";
    }
}

// Media settings
echo "❌ MEDIA_SETTINGS (не работает):\n";
echo "   Поле media_settings в БД: ";
$hasMediaSettings = false;
foreach($columns as $column) {
    if($column->Field === 'media_settings') {
        $hasMediaSettings = true;
        echo "НАЙДЕНО ({$column->Type})\n";
    }
}
if (!$hasMediaSettings) {
    echo "НЕ НАЙДЕНО\n";
}

echo "   Вместо него есть отдельные boolean поля:\n";
foreach($columns as $column) {
    if(in_array($column->Field, ['show_photos_in_gallery', 'allow_download_photos', 'watermark_photos'])) {
        echo "   - {$column->Field} ({$column->Type})\n";
    }
}

echo "\n📋 Тестовые данные:\n";
echo "------------------\n";

$draft = \App\Domain\Ad\Models\Ad::where('status', 'draft')->first();
if ($draft) {
    echo "Черновик ID: {$draft->id}\n\n";
    
    // Features
    echo "FEATURES:\n";
    $features = json_decode($draft->features, true) ?: [];
    echo "   В БД (raw): " . $draft->features . "\n";
    echo "   Декодировано: [" . implode(', ', $features) . "]\n";
    echo "   Тип: " . gettype($features) . "\n\n";
    
    // Media settings
    echo "MEDIA SETTINGS отдельные поля:\n";
    echo "   show_photos_in_gallery: " . var_export($draft->show_photos_in_gallery, true) . "\n";
    echo "   allow_download_photos: " . var_export($draft->allow_download_photos, true) . "\n";
    echo "   watermark_photos: " . var_export($draft->watermark_photos, true) . "\n";
    
    // Проверим prepareForDisplay
    $draftService = app(\App\Domain\Ad\Services\DraftService::class);
    $displayData = $draftService->prepareForDisplay($draft);
    
    echo "\nПосле prepareForDisplay:\n";
    echo "   features: " . (isset($displayData['features']) ? 
        "[" . implode(', ', $displayData['features']) . "]" : "НЕ УСТАНОВЛЕНО") . "\n";
    echo "   media_settings: " . (isset($displayData['media_settings']) ? 
        "[" . implode(', ', $displayData['media_settings']) . "]" : "НЕ УСТАНОВЛЕНО") . "\n";
}

echo "\n❗ ВЫВОДЫ:\n";
echo "----------\n";
echo "1. features - это JSON поле в БД, массив сохраняется напрямую\n";
echo "2. media_settings - НЕТ такого поля в БД, есть 3 отдельных boolean поля\n";
echo "3. Мы пытаемся преобразовать массив в boolean поля и обратно\n";
echo "4. Возможно, проблема в том, что prepareForDisplay использует \$ad, а не \$data\n";