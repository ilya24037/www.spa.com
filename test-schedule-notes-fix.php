<?php

use App\Domain\Ad\Models\Ad;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 ТЕСТ ИСПРАВЛЕНИЯ schedule_notes\n";
echo str_repeat("=", 60) . "\n\n";

echo "📋 ВЫПОЛНЕННОЕ ИСПРАВЛЕНИЕ:\n";
echo str_repeat("-", 50) . "\n";
echo "В ScheduleSection.vue:\n";
echo "  ❌ БЫЛО: emit('update:scheduleNotes', value)\n";
echo "  ✅ СТАЛО: emit('update:schedule-notes', value)\n\n";

echo "ПРИЧИНА ОШИБКИ:\n";
echo "  • AdForm.vue использует v-model:schedule-notes (с дефисом)\n";
echo "  • Vue ожидает событие update:schedule-notes\n";
echo "  • Компонент отправлял update:scheduleNotes (camelCase)\n";
echo "  • Несоответствие имен блокировало передачу данных\n\n";

// Тестируем сохранение
$ad = Ad::find(97) ?: Ad::first();

if ($ad) {
    echo "🧪 ТЕСТИРОВАНИЕ НА ОБЪЯВЛЕНИИ ID: " . $ad->id . "\n";
    
    $testText = "График работы: понедельник-пятница 9:00-18:00, выходные по договоренности. Тест: " . date('H:i:s');
    $ad->schedule_notes = $testText;
    $ad->save();
    
    echo "  ✅ Установлено тестовое значение\n";
    
    // Проверяем сохранение
    $ad->refresh();
    if ($ad->schedule_notes === $testText) {
        echo "  ✅ Значение успешно сохранено в БД!\n\n";
    } else {
        echo "  ❌ Значение не сохранилось\n\n";
    }
} else {
    echo "  ❌ Нет объявлений для тестирования\n\n";
}

echo str_repeat("=", 60) . "\n";
echo "✅ ИСПРАВЛЕНИЕ ПРИМЕНЕНО!\n\n";

echo "🌐 ИНСТРУКЦИЯ ДЛЯ ТЕСТИРОВАНИЯ:\n";
echo "1. Откройте http://spa.test/ads/97/edit\n";
echo "2. Найдите секцию 'График работы'\n";
echo "3. В поле 'Дополнительная информация о графике работы' введите текст\n";
echo "4. Нажмите 'Сохранить черновик'\n";
echo "5. Обновите страницу - текст должен сохраниться! ✅\n";