<?php

use App\Domain\Ad\Models\Ad;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "=== ДОБАВЛЕНИЕ КООРДИНАТ К ОБЪЯВЛЕНИЯМ ===\n\n";

// Координаты районов Перми
$permDistricts = [
    'Ленинский' => ['lat' => 58.0105, 'lng' => 56.2502],
    'Свердловский' => ['lat' => 58.0296, 'lng' => 56.2865],
    'Мотовилихинский' => ['lat' => 58.0048, 'lng' => 56.2092],
    'Дзержинский' => ['lat' => 58.0183, 'lng' => 56.1995],
    'Индустриальный' => ['lat' => 58.0748, 'lng' => 56.2565],
    'Кировский' => ['lat' => 57.9847, 'lng' => 56.1675],
    'Орджоникидзевский' => ['lat' => 58.0655, 'lng' => 56.3241],
];

// Получаем объявления без координат
$ads = Ad::where('status', 'active')
    ->whereNotNull('address')
    ->get();

$updated = 0;

foreach ($ads as $ad) {
    $geo = is_string($ad->geo) ? json_decode($ad->geo, true) : $ad->geo;
    
    // Если уже есть координаты - пропускаем
    if (is_array($geo) && isset($geo['lat']) && isset($geo['lng'])) {
        continue;
    }
    
    // Генерируем координаты на основе адреса
    $address = $ad->address;
    $baseCoords = ['lat' => 58.0105, 'lng' => 56.2502]; // Центр Перми
    
    // Проверяем район в адресе
    foreach ($permDistricts as $district => $coords) {
        if (stripos($address, $district) !== false) {
            $baseCoords = $coords;
            break;
        }
    }
    
    // Добавляем небольшое случайное смещение (в пределах района)
    $lat = $baseCoords['lat'] + (mt_rand(-50, 50) / 10000);
    $lng = $baseCoords['lng'] + (mt_rand(-50, 50) / 10000);
    
    // Обновляем geo данные
    $geoData = [
        'lat' => $lat,
        'lng' => $lng,
        'address' => $address,
        'city' => 'Пермь',
        'district' => null
    ];
    
    // Определяем район
    foreach ($permDistricts as $district => $coords) {
        if (stripos($address, $district) !== false) {
            $geoData['district'] = $district . ' район';
            break;
        }
    }
    
    $ad->geo = json_encode($geoData);
    $ad->save();
    
    echo "Обновлено ID {$ad->id}: {$ad->title}\n";
    echo "  Адрес: {$address}\n";
    echo "  Координаты: lat={$lat}, lng={$lng}\n";
    $updated++;
}

echo "\n✅ ГОТОВО!\n";
echo "Обновлено объявлений: {$updated}\n";
echo "Теперь на карте будут отображаться все активные объявления с адресами.\n";