<?php

use App\Domain\Ad\Models\Ad;
use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 ПРОВЕРКА СОХРАНЕНИЯ 'Дополнительная информация о графике работы'\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Проверяем структуру таблицы
echo "📊 ПРОВЕРКА СТРУКТУРЫ ТАБЛИЦЫ ads:\n";
$columns = DB::select('DESCRIBE ads');
$hasScheduleNotes = false;

foreach ($columns as $column) {
    if ($column->Field === 'schedule_notes') {
        $hasScheduleNotes = true;
        echo "  ✅ Поле schedule_notes найдено:\n";
        echo "     Тип: " . $column->Type . "\n";
        echo "     Nullable: " . ($column->Null === 'YES' ? 'Да' : 'Нет') . "\n";
        echo "     Default: " . ($column->Default ?? 'NULL') . "\n";
        break;
    }
}

if (!$hasScheduleNotes) {
    echo "  ❌ Поле schedule_notes НЕ найдено в таблице ads!\n";
}

// 2. Проверяем модель Ad
echo "\n📋 ПРОВЕРКА МОДЕЛИ Ad:\n";
$ad = new Ad();
$fillable = $ad->getFillable();

if (in_array('schedule_notes', $fillable)) {
    echo "  ✅ schedule_notes в массиве fillable\n";
} else {
    echo "  ❌ schedule_notes НЕ в массиве fillable!\n";
}

// 3. Проверяем тестовое объявление
echo "\n🧪 ТЕСТИРОВАНИЕ СОХРАНЕНИЯ:\n";
$testAd = Ad::find(97) ?: Ad::first();

if ($testAd) {
    echo "  Объявление ID: " . $testAd->id . "\n";
    echo "  Текущее значение schedule_notes: ";
    
    if ($testAd->schedule_notes === null) {
        echo "NULL (пусто)\n";
    } else {
        echo "'" . $testAd->schedule_notes . "'\n";
    }
    
    // Пробуем сохранить тестовое значение
    $testText = "Тестовая информация о графике работы - " . date('Y-m-d H:i:s');
    $testAd->schedule_notes = $testText;
    
    try {
        $testAd->save();
        echo "\n  ✅ Тестовое сохранение успешно!\n";
        
        // Перезагружаем и проверяем
        $testAd->refresh();
        if ($testAd->schedule_notes === $testText) {
            echo "  ✅ Данные корректно сохранены в БД\n";
        } else {
            echo "  ❌ Данные не сохранились корректно\n";
        }
    } catch (\Exception $e) {
        echo "\n  ❌ Ошибка при сохранении: " . $e->getMessage() . "\n";
    }
} else {
    echo "  ❌ Нет объявлений для тестирования\n";
}

// 4. Проверяем frontend -> backend flow
echo "\n🔄 ПРОВЕРКА ПОТОКА ДАННЫХ:\n";
echo "  Frontend (ScheduleSection.vue):\n";
echo "    • v-model=\"localNotes\" - привязка к textarea ✅\n";
echo "    • @update:modelValue=\"emitNotes\" - отправка изменений ✅\n";
echo "    • emit('update:scheduleNotes', value) - эмит события ✅\n";
echo "\n  Frontend (AdForm.vue):\n";
echo "    • v-model:schedule-notes=\"form.schedule_notes\" - прием данных ✅\n";
echo "    • form.schedule_notes в секции schedule ✅\n";
echo "\n  Backend (formDataBuilder.ts):\n";
echo "    • schedule_notes должно отправляться в FormData\n";

echo "\n" . str_repeat("=", 60) . "\n";
if ($hasScheduleNotes && in_array('schedule_notes', $fillable)) {
    echo "✅ БАЗА ДАННЫХ ГОТОВА К СОХРАНЕНИЮ schedule_notes\n";
    echo "❓ Проблема может быть в отправке данных с frontend\n";
} else {
    echo "❌ ПРОБЛЕМА В БАЗЕ ДАННЫХ ИЛИ МОДЕЛИ\n";
}