<?php

use App\Domain\Ad\Models\Ad;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "=== ПРОВЕРКА ДАННЫХ ДЛЯ ГЛАВНОЙ СТРАНИЦЫ ===\n\n";

// Получаем первые 3 объявления как на главной
$ads = Ad::where('status', 'active')
    ->whereNotNull('address')
    ->take(3)
    ->get();

foreach ($ads as $ad) {
    echo "ID: {$ad->id} - {$ad->title}\n";
    
    // Проверка photos
    echo "  Photos: ";
    if ($ad->photos) {
        $photos = is_string($ad->photos) ? json_decode($ad->photos, true) : $ad->photos;
        if (is_array($photos) && !empty($photos)) {
            $firstPhoto = $photos[0];
            echo is_string($firstPhoto) ? "✅ Строка: $firstPhoto" : "❌ НЕ строка: " . gettype($firstPhoto);
        } else {
            echo "❌ Пустой массив или не массив";
        }
    } else {
        echo "❌ Нет фото";
    }
    echo "\n";
    
    // Проверка prices
    echo "  Prices: ";
    if ($ad->prices) {
        $prices = is_string($ad->prices) ? json_decode($ad->prices, true) : $ad->prices;
        if (is_array($prices) && !empty($prices)) {
            $priceValues = array_column($prices, 'price');
            if (!empty($priceValues)) {
                echo "✅ Мин. цена: " . min($priceValues);
            } else {
                echo "❌ Нет поля 'price' в элементах";
            }
        } else {
            echo "❌ Пустой массив или не массив";
        }
    } else {
        echo "Пусто, используем price: " . ($ad->price ?: 'тоже пусто');
    }
    echo "\n";
    
    // Проверка services
    echo "  Services: ";
    if ($ad->services) {
        $services = is_string($ad->services) ? json_decode($ad->services, true) : $ad->services;
        if (is_array($services)) {
            $serviceNames = array_slice(array_keys($services), 0, 3);
            echo "✅ " . implode(', ', $serviceNames);
        } else {
            echo "❌ Не массив";
        }
    } else {
        echo "❌ Пусто";
    }
    echo "\n";
    
    // Проверка geo
    echo "  Geo: ";
    $geo = is_string($ad->geo) ? json_decode($ad->geo, true) : $ad->geo;
    if (is_array($geo) && isset($geo['lat']) && isset($geo['lng'])) {
        echo "✅ lat={$geo['lat']}, lng={$geo['lng']}";
    } else {
        echo "❌ Нет координат";
    }
    echo "\n\n";
}

echo "✅ Проверка завершена\n";