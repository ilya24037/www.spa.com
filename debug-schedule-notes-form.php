<?php

require_once 'vendor/autoload.php';

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\DraftService;

// Загружаем Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 ОТЛАДКА SCHEDULE_NOTES В ФОРМЕ\n";
echo "=================================\n\n";

// Получаем данные как их видит AdController::edit
$ad = Ad::find(97);
if (!$ad) {
    echo "❌ Объявление не найдено\n";
    exit;
}

echo "📋 ДАННЫЕ НАПРЯМУЮ ИЗ БД:\n";
echo "   schedule_notes: '{$ad->schedule_notes}'\n";
echo "   description: '{$ad->description}'\n\n";

// Теперь получаем данные через DraftService как в контроллере
$draftService = app(\App\Domain\Ad\Services\DraftService::class);
$preparedData = $draftService->prepareForDisplay($ad);

echo "📋 ДАННЫЕ ЧЕРЕЗ DraftService::prepareForDisplay:\n";
$scheduleNotes = isset($preparedData['schedule_notes']) ? $preparedData['schedule_notes'] : 'ОТСУТСТВУЕТ';
$description = isset($preparedData['description']) ? $preparedData['description'] : 'ОТСУТСТВУЕТ';
echo "   schedule_notes: '{$scheduleNotes}'\n";
echo "   description: '{$description}'\n\n";

// Проверяем какие еще поля есть
echo "📋 ВСЕ ПОЛЯ В PREPARED DATA:\n";
foreach ($preparedData as $key => $value) {
    if (is_string($value) && strlen($value) < 100) {
        echo "   {$key}: '{$value}'\n";
    } elseif (is_array($value)) {
        echo "   {$key}: [массив с " . count($value) . " элементами]\n";
    } else {
        echo "   {$key}: [" . gettype($value) . "]\n";
    }
    
    if ($key === 'schedule_notes') {
        $len = $value ? strlen($value) : 0;
        echo "      ↳ Тип: " . gettype($value) . ", Длина: " . $len . "\n";
    }
}

echo "\n🎯 РЕКОМЕНДАЦИЯ:\n";
echo "Если schedule_notes присутствует в prepared data, проблема в frontend\n";
echo "Если отсутствует - проблема в DraftService::prepareForDisplay\n";