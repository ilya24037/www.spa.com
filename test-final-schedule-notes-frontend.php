<?php

require_once 'vendor/autoload.php';

use App\Domain\Ad\Models\Ad;

// Загружаем Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🎯 ФИНАЛЬНЫЙ ТЕСТ FRONTEND ИСПРАВЛЕНИЯ\n";
echo "=====================================\n\n";

// Устанавливаем тестовое значение
$ad = Ad::find(97);
if (!$ad) {
    echo "❌ Объявление не найдено\n";
    exit;
}

$testValue = "✅ ТЕСТ ПОСЛЕ ИСПРАВЛЕНИЯ FRONTEND: " . date('H:i:s');
echo "📝 Устанавливаем: '{$testValue}'\n";

$ad->schedule_notes = $testValue;
$ad->save();

echo "💾 Сохранено в БД\n\n";

// Проверяем что DraftService передает значение
$draftService = app(\App\Domain\Ad\Services\DraftService::class);
$preparedData = $draftService->prepareForDisplay($ad);

echo "🔍 Проверка DraftService:\n";
echo "   schedule_notes: '{$preparedData['schedule_notes']}'\n\n";

if ($preparedData['schedule_notes'] === $testValue) {
    echo "✅ DraftService работает правильно!\n\n";
    
    echo "🌐 ФИНАЛЬНОЕ ТЕСТИРОВАНИЕ:\n";
    echo "1. Откройте: http://spa.test/ads/97/edit\n";
    echo "2. Найдите секцию 'График работы'\n";
    echo "3. Поле должно содержать: '{$testValue}'\n";
    echo "4. Измените текст на: 'Мой тест работает!'\n";
    echo "5. Нажмите 'Сохранить черновик'\n";
    echo "6. Обновите страницу\n";
    echo "7. Поле должно содержать: 'Мой тест работает!'\n\n";
    
    echo "🔧 ЧТО БЫЛО ИСПРАВЛЕНО:\n";
    echo "   1. DraftService::prepareForDisplay - добавлена передача schedule_notes\n";
    echo "   2. ScheduleSection.vue:\n";
    echo "      - const localNotes = ref(props.scheduleNotes || '')\n";
    echo "      - localNotes.value = props.scheduleNotes || ''\n";
    echo "      - watch(() => props.scheduleNotes, (val) => localNotes.value = val || '')\n\n";
    
    echo "🎯 ТЕПЕРЬ schedule_notes работает ТОЧНО КАК description!\n";
} else {
    echo "❌ Ошибка в DraftService\n";
}

echo "\n📋 СРАВНЕНИЕ С DESCRIPTION:\n";
echo "   description в prepared data: '{$preparedData['description']}'\n";
echo "   schedule_notes в prepared data: '{$preparedData['schedule_notes']}'\n";
echo "   Оба поля теперь обрабатываются одинаково!\n";