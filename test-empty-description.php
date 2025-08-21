<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;

echo "🔍 ТЕСТ: Проверка сохранения пустого описания\n";
echo "==========================================\n\n";

// Найдем черновик для тестирования
$draft = Ad::where('status', 'draft')->first();

if (!$draft) {
    echo "❌ Черновик не найден для тестирования\n";
    exit;
}

echo "📝 Тестируем черновик ID: {$draft->id}\n";
echo "Текущее описание: " . ($draft->description ? "'{$draft->description}'" : "NULL") . "\n";
echo "Длина описания: " . strlen($draft->description ?? '') . " символов\n\n";

// Попробуем сохранить пустое описание
echo "🔄 Тест 1: Сохраняем пустую строку ''\n";
$draft->description = '';
$draft->save();
$draft->refresh();

echo "После сохранения: " . ($draft->description !== null ? "'{$draft->description}'" : "NULL") . "\n";
echo "Тип данных: " . gettype($draft->description) . "\n";
echo "Длина: " . strlen($draft->description ?? '') . " символов\n\n";

// Попробуем сохранить NULL
echo "🔄 Тест 2: Сохраняем NULL\n";
$draft->description = null;
$draft->save();
$draft->refresh();

echo "После сохранения NULL: " . ($draft->description !== null ? "'{$draft->description}'" : "NULL") . "\n";
echo "Тип данных: " . gettype($draft->description) . "\n\n";

// Проверяем через update()
echo "🔄 Тест 3: Используем update() с пустой строкой\n";
$draft->update(['description' => '']);
$draft->refresh();

echo "После update(''): " . ($draft->description !== null ? "'{$draft->description}'" : "NULL") . "\n";
echo "Тип данных: " . gettype($draft->description) . "\n\n";

// Проверяем сырой SQL
echo "🔄 Тест 4: Проверяем в базе данных напрямую\n";
$rawData = \DB::table('ads')->where('id', $draft->id)->first();
echo "В базе данных (description): " . ($rawData->description !== null ? "'{$rawData->description}'" : "NULL") . "\n";
echo "Тип в БД: " . gettype($rawData->description) . "\n\n";

// Проверяем fillable
echo "📋 Проверка fillable массива:\n";
$fillable = $draft->getFillable();
echo "description в fillable: " . (in_array('description', $fillable) ? "✅ ДА" : "❌ НЕТ") . "\n\n";

// Проверяем casts
echo "📋 Проверка casts:\n";
$casts = $draft->getCasts();
if (isset($casts['description'])) {
    echo "description cast: " . $casts['description'] . "\n";
} else {
    echo "description не имеет cast\n";
}

echo "\n✅ Тестирование завершено\n";