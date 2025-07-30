<?php

// Простая проверка данных черновика
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Находим черновик
$ad = \App\Models\Ad::find(137);

if ($ad) {
    echo "=== ДАННЫЕ ЧЕРНОВИКА 137 ===\n";
    echo "Title: " . $ad->title . "\n";
    echo "Services: " . json_encode($ad->services) . "\n";
    echo "Services Additional Info: " . ($ad->services_additional_info ?: 'empty') . "\n";
    echo "Schedule: " . json_encode($ad->schedule) . "\n";
    echo "Schedule Notes: " . ($ad->schedule_notes ?: 'empty') . "\n";
    echo "Status: " . $ad->status . "\n";
    echo "Updated: " . $ad->updated_at . "\n";
    
    echo "\n=== ВСЕ ПОЛЯ SERVICES ===\n";
    echo "service_location: " . json_encode($ad->service_location) . "\n";
    echo "service_provider: " . json_encode($ad->service_provider) . "\n";
    echo "services: " . json_encode($ad->services) . "\n";
    echo "services_additional_info: " . ($ad->services_additional_info ?: 'empty') . "\n";
    
    echo "\n=== ВСЕ ПОЛЯ SCHEDULE ===\n";
    echo "schedule: " . json_encode($ad->schedule) . "\n";
    echo "schedule_notes: " . ($ad->schedule_notes ?: 'empty') . "\n";
} else {
    echo "Черновик ID 137 не найден\n";
} 