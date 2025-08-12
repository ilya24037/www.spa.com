<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Проверяем структуру таблицы
$schema = \DB::select("SHOW COLUMNS FROM ads WHERE Field = 'services_additional_info'");
if (!empty($schema)) {
    echo "Column services_additional_info exists:\n";
    echo "Type: " . $schema[0]->Type . ", Default: " . $schema[0]->Default . "\n";
} else {
    echo "Column services_additional_info does not exist\n";
    
    // Проверяем все колонки с похожими названиями
    $allColumns = \DB::select("SHOW COLUMNS FROM ads WHERE Field LIKE '%services%' OR Field LIKE '%additional%'");
    echo "\nRelated columns:\n";
    foreach ($allColumns as $col) {
        echo "- " . $col->Field . " (" . $col->Type . ")\n";
    }
}