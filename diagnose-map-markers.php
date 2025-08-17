<?php

use App\Domain\Ad\Models\Ad;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "=== ДИАГНОСТИКА МЕТОК НА КАРТЕ ===" . PHP_EOL . PHP_EOL;

// Проверяем объявление ID 128
$ad = Ad::find(128);

if ($ad) {
    echo "📋 Основные данные:" . PHP_EOL;
    echo "   ID: " . $ad->id . PHP_EOL;
    echo "   Заголовок: " . $ad->title . PHP_EOL;
    echo "   Статус: " . $ad->status->value . PHP_EOL;
    echo "   Адрес: " . $ad->address . PHP_EOL . PHP_EOL;
    
    echo "🗺️ Координаты (raw):" . PHP_EOL;
    echo "   geo: " . json_encode($ad->geo) . PHP_EOL . PHP_EOL;
    
    echo "🧩 Парсинг координат (как в HomeController):" . PHP_EOL;
    $geo = is_string($ad->geo) ? json_decode($ad->geo, true) : $ad->geo;
    
    if (is_array($geo)) {
        $lat = null;
        $lng = null;
        
        if (isset($geo['lat']) && isset($geo['lng'])) {
            $lat = (float)$geo['lat'];
            $lng = (float)$geo['lng'];
        } elseif (isset($geo['coordinates']['lat']) && isset($geo['coordinates']['lng'])) {
            $lat = (float)$geo['coordinates']['lat'];
            $lng = (float)$geo['coordinates']['lng'];
        }
        
        echo "   lat: " . ($lat ?? 'НЕТ') . PHP_EOL;
        echo "   lng: " . ($lng ?? 'НЕТ') . PHP_EOL;
        
        if ($lat && $lng) {
            echo "✅ Координаты найдены! lat=$lat, lng=$lng" . PHP_EOL;
            
            // Проверяем, корректны ли координаты для Перми
            if ($lat >= 57.5 && $lat <= 58.5 && $lng >= 55.5 && $lng <= 56.5) {
                echo "✅ Координаты корректны для Перми!" . PHP_EOL;
            } else {
                echo "❌ Координаты НЕ соответствуют Перми!" . PHP_EOL;
                echo "   Пермь: lat ~58.0, lng ~56.2" . PHP_EOL;
            }
        } else {
            echo "❌ Координаты НЕ найдены в поле geo!" . PHP_EOL;
        }
        
        echo PHP_EOL . "🔍 Полная структура geo:" . PHP_EOL;
        print_r($geo);
        
    } else {
        echo "❌ geo не является массивом!" . PHP_EOL;
        echo "Тип: " . gettype($geo) . PHP_EOL;
        echo "Значение: " . var_export($geo, true) . PHP_EOL;
    }
} else {
    echo "❌ Объявление ID 128 не найдено!" . PHP_EOL;
}

echo PHP_EOL . "=== ПРОВЕРКА ВЫБОРКИ HOMECONTROLLER ===" . PHP_EOL . PHP_EOL;

// Проверяем выборку как в HomeController
$ads = Ad::where('status', 'active')
    ->whereNotNull('address')
    ->take(12)
    ->get();

echo "Найдено активных объявлений с адресом: " . $ads->count() . PHP_EOL;

foreach ($ads as $ad) {
    $geo = is_string($ad->geo) ? json_decode($ad->geo, true) : $ad->geo;
    $lat = null;
    $lng = null;
    
    if (is_array($geo)) {
        // Проверяем два формата координат (как в исправленном HomeController)
        if (isset($geo['lat']) && isset($geo['lng'])) {
            // Формат: {"lat": 58.0, "lng": 56.0}
            $lat = (float)$geo['lat'];
            $lng = (float)$geo['lng'];
        } elseif (isset($geo['coordinates']['lat']) && isset($geo['coordinates']['lng'])) {
            // Формат: {"coordinates": {"lat": 58.0, "lng": 56.0}}
            $lat = (float)$geo['coordinates']['lat'];
            $lng = (float)$geo['coordinates']['lng'];
        }
    }
    
    echo "ID {$ad->id}: {$ad->title}" . PHP_EOL;
    echo "  Адрес: {$ad->address}" . PHP_EOL;
    echo "  Координаты: lat=" . ($lat ?? 'НЕТ') . ", lng=" . ($lng ?? 'НЕТ') . PHP_EOL . PHP_EOL;
}