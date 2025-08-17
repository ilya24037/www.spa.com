<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 Исправление координат объявления ID 128 для Москвы...\n\n";

// Найдем объявление
$ad = \App\Domain\Ad\Models\Ad::find(128);

if (!$ad) {
    echo "❌ Объявление с ID 128 не найдено\n";
    exit(1);
}

echo "📋 ТЕКУЩИЕ ДАННЫЕ:\n";
echo "=================\n";
echo "ID: {$ad->id}\n";
echo "Название: {$ad->title}\n";
echo "Адрес: {$ad->address}\n";
echo "Текущие geo: {$ad->geo}\n";

// Московские координаты (центр города)
$moscowCoordinates = [
    'lat' => 55.7558,
    'lng' => 37.6176,
    'city' => 'Москва',
    'address' => 'Москва, ул. Адмирала Ушакова, 10'  // Обновляем и адрес
];

$newGeoData = json_encode($moscowCoordinates);
$newAddress = "Москва, ул. Адмирала Ушакова, 10";

try {
    $ad->update([
        'geo' => $newGeoData,
        'address' => $newAddress  // Также обновляем адрес на московский
    ]);
    
    echo "\n✅ ОБНОВЛЕНИЕ ЗАВЕРШЕНО:\n";
    echo "========================\n";
    echo "Новый адрес: {$newAddress}\n";
    echo "Новые координаты: lat={$moscowCoordinates['lat']}, lng={$moscowCoordinates['lng']}\n";
    echo "Новые geo данные: {$newGeoData}\n";
    
    // Проверяем что обновилось
    $ad->refresh();
    echo "\n✅ ПРОВЕРКА В БД:\n";
    echo "================\n";
    echo "Адрес в БД: {$ad->address}\n";
    echo "Geo в БД: {$ad->geo}\n";
    
    echo "\n🗺️ РЕЗУЛЬТАТ:\n";
    echo "=============\n";
    echo "✅ Объявление теперь находится в центре Москвы\n";
    echo "✅ Маркер должен появиться на карте\n";
    echo "✅ Координаты попадают в границы Москвы\n";
    
    echo "\n🌐 ССЫЛКИ:\n";
    echo "==========\n";
    echo "Главная с картой: http://spa.test/\n";
    echo "Объявление: http://spa.test/ads/{$ad->id}\n";
    
} catch (\Exception $e) {
    echo "\n❌ Ошибка при обновлении:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Готово! Обновите страницу для просмотра маркера.\n";