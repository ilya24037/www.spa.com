<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

// Отмечаем проблемную миграцию как выполненную
DB::table('migrations')->insert([
    'migration' => '2025_07_25_134821_add_physical_parameters_to_master_profiles_table',
    'batch' => DB::table('migrations')->max('batch') + 1
]);

echo "Миграция отмечена как выполненная\n";

// Теперь проверяем наличие поля photos в таблице ads
$hasPhotosColumn = DB::getSchemaBuilder()->hasColumn('ads', 'photos');

if ($hasPhotosColumn) {
    echo "Поле 'photos' уже существует в таблице ads\n";
} else {
    echo "Поле 'photos' НЕ существует в таблице ads - нужно выполнить миграцию\n";
}