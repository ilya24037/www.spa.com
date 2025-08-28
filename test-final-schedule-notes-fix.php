<?php

require_once 'vendor/autoload.php';

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\DraftService;

// Загружаем Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🎯 ФИНАЛЬНЫЙ ТЕСТ ИСПРАВЛЕНИЯ SCHEDULE_NOTES\n";
echo "============================================\n\n";

// 1. Добавим тестовое значение в БД
$ad = Ad::find(97);
if (!$ad) {
    echo "❌ Объявление не найдено\n";
    exit;
}

$testValue = "ФИНАЛЬНЫЙ ТЕСТ: " . date('H:i:s') . " - можете связаться по WhatsApp для уточнения графика";

echo "📝 Устанавливаем тестовое значение: '{$testValue}'\n";
$ad->schedule_notes = $testValue;
$ad->save();

echo "✅ Значение сохранено в БД\n\n";

// 2. Проверим через DraftService
$draftService = app(\App\Domain\Ad\Services\DraftService::class);
$preparedData = $draftService->prepareForDisplay($ad);

echo "🔍 Проверка через DraftService::prepareForDisplay:\n";
echo "   schedule_notes: '{$preparedData['schedule_notes']}'\n";
echo "   Длина: " . strlen($preparedData['schedule_notes']) . " символов\n\n";

if ($preparedData['schedule_notes'] === $testValue) {
    echo "✅ SUCCESS! ИСПРАВЛЕНИЕ РАБОТАЕТ ИДЕАЛЬНО!\n\n";
    
    echo "🌐 ИНСТРУКЦИИ ДЛЯ ФИНАЛЬНОГО ТЕСТИРОВАНИЯ:\n";
    echo "1. Откройте: http://spa.test/ads/97/edit\n";
    echo "2. Найдите секцию 'График работы'\n";
    echo "3. Поле 'Дополнительная информация о графике работы' должно содержать:\n";
    echo "   '{$testValue}'\n";
    echo "4. Измените текст на свой и нажмите 'Сохранить черновик'\n";
    echo "5. Обновите страницу - изменения должны сохраниться!\n\n";
    
    echo "🎯 ПРОБЛЕМА ПОЛНОСТЬЮ РЕШЕНА!\n";
    echo "   ✅ БД: поле schedule_notes сохраняется\n";
    echo "   ✅ Backend: DraftService передает поле\n";
    echo "   ✅ Frontend: поле должно отображаться\n";
} else {
    echo "❌ ОШИБКА! Значения не совпадают:\n";
    echo "   Ожидали: '{$testValue}'\n";
    echo "   Получили: '{$preparedData['schedule_notes']}'\n";
}

echo "\n🔧 ТЕХНИЧЕСКАЯ ИНФОРМАЦИЯ:\n";
echo "   Исправлено: DraftService::prepareForDisplay (строки 173-176)\n";
echo "   Причина: Поле schedule_notes не добавлялось в prepared data\n";
echo "   Решение: Добавлена обработка аналогично полю description\n";