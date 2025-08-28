<?php

require_once 'vendor/autoload.php';

use App\Domain\Ad\Models\Ad;

// Загружаем Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 ПРЯМАЯ ПРОВЕРКА DraftService::prepareForDisplay()\n";
echo "=============================================\n\n";

$ad = Ad::find(97);
if (!$ad) {
    echo "❌ Объявление не найдено\n";
    exit;
}

$draftService = app(\App\Domain\Ad\Services\DraftService::class);
$preparedData = $draftService->prepareForDisplay($ad);

echo "📊 РЕЗУЛЬТАТЫ:\n";
echo "   schedule_notes в БД: \"" . ($ad->schedule_notes ?? 'NULL') . "\"\n";
echo "   schedule_notes в prepared: \"" . ($preparedData['schedule_notes'] ?? 'ОТСУТСТВУЕТ') . "\"\n";
echo "   array_key_exists schedule_notes: " . (array_key_exists('schedule_notes', $preparedData) ? 'ДА' : 'НЕТ') . "\n";
echo "   Общее количество ключей в prepared: " . count($preparedData) . "\n\n";

if (array_key_exists('schedule_notes', $preparedData)) {
    if ($preparedData['schedule_notes'] === $ad->schedule_notes) {
        echo "✅ SCHEDULE_NOTES ПЕРЕДАЕТСЯ ПРАВИЛЬНО!\n";
        echo "   Проблема НЕ в DraftService\n";
        echo "   Проблема в передаче от контроллера к frontend\n";
    } else {
        echo "⚠️ SCHEDULE_NOTES ПЕРЕДАЕТСЯ, НО НЕПРАВИЛЬНО!\n";
        echo "   Ожидали: \"" . ($ad->schedule_notes ?? 'NULL') . "\"\n";
        echo "   Получили: \"" . ($preparedData['schedule_notes'] ?? 'NULL') . "\"\n";
    }
} else {
    echo "❌ SCHEDULE_NOTES НЕ ПЕРЕДАЕТСЯ!\n";
    echo "   DraftService НЕ добавляет поле в массив\n";
    echo "   Нужно исправить DraftService::prepareForDisplay()\n";
}

echo "\n🔧 КЛЮЧИ В PREPARED DATA:\n";
$keys = array_keys($preparedData);
$scheduleRelated = array_filter($keys, function($key) {
    return str_contains(strtolower($key), 'schedule');
});

echo "   Всего ключей: " . count($keys) . "\n";
echo "   Schedule-связанные ключи: " . json_encode(array_values($scheduleRelated)) . "\n";