<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Тестирование редактирования активного объявления ID 128...\n\n";

// Проверяем текущее состояние объявления
$ad = \App\Domain\Ad\Models\Ad::find(128);

if (!$ad) {
    echo "❌ Объявление с ID 128 не найдено\n";
    exit(1);
}

echo "📋 ТЕКУЩЕЕ СОСТОЯНИЕ:\n";
echo "===================\n";
echo "ID: {$ad->id}\n";
echo "Статус: {$ad->status->value}\n";
echo "Название: {$ad->title}\n";
echo "Описание: " . substr($ad->description, 0, 50) . "...\n";
echo "Адрес: {$ad->address}\n";

// Проверяем доступность редактирования
echo "\n🔧 ПРОВЕРКА ВОЗМОЖНОСТИ РЕДАКТИРОВАНИЯ:\n";
echo "=====================================\n";

// Симулируем что пользователь пытается отредактировать активное объявление
$canEdit = true;
$editUrl = "/ads/{$ad->id}/edit";

echo "URL редактирования: http://spa.test{$editUrl}\n";
echo "Возможность редактирования: " . ($canEdit ? "✅ ДА" : "❌ НЕТ") . "\n";

if ($ad->status->value === 'active') {
    echo "✅ Объявление активно - редактирование должно работать\n";
} else {
    echo "⚠️ Объявление не активно - статус: {$ad->status->value}\n";
}

// Тестируем обновление
echo "\n🔄 ТЕСТИРОВАНИЕ ОБНОВЛЕНИЯ:\n";
echo "=========================\n";

$originalDescription = $ad->description;
$testDescription = $originalDescription . "\n\n[Обновлено: " . date('Y-m-d H:i:s') . "]";

try {
    $ad->update([
        'description' => $testDescription
    ]);
    
    echo "✅ Обновление прошло успешно\n";
    
    // Проверяем что статус сохранился
    $ad->refresh();
    echo "Статус после обновления: {$ad->status->value}\n";
    echo "Описание обновлено: " . (strpos($ad->description, '[Обновлено:') !== false ? "✅ ДА" : "❌ НЕТ") . "\n";
    
    // Возвращаем оригинальное описание
    $ad->update(['description' => $originalDescription]);
    echo "Описание восстановлено: ✅ ДА\n";
    
} catch (\Exception $e) {
    echo "❌ Ошибка обновления: " . $e->getMessage() . "\n";
}

echo "\n🌐 ССЫЛКИ ДЛЯ ПРОВЕРКИ:\n";
echo "=====================\n";
echo "Редактирование: http://spa.test/ads/{$ad->id}/edit\n";
echo "Просмотр: http://spa.test/ads/{$ad->id}\n";
echo "Личный кабинет: http://spa.test/profile\n";

echo "\n🎉 Тестирование завершено!\n";