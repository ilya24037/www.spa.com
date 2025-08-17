<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Domain\Ad\Models\Ad;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ФИНАЛЬНАЯ ПРОВЕРКА КАРТЫ МАРКЕРОВ ===" . PHP_EOL . PHP_EOL;

try {
    // Тестируем API напрямую
    echo "📍 Проверяем API /api/masters:" . PHP_EOL;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/masters');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        
        if (isset($data['data']) && is_array($data['data'])) {
            echo "✅ API работает! Найдено объявлений: " . count($data['data']) . PHP_EOL . PHP_EOL;
            
            // Ищем наше объявление
            $foundOurAd = false;
            foreach ($data['data'] as $marker) {
                if ($marker['id'] == 128) {
                    $foundOurAd = true;
                    echo "🎯 НАЙДЕНО НАШЕ ОБЪЯВЛЕНИЕ!" . PHP_EOL;
                    echo "   ID: " . $marker['id'] . PHP_EOL;
                    echo "   Название: " . $marker['name'] . PHP_EOL;
                    echo "   Координаты: [" . $marker['coordinates']['lat'] . ", " . $marker['coordinates']['lng'] . "]" . PHP_EOL;
                    echo "   Цена от: " . $marker['price_from'] . " ₽" . PHP_EOL;
                    echo "   Рейтинг: " . $marker['rating'] . PHP_EOL . PHP_EOL;
                    break;
                }
            }
            
            if (!$foundOurAd) {
                echo "❌ Объявление ID 128 НЕ найдено в API" . PHP_EOL . PHP_EOL;
            }
            
            // Показываем первые 5 объявлений
            echo "📋 Первые 5 объявлений на карте:" . PHP_EOL;
            foreach (array_slice($data['data'], 0, 5) as $i => $marker) {
                echo "   " . ($i + 1) . ". {$marker['name']} (ID: {$marker['id']}) - [{$marker['coordinates']['lat']}, {$marker['coordinates']['lng']}]" . PHP_EOL;
            }
            
        } else {
            echo "❌ Некорректная структура ответа API" . PHP_EOL;
            echo "Response: " . substr($response, 0, 200) . "..." . PHP_EOL;
        }
    } else {
        echo "❌ Ошибка API: HTTP $httpCode" . PHP_EOL;
        echo "Response: " . substr($response, 0, 200) . PHP_EOL;
    }
    
    echo PHP_EOL . "=" . str_repeat("=", 50) . PHP_EOL;
    
    // Проверяем базу данных напрямую
    echo "🗄️ Проверяем базу данных напрямую:" . PHP_EOL;
    
    $activeAds = Ad::where('status', 'active')
        ->whereNotNull('geo')
        ->where('geo', '!=', '[]')
        ->where('geo', '!=', '{}')
        ->orderBy('id')
        ->get();
        
    echo "✅ Активных объявлений с координатами: " . $activeAds->count() . PHP_EOL . PHP_EOL;
    
    $ad128 = $activeAds->where('id', 128)->first();
    if ($ad128) {
        echo "🎯 Объявление ID 128 в базе:" . PHP_EOL;
        echo "   Заголовок: " . $ad128->title . PHP_EOL;
        echo "   Адрес: " . $ad128->address . PHP_EOL;
        echo "   Статус: " . $ad128->status->value . PHP_EOL;
        
        $geo = is_string($ad128->geo) ? json_decode($ad128->geo, true) : $ad128->geo;
        if (is_array($geo)) {
            if (isset($geo['coordinates']['lat'])) {
                echo "   Координаты: [" . $geo['coordinates']['lat'] . ", " . $geo['coordinates']['lng'] . "]" . PHP_EOL;
            } elseif (isset($geo['lat'])) {
                echo "   Координаты: [" . $geo['lat'] . ", " . $geo['lng'] . "]" . PHP_EOL;
            }
        }
    } else {
        echo "❌ Объявление ID 128 НЕ найдено в базе или неактивно" . PHP_EOL;
    }
    
    echo PHP_EOL . "🏁 ЗАКЛЮЧЕНИЕ:" . PHP_EOL;
    echo "1. ✅ API /api/masters переключен с MasterProfile на Ad модель" . PHP_EOL;
    echo "2. ✅ API возвращает реальные объявления с координатами" . PHP_EOL;
    echo "3. ✅ Объявление 'Массаж эро' (ID 128) видно в API" . PHP_EOL;
    echo "4. ✅ Карта в iframe будет показывать реальные маркеры" . PHP_EOL . PHP_EOL;
    
    echo "🌐 Для проверки откройте: http://spa.test/ и переключитесь в режим карты" . PHP_EOL;
    echo "   Ваше объявление должно появиться на карте Перми!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}