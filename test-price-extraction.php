<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Domain\Ad\Models\Ad;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ТЕСТИРОВАНИЕ ИЗВЛЕЧЕНИЯ ЦЕН ИЗ ОБЪЯВЛЕНИЙ ===" . PHP_EOL . PHP_EOL;

try {
    // Проверяем объявление ID 128
    $ad = Ad::find(128);
    
    if (!$ad) {
        echo "❌ Объявление ID 128 не найдено" . PHP_EOL;
        exit;
    }
    
    echo "🎯 Объявление ID 128:" . PHP_EOL;
    echo "   Заголовок: " . $ad->title . PHP_EOL;
    echo "   Статус: " . $ad->status->value . PHP_EOL . PHP_EOL;
    
    echo "💰 Проверяем поле 'price':" . PHP_EOL;
    echo "   Значение: " . ($ad->price ?: 'NULL') . PHP_EOL;
    echo "   Тип: " . gettype($ad->price) . PHP_EOL . PHP_EOL;
    
    echo "💰 Проверяем поле 'prices':" . PHP_EOL;
    if ($ad->prices) {
        echo "   Значение: " . (is_array($ad->prices) ? json_encode($ad->prices) : $ad->prices) . PHP_EOL;
    } else {
        echo "   Значение: NULL" . PHP_EOL;
    }
    echo "   Тип: " . gettype($ad->prices) . PHP_EOL . PHP_EOL;
    
    // Проверяем все поля связанные с ценой
    echo "🔍 Все поля модели содержащие 'price':" . PHP_EOL;
    $attributes = $ad->getAttributes();
    foreach ($attributes as $key => $value) {
        if (strpos(strtolower($key), 'price') !== false) {
            echo "   $key: " . ($value ?: 'NULL') . " (тип: " . gettype($value) . ")" . PHP_EOL;
        }
    }
    
    echo PHP_EOL . "🧪 ТЕСТИРУЕМ ЛОГИКУ ИЗВЛЕЧЕНИЯ:" . PHP_EOL;
    
    // Тестируем нашу логику
    $priceFrom = 2000; // Дефолтная цена
    if ($ad->prices) {
        echo "✅ Найдено поле 'prices'" . PHP_EOL;
        $prices = is_string($ad->prices) ? json_decode($ad->prices, true) : $ad->prices;
        echo "   Декодированное значение: " . print_r($prices, true) . PHP_EOL;
        
        if (is_array($prices) && !empty($prices)) {
            echo "✅ Prices - это непустой массив" . PHP_EOL;
            $priceValues = array_column($prices, 'price');
            echo "   Извлеченные цены: " . print_r($priceValues, true) . PHP_EOL;
            
            if (!empty($priceValues)) {
                $priceFrom = min($priceValues);
                echo "✅ Минимальная цена: $priceFrom" . PHP_EOL;
            } else {
                echo "❌ Массив цен пуст" . PHP_EOL;
            }
        } else {
            echo "❌ Prices не является массивом или пуст" . PHP_EOL;
        }
    } else {
        echo "❌ Поле 'prices' пусто или NULL" . PHP_EOL;
    }
    
    // Пробуем fallback на price
    if ($priceFrom === 2000 && $ad->price) {
        echo "🔄 Используем fallback на поле 'price': " . $ad->price . PHP_EOL;
        $priceFrom = (float)$ad->price;
    }
    
    echo PHP_EOL . "🎯 ИТОГОВАЯ ЦЕНА: $priceFrom ₽" . PHP_EOL;
    
    echo PHP_EOL . "=" . str_repeat("=", 60) . PHP_EOL;
    
    // Проверяем несколько других объявлений
    echo "🔍 Проверяем другие объявления:" . PHP_EOL;
    
    $otherAds = Ad::where('status', 'active')
        ->whereNotNull('geo')
        ->where('geo', '!=', '[]')
        ->where('geo', '!=', '{}')
        ->limit(5)
        ->get();
        
    foreach ($otherAds as $ad) {
        echo "   ID {$ad->id}: prices=" . ($ad->prices ? 'есть' : 'нет') . ", price=" . ($ad->price ? 'есть' : 'нет') . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}