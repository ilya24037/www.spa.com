<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Обновление геокоординат для объявления ID 128...\n";

// Найдем объявление
$ad = \App\Domain\Ad\Models\Ad::find(128);

if (!$ad) {
    echo "❌ Объявление с ID 128 не найдено\n";
    exit(1);
}

echo "📋 Текущие данные объявления:\n";
echo "ID: {$ad->id}\n";
echo "Адрес: {$ad->address}\n";
echo "Статус: {$ad->status->value}\n";
echo "Геоданные: " . ($ad->geo ?: 'НЕ УКАЗАНЫ') . "\n";

// Координаты для ул. Адмирала Ушакова, 10, Пермь
// Примерные координаты (нужно уточнить точные)
$coordinates = [
    'lat' => 58.0174,  // Широта для Перми, ул. Адмирала Ушакова
    'lng' => 56.2346   // Долгота для Перми, ул. Адмирала Ушакова
];

$geoData = json_encode($coordinates);

try {
    $ad->update([
        'geo' => $geoData
    ]);
    
    echo "\n✅ Геокоординаты успешно обновлены!\n";
    echo "Координаты: lat={$coordinates['lat']}, lng={$coordinates['lng']}\n";
    echo "JSON geo: {$geoData}\n";
    
    // Проверяем что обновилось
    $ad->refresh();
    echo "\nПроверка обновления:\n";
    echo "Новые geo данные в БД: {$ad->geo}\n";
    
    echo "\n🗺️ Объявление должно появиться на карте по адресу: {$ad->address}\n";
    echo "🌐 URL объявления: http://spa.test/ads/{$ad->id}\n";
    
} catch (\Exception $e) {
    echo "\n❌ Ошибка при обновлении геоданных:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}

echo "\n💡 Примечание: Координаты приблизительные. Для точного позиционирования нужна геокодирование через API карт.\n";
echo "\n🎉 Готово! Маркер должен появиться на карте.\n";