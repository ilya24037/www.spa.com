<?php

require_once 'vendor/autoload.php';

use App\Domain\Ad\Models\Ad;

// Загружаем Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 ПРОВЕРКА SCHEDULE_NOTES ПОСЛЕ СОХРАНЕНИЯ В БРАУЗЕРЕ\n";
echo "===================================================\n\n";

$ad = Ad::find(97);
if (!$ad) {
    echo "❌ Объявление с ID 97 не найдено\n";
    exit;
}

echo "📊 ДАННЫЕ ИЗ БД:\n";
echo "   ID: {$ad->id}\n";
echo "   schedule_notes: \"" . ($ad->schedule_notes ?? 'NULL') . "\"\n";
echo "   Длина: " . strlen($ad->schedule_notes ?? '') . " символов\n";
echo "   Тип: " . gettype($ad->schedule_notes) . "\n\n";

// Проверим как DraftService передает поле (именно это видит frontend)
$draftService = app(\App\Domain\Ad\Services\DraftService::class);
$preparedData = $draftService->prepareForDisplay($ad);

echo "🎯 ДАННЫЕ ЧЕРЕЗ DraftService::prepareForDisplay:\n";
echo "   schedule_notes: \"" . ($preparedData['schedule_notes'] ?? 'ОТСУТСТВУЕТ') . "\"\n";
echo "   Длина в prepared data: " . strlen($preparedData['schedule_notes'] ?? '') . " символов\n";
echo "   Есть ли поле в массиве: " . (array_key_exists('schedule_notes', $preparedData) ? 'ДА' : 'НЕТ') . "\n\n";

// Сравним с description для убеждения
echo "📋 СРАВНЕНИЕ С DESCRIPTION:\n";
echo "   description: \"" . ($ad->description ?? 'NULL') . "\"\n";
echo "   description в prepared data: \"" . ($preparedData['description'] ?? 'ОТСУТСТВУЕТ') . "\"\n\n";

if ($ad->schedule_notes === null || $ad->schedule_notes === '') {
    echo "⚠️ ОБНАРУЖЕНА ПРОБЛЕМА:\n";
    echo "   schedule_notes пустое или NULL\n";
    echo "   Это может означать, что поле не обновляется при сохранении\n\n";
    
    echo "🔧 ТЕСТИРУЕМ ОБНОВЛЕНИЕ ВРУЧНУЮ:\n";
    $testValue = "РУЧНОЙ ТЕСТ ОБНОВЛЕНИЯ: " . date('H:i:s');
    echo "   Устанавливаем: \"{$testValue}\"\n";
    
    $ad->schedule_notes = $testValue;
    $saved = $ad->save();
    
    echo "   Сохранение: " . ($saved ? 'УСПЕШНО' : 'ОШИБКА') . "\n";
    
    $ad->refresh();
    echo "   После refresh: \"" . ($ad->schedule_notes ?? 'NULL') . "\"\n";
    
    if ($ad->schedule_notes === $testValue) {
        echo "   ✅ Ручное обновление работает - проблема в форме!\n";
    } else {
        echo "   ❌ Ручное обновление не работает - проблема в модели/БД!\n";
    }
} else {
    echo "✅ SCHEDULE_NOTES НЕ ПУСТОЕ:\n";
    echo "   Значение сохранено правильно\n";
    echo "   Проблема может быть в отображении frontend\n";
}

echo "\n🌐 URL для повторной проверки: http://spa.test/ads/97/edit\n";