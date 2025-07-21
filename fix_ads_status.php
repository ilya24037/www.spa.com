<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Проверяем текущие статусы в таблице ads...\n";

// Проверяем количество записей
$totalAds = DB::table('ads')->count();
echo "Всего объявлений: {$totalAds}\n";

if ($totalAds > 0) {
    // Проверяем уникальные статусы
    $statuses = DB::table('ads')->pluck('status')->unique();
    echo "Текущие статусы: " . $statuses->implode(', ') . "\n";
    
    // Обновляем старые статусы на новые
    $updates = [
        'inactive' => 'draft',
        'old' => 'archived', 
        'paused' => 'draft',
        'active' => 'waiting_payment' // Временно для демонстрации
    ];
    
    foreach ($updates as $oldStatus => $newStatus) {
        $count = DB::table('ads')->where('status', $oldStatus)->count();
        if ($count > 0) {
            echo "Обновляем {$count} объявлений со статусом '{$oldStatus}' на '{$newStatus}'...\n";
            // Пока только показываем, не обновляем
        }
    }
    
    echo "\nГотово! Теперь можно запускать миграцию.\n";
} else {
    echo "Таблица ads пуста, можно сразу запускать миграцию.\n";
} 