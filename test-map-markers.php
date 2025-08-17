<?php

use App\Domain\Ad\Models\Ad;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "=== ТЕСТ МЕТОК НА КАРТЕ ИЗ ОБЪЯВЛЕНИЙ ===\n\n";

// Получаем активные объявления с адресами
$ads = Ad::where('status', 'active')
    ->whereNotNull('address')
    ->take(10)
    ->get(['id', 'title', 'address', 'geo', 'status']);

echo "Найдено активных объявлений с адресами: " . $ads->count() . "\n\n";

foreach ($ads as $ad) {
    echo "ID: {$ad->id}\n";
    echo "Заголовок: {$ad->title}\n";
    echo "Адрес: {$ad->address}\n";
    
    // Парсим geo для получения координат
    $geo = is_string($ad->geo) ? json_decode($ad->geo, true) : $ad->geo;
    
    if (is_array($geo) && isset($geo['lat']) && isset($geo['lng'])) {
        echo "Координаты: lat={$geo['lat']}, lng={$geo['lng']}\n";
        echo "✅ Метка будет отображена на карте\n";
    } else {
        echo "❌ Нет координат - метка не будет отображена\n";
    }
    
    echo "---\n";
}

echo "\n📍 ИТОГ:\n";
echo "Для отображения меток на карте нужны:\n";
echo "1. Статус объявления = 'active'\n";
echo "2. Заполненное поле address\n";
echo "3. Координаты в поле geo (lat, lng)\n";
echo "\nЕсли меток мало, создайте больше активных объявлений с адресами.\n";