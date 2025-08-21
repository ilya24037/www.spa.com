<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Проверяем структуру таблицы ads:\n";
$columns = DB::select('DESCRIBE ads');
foreach($columns as $col) {
    echo $col->Field . ' - ' . $col->Type . "\n";
}

echo "\nПроверяем существование полей schedule и schedule_notes:\n";
$scheduleExists = false;
$scheduleNotesExists = false;

foreach($columns as $col) {
    if ($col->Field === 'schedule') {
        $scheduleExists = true;
        echo "✅ Поле schedule найдено: " . $col->Type . "\n";
    }
    if ($col->Field === 'schedule_notes') {
        $scheduleNotesExists = true;
        echo "✅ Поле schedule_notes найдено: " . $col->Type . "\n";
    }
}

if (!$scheduleExists) {
    echo "❌ Поле schedule НЕ найдено в таблице ads\n";
}
if (!$scheduleNotesExists) {
    echo "❌ Поле schedule_notes НЕ найдено в таблице ads\n";
}
