<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;

echo "=== ПРОВЕРКА ЦЕН В ЧЕРНОВИКАХ ===\n\n";

// Получаем все черновики
$drafts = Ad::where('status', 'draft')->latest()->limit(3)->get();

if ($drafts->isEmpty()) {
    echo "Черновиков не найдено\n";
    exit;
}

foreach ($drafts as $ad) {
    echo "--- Черновик #{$ad->id} ---\n";
    echo "Title: {$ad->title}\n";
    echo "Created: {$ad->created_at}\n\n";
    
    // Проверяем поле price (старое)
    echo "OLD PRICE field: " . ($ad->price ?? 'NULL') . "\n";
    
    // Проверяем поле prices (новое JSON)
    echo "NEW PRICES field (raw): " . ($ad->prices ?? 'NULL') . "\n";
    
    if ($ad->prices) {
        $prices = is_string($ad->prices) ? json_decode($ad->prices, true) : $ad->prices;
        echo "NEW PRICES (decoded):\n";
        if (is_array($prices)) {
            foreach ($prices as $key => $value) {
                echo "  {$key}: " . ($value ?? 'NULL') . "\n";
            }
        } else {
            echo "  ERROR: prices не является массивом\n";
        }
    }
    
    // Проверяем другие ценовые поля
    echo "\nOTHER PRICE FIELDS:\n";
    echo "price_per_hour: " . ($ad->price_per_hour ?? 'NULL') . "\n";
    echo "outcall_price: " . ($ad->outcall_price ?? 'NULL') . "\n";
    echo "express_price: " . ($ad->express_price ?? 'NULL') . "\n";
    echo "price_two_hours: " . ($ad->price_two_hours ?? 'NULL') . "\n";
    echo "price_night: " . ($ad->price_night ?? 'NULL') . "\n";
    
    // Симулируем логику из ProfileController
    echo "\n=== ЛОГИКА PROFILECONTROLLER ===\n";
    
    $prices = $ad->prices;
    if (is_string($prices)) {
        $prices = json_decode($prices, true) ?? [];
    }
    $prices = is_array($prices) ? $prices : [];
    
    echo "Processed prices array: " . json_encode($prices) . "\n";
    
    // СТАРАЯ ЛОГИКА (проблемная)
    $possiblePricesOld = array_filter([
        $prices['apartments_1h'] ?? null,
        $prices['outcall_1h'] ?? null,
        $prices['apartments_express'] ?? null
    ]);
    echo "OLD logic - Possible prices (strings): " . json_encode($possiblePricesOld) . "\n";
    if (!empty($possiblePricesOld)) {
        $hourPriceOld = min($possiblePricesOld);
        echo "OLD logic - Selected min price: {$hourPriceOld}\n";
    }
    
    // НОВАЯ ЛОГИКА (исправленная)
    $possiblePrices = array_filter([
        isset($prices['apartments_1h']) ? (float)$prices['apartments_1h'] : null,
        isset($prices['outcall_1h']) ? (float)$prices['outcall_1h'] : null,
        isset($prices['apartments_express']) ? (float)$prices['apartments_express'] : null
    ]);
    echo "NEW logic - Possible prices (numbers): " . json_encode($possiblePrices) . "\n";
    
    if (!empty($possiblePrices)) {
        $hourPrice = min($possiblePrices);
        echo "NEW logic - Selected min price: {$hourPrice}\n";
    } else {
        echo "No hour prices found, using old price: " . ($ad->price ?? 0) . "\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
}
