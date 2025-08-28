<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Загружаем Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 ПРОВЕРКА ПОЛЯ SCHEDULE_NOTES В БД\n";
echo "===================================\n\n";

try {
    $columns = DB::select('DESCRIBE ads');
    $hasScheduleNotes = false;
    
    echo "📋 Поиск поля schedule_notes:\n";
    
    foreach($columns as $column) {
        if ($column->Field === 'schedule_notes') {
            echo "  ✅ Поле schedule_notes найдено: " . $column->Type . ($column->Null === 'YES' ? ' [nullable]' : '') . "\n";
            $hasScheduleNotes = true;
            break;
        }
    }
    
    if (!$hasScheduleNotes) {
        echo "  ❌ Поле schedule_notes НЕ найдено в БД!\n";
        echo "\n📋 Доступные поля связанные с schedule:\n";
        foreach($columns as $column) {
            if (str_contains($column->Field, 'schedule')) {
                echo "    - " . $column->Field . " (" . $column->Type . ")\n";
            }
        }
    }
    
    echo "\n🎯 РЕЗУЛЬТАТ:\n";
    if ($hasScheduleNotes) {
        echo "✅ Поле schedule_notes существует в БД\n";
    } else {
        echo "❌ Поле schedule_notes ОТСУТСТВУЕТ в БД - нужна миграция!\n";
    }

} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}