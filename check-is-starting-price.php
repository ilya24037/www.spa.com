<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Проверяем структуру таблицы
$schema = \DB::select("SHOW COLUMNS FROM ads WHERE Field = 'is_starting_price'");
if (!empty($schema)) {
    echo "Column is_starting_price info:\n";
    echo "Type: " . $schema[0]->Type . ", Default: " . $schema[0]->Default . "\n";
    echo "\n";
}

// Проверяем последний черновик
$ad = \App\Domain\Ad\Models\Ad::where('status', 'draft')->latest()->first();
if ($ad) {
    echo "Latest draft:\n";
    echo "ID: " . $ad->id . "\n";
    echo "is_starting_price: " . ($ad->is_starting_price ? 'true' : 'false') . "\n";
    echo "Type: " . gettype($ad->is_starting_price) . "\n";
}