<?php

use App\Domain\Ad\Models\Ad;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "=== ТЕСТ ДАННЫХ ДЛЯ КАРТЫ (как HomeController) ===" . PHP_EOL . PHP_EOL;

// Эмулируем точно ту же логику что в HomeController
$ads = Ad::where('status', 'active')
    ->whereNotNull('address')
    ->take(12)
    ->get()
    ->map(function ($ad) {
        // Парсим geo для получения координат
        $geo = is_string($ad->geo) ? json_decode($ad->geo, true) : $ad->geo;
        $lat = null;
        $lng = null;
        
        if (is_array($geo)) {
            // Проверяем два формата координат
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
        
        return [
            'id' => $ad->id,
            'name' => $ad->title ?? 'Мастер',
            'address' => $ad->address,
            'lat' => $lat,
            'lng' => $lng,
            'geo' => $geo
        ];
    });

echo "📋 ИТОГОВЫЕ ДАННЫЕ ДЛЯ FRONTEND:" . PHP_EOL . PHP_EOL;

$mastersWithCoords = $ads->filter(fn($m) => $m['lat'] && $m['lng']);

echo "Всего мастеров: " . $ads->count() . PHP_EOL;
echo "С координатами: " . $mastersWithCoords->count() . PHP_EOL . PHP_EOL;

foreach ($mastersWithCoords->take(3) as $master) {
    echo "✅ Мастер с координатами:" . PHP_EOL;
    echo "   ID: {$master['id']}" . PHP_EOL;
    echo "   Имя: {$master['name']}" . PHP_EOL;
    echo "   lat: {$master['lat']}" . PHP_EOL;
    echo "   lng: {$master['lng']}" . PHP_EOL;
    echo "   Адрес: {$master['address']}" . PHP_EOL . PHP_EOL;
}

// Проверяем конкретно ID 128
$master128 = $ads->firstWhere('id', 128);
if ($master128) {
    echo "🎯 МАСТЕР ID 128 (Массаж эро):" . PHP_EOL;
    echo "   lat: " . ($master128['lat'] ?? 'НЕТ') . PHP_EOL;
    echo "   lng: " . ($master128['lng'] ?? 'НЕТ') . PHP_EOL;
    echo "   Должен появиться на карте: " . ($master128['lat'] && $master128['lng'] ? '✅ ДА' : '❌ НЕТ') . PHP_EOL;
} else {
    echo "❌ Мастер ID 128 не найден в выборке" . PHP_EOL;
}

echo PHP_EOL . "🌐 JSON для проверки во frontend:" . PHP_EOL;
echo json_encode($ads->take(1)->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);