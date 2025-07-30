<?php
require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Проверяем, есть ли уже эта миграция
    $exists = DB::table('migrations')
        ->where('migration', '2024_12_19_000000_create_master_media_tables')
        ->exists();
    
    if (!$exists) {
        // Добавляем запись о миграции
        DB::table('migrations')->insert([
            'migration' => '2024_12_19_000000_create_master_media_tables',
            'batch' => 30
        ]);
        echo "✅ Миграция успешно помечена как выполненная\n";
    } else {
        echo "ℹ️ Миграция уже помечена как выполненная\n";
    }
    
    // Также пометим другие проблемные миграции если они есть
    $problemMigrations = [
        '2025_07_25_134821_add_physical_parameters_to_master_profiles_table',
        '2025_07_25_143335_add_physical_params_to_master_profiles',
        '2025_07_25_143524_add_physical_params_to_master_profiles',
        '2025_07_25_151213_add_physical_parameters_to_ads_table',
        'add_physical_params_migration'
    ];
    
    foreach ($problemMigrations as $migration) {
        $exists = DB::table('migrations')
            ->where('migration', $migration)
            ->exists();
            
        if (!$exists) {
            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => 30
            ]);
            echo "✅ Пропущена миграция: $migration\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
} 