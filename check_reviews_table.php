<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "📋 Проверка таблицы reviews:\n\n";

try {
    $columns = DB::select("DESCRIBE reviews");
    echo "✅ Таблица reviews существует:\n";
    foreach($columns as $column) {
        echo "  - {$column->Field} ({$column->Type})" . ($column->Null === 'YES' ? ' [nullable]' : '') . "\n";
    }
    
    echo "\n📊 Количество записей: " . DB::table('reviews')->count() . "\n";
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}