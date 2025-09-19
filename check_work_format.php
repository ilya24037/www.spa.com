<?php

require_once 'vendor/autoload.php';

use App\Domain\Ad\Models\Ad;

// Загружаем Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 ПРОВЕРКА ПОЛЯ WORK_FORMAT\n";
echo "============================\n\n";

$ad = Ad::find(50);

if (!$ad) {
    echo "❌ Объявление не найдено\n";
    exit;
}

echo "📋 ID объявления: {$ad->id}\n\n";

// Проверяем raw значение из БД
$rawValue = $ad->getRawOriginal('work_format');
echo "🔍 RAW значение из БД: " . ($rawValue ?? 'NULL') . "\n";

// Проверяем через accessor
$accessorValue = $ad->work_format;
echo "🔍 Через accessor: " . ($accessorValue ?? 'NULL') . "\n";

// Проверяем атрибуты
$attributes = $ad->getAttributes();
echo "🔍 В getAttributes(): " . ($attributes['work_format'] ?? 'NULL') . "\n";

// Проверяем что в JSON полях
$jsonFields = ['clients', 'service_provider'];
foreach ($jsonFields as $field) {
    $rawValue = $ad->getRawOriginal($field);
    $accessorValue = $ad->$field;
    echo "🔍 {$field} RAW: " . ($rawValue ?? 'NULL') . "\n";
    echo "🔍 {$field} accessor: " . (is_array($accessorValue) ? '[' . count($accessorValue) . ' элементов]' : ($accessorValue ?? 'NULL')) . "\n";
}
