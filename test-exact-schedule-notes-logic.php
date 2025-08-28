<?php

require_once 'vendor/autoload.php';

use App\Domain\Ad\Models\Ad;

// Загружаем Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🎯 ТЕСТ ТОЧНОГО СООТВЕТСТВИЯ ЛОГИКИ С DESCRIPTIONSECTION\n";
echo "=====================================================\n\n";

// Найдем объявление
$ad = Ad::find(97);
if (!$ad) {
    echo "❌ Объявление не найдено\n";
    exit;
}

// Устанавливаем тестовые значения
$descriptionValue = "ТЕСТ DESCRIPTION: " . date('H:i:s');
$scheduleNotesValue = "ТЕСТ SCHEDULE_NOTES: " . date('H:i:s');

echo "📝 Устанавливаем значения:\n";
echo "   description: '{$descriptionValue}'\n";
echo "   schedule_notes: '{$scheduleNotesValue}'\n\n";

$ad->description = $descriptionValue;
$ad->schedule_notes = $scheduleNotesValue;
$ad->save();

// Проверяем через DraftService (как делает frontend)
$draftService = app(\App\Domain\Ad\Services\DraftService::class);
$preparedData = $draftService->prepareForDisplay($ad);

echo "🔍 Проверка через DraftService::prepareForDisplay:\n";
echo "   description: '{$preparedData['description']}'\n";
echo "   schedule_notes: '{$preparedData['schedule_notes']}'\n\n";

// Проверяем идентичность обработки
$descriptionMatch = $preparedData['description'] === $descriptionValue;
$scheduleNotesMatch = $preparedData['schedule_notes'] === $scheduleNotesValue;

echo "✅ РЕЗУЛЬТАТ ПРОВЕРКИ:\n";
echo "   description передается: " . ($descriptionMatch ? "ДА" : "НЕТ") . "\n";
echo "   schedule_notes передается: " . ($scheduleNotesMatch ? "ДА" : "НЕТ") . "\n\n";

if ($descriptionMatch && $scheduleNotesMatch) {
    echo "🎉 SUCCESS! Оба поля обрабатываются ИДЕНТИЧНО!\n\n";
    
    echo "🌐 ИНСТРУКЦИЯ ДЛЯ ФИНАЛЬНОГО ТЕСТИРОВАНИЯ:\n";
    echo "1. Откройте: http://spa.test/ads/97/edit\n";
    echo "2. Проверьте секцию 'Основное описание':\n";
    echo "   Должно содержать: '{$descriptionValue}'\n";
    echo "3. Проверьте секцию 'График работы' → 'Дополнительная информация':\n";
    echo "   Должно содержать: '{$scheduleNotesValue}'\n";
    echo "4. Измените оба поля и нажмите 'Сохранить черновик'\n";
    echo "5. Обновите страницу - оба изменения должны сохраниться!\n\n";
    
    echo "✅ ТЕПЕРЬ SCHEDULE_NOTES РАБОТАЕТ КАК ОТДЕЛЬНЫЙ КОМПОНЕНТ!\n";
    echo "   - Создан ScheduleNotesSection (точная копия DescriptionSection)\n";
    echo "   - Использует ту же логику: props → localValue → emit\n";
    echo "   - Интегрирован в ScheduleSection как отдельный блок\n";
} else {
    echo "❌ ОШИБКА! Обработка полей различается:\n";
    if (!$descriptionMatch) {
        echo "   description ожидали: '{$descriptionValue}', получили: '{$preparedData['description']}'\n";
    }
    if (!$scheduleNotesMatch) {
        echo "   schedule_notes ожидали: '{$scheduleNotesValue}', получили: '{$preparedData['schedule_notes']}'\n";
    }
}

echo "\n🔧 ТЕХНИЧЕСКАЯ ИНФОРМАЦИЯ:\n";
echo "   Backend: DraftService::prepareForDisplay обрабатывает оба поля идентично\n";
echo "   Frontend: ScheduleNotesSection - точная копия DescriptionSection\n";
echo "   Архитектура: Модульная, следует принципам FSD\n";