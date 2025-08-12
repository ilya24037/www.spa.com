<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;

// Проверяем последний черновик
$ad = Ad::where('status', 'draft')->latest()->first();

if ($ad) {
    echo "Draft found:\n";
    echo "ID: " . $ad->id . "\n";
    echo "Title: " . $ad->title . "\n";
    echo "Services (raw): " . $ad->services . "\n";
    
    $services = json_decode($ad->services, true);
    echo "Services (decoded): ";
    if ($services) {
        echo json_encode($services, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo "null";
    }
    echo "\n";
    
    // Проверяем другие JSON поля для сравнения
    echo "Clients (raw): " . $ad->clients . "\n";
    echo "Service Location (raw): " . $ad->service_location . "\n";
    echo "Service Provider (raw): " . $ad->service_provider . "\n";
} else {
    echo "No drafts found\n";
}

// Проверяем структуру таблицы
$schema = \DB::select("SHOW COLUMNS FROM ads WHERE Field = 'services'");
if (!empty($schema)) {
    echo "\nServices column info:\n";
    echo "Type: " . $schema[0]->Type . ", Default: " . $schema[0]->Default . "\n";
}