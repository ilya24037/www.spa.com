<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Диагностика проблемы с маркером объявления ID 128...\n\n";

// Проверяем объявление
$ad = \App\Domain\Ad\Models\Ad::find(128);

if (!$ad) {
    echo "❌ Объявление с ID 128 не найдено\n";
    exit(1);
}

echo "📋 ДАННЫЕ ОБЪЯВЛЕНИЯ:\n";
echo "==================\n";
echo "ID: {$ad->id}\n";
echo "Название: {$ad->title}\n";
echo "Статус: {$ad->status->value}\n";
echo "Пользователь: {$ad->user_id}\n";
echo "Адрес: {$ad->address}\n";
echo "Геоданные: {$ad->geo}\n";
echo "Создано: {$ad->created_at}\n";
echo "Обновлено: {$ad->updated_at}\n";

// Декодируем geo данные
if ($ad->geo) {
    $geoData = json_decode($ad->geo, true);
    if ($geoData && is_array($geoData)) {
        echo "\n📍 ГЕОКООРДИНАТЫ:\n";
        echo "===============\n";
        if (isset($geoData['lat'])) echo "Широта: {$geoData['lat']}\n";
        if (isset($geoData['lng'])) echo "Долгота: {$geoData['lng']}\n";
        if (isset($geoData['city'])) echo "Город: {$geoData['city']}\n";
        if (isset($geoData['address'])) echo "Адрес в geo: {$geoData['address']}\n";
        
        // Проверяем попадают ли координаты в Москву
        if (isset($geoData['lat']) && isset($geoData['lng'])) {
            $lat = (float)$geoData['lat'];
            $lng = (float)$geoData['lng'];
            
            // Примерные границы Москвы
            $moscowBounds = [
                'lat_min' => 55.48,
                'lat_max' => 55.95,
                'lng_min' => 37.32,
                'lng_max' => 37.87
            ];
            
            $isInMoscow = (
                $lat >= $moscowBounds['lat_min'] && $lat <= $moscowBounds['lat_max'] &&
                $lng >= $moscowBounds['lng_min'] && $lng <= $moscowBounds['lng_max']
            );
            
            echo "\n🗺️ ПРОВЕРКА КООРДИНАТ:\n";
            echo "====================\n";
            echo "Координаты попадают в Москву: " . ($isInMoscow ? "✅ ДА" : "❌ НЕТ") . "\n";
            
            if (!$isInMoscow) {
                echo "⚠️ ПРОБЛЕМА: Координаты не попадают в границы Москвы!\n";
                echo "Это может быть причиной отсутствия маркера на карте.\n";
                
                // Предлагаем московские координаты
                echo "\n💡 РЕКОМЕНДУЕМЫЕ КООРДИНАТЫ ДЛЯ МОСКВЫ:\n";
                echo "Широта: 55.7558 (центр Москвы)\n";
                echo "Долгота: 37.6176 (центр Москвы)\n";
            }
        }
    } else {
        echo "\n❌ Ошибка декодирования geo данных: " . json_last_error_msg() . "\n";
    }
} else {
    echo "\n❌ Геоданные отсутствуют\n";
}

// Проверяем другие активные объявления для сравнения
echo "\n\n📊 ДРУГИЕ АКТИВНЫЕ ОБЪЯВЛЕНИЯ:\n";
echo "=============================\n";
$activeAds = \App\Domain\Ad\Models\Ad::where('status', 'active')->limit(5)->get();

foreach ($activeAds as $activeAd) {
    echo "ID {$activeAd->id}: {$activeAd->title} ";
    if ($activeAd->geo) {
        $geo = json_decode($activeAd->geo, true);
        if ($geo && isset($geo['lat'], $geo['lng'])) {
            echo "(lat: {$geo['lat']}, lng: {$geo['lng']})";
        } else {
            echo "(geo: невалидный JSON)";
        }
    } else {
        echo "(geo: отсутствует)";
    }
    echo "\n";
}

echo "\n🔗 СЛЕДУЮЩИЕ ШАГИ:\n";
echo "=================\n";
echo "1. Проверить API эндпоинт для карты\n";
echo "2. Проверить frontend компонент карты\n";
echo "3. Исправить координаты если нужно\n";

echo "\nДиагностика завершена.\n";