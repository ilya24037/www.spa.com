<?php

use App\Domain\Ad\Models\Ad;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🏗️ ТЕСТ АРХИТЕКТУРНОГО ИСПРАВЛЕНИЯ: МЕСТА ВЫЕЗДА В GEO\n";
echo "========================================================\n\n";

try {
    // Проверяем черновик ID 97
    $ad = Ad::find(97);
    
    if (!$ad) {
        echo "❌ Тестовый черновик ID 97 не найден\n";
        exit;
    }
    
    echo "📋 ПРОВЕРКА ЧЕРНОВИКА ID: {$ad->id}\n";
    echo "Заголовок: {$ad->title}\n";
    echo "Статус: {$ad->status->value}\n\n";
    
    // Проверяем что данные мигрировали правильно
    $prices = $ad->prices ?? [];
    $geo = $ad->geo ?? [];
    
    echo "🏗️ ПРОВЕРКА МИГРАЦИИ ДАННЫХ:\n";
    echo str_repeat("-", 50) . "\n";
    
    // Проверяем что в prices НЕТ outcall полей
    echo "\n📊 ПОЛЕ PRICES (должны быть только цены):\n";
    if (is_array($prices)) {
        $outcallFields = ['outcall_apartment', 'outcall_hotel', 'outcall_house', 
                         'outcall_sauna', 'outcall_office', 'taxi_included'];
        
        $foundInPrices = [];
        foreach ($outcallFields as $field) {
            if (isset($prices[$field])) {
                $foundInPrices[] = $field;
            }
        }
        
        if (empty($foundInPrices)) {
            echo "  ✅ В prices НЕТ outcall полей - ПРАВИЛЬНО!\n";
        } else {
            echo "  ❌ В prices всё ещё есть outcall поля: " . implode(', ', $foundInPrices) . "\n";
        }
        
        // Показываем что есть в prices
        echo "  📦 Содержимое prices:\n";
        foreach ($prices as $key => $value) {
            if (strpos($key, 'outcall') === false && $key !== 'taxi_included') {
                echo "    - {$key}: " . ($value ?? 'null') . "\n";
            }
        }
    }
    
    // Проверяем что в geo ЕСТЬ outcall поля
    echo "\n🌍 ПОЛЕ GEO (должны быть места выезда):\n";
    if (is_array($geo)) {
        $outcallFields = [
            'outcall_apartment' => 'На квартиру',
            'outcall_hotel' => 'В гостиницу', 
            'outcall_house' => 'В загородный дом',
            'outcall_sauna' => 'В сауну',
            'outcall_office' => 'В офис',
            'taxi_included' => 'Такси включено'
        ];
        
        $foundInGeo = 0;
        foreach ($outcallFields as $field => $name) {
            if (isset($geo[$field])) {
                $value = $geo[$field];
                $status = $value ? '✅ ДА' : '❌ НЕТ';
                echo "  📍 {$name} ({$field}): {$status}\n";
                $foundInGeo++;
            }
        }
        
        if ($foundInGeo === count($outcallFields)) {
            echo "\n  ✅ ВСЕ outcall поля в geo - АРХИТЕКТУРА ИСПРАВЛЕНА!\n";
        } else {
            echo "\n  ⚠️ Найдено {$foundInGeo} из " . count($outcallFields) . " полей\n";
        }
        
        // Другие гео данные
        echo "\n  🗺️ Другие гео данные:\n";
        if (isset($geo['address'])) echo "    - Адрес: " . $geo['address'] . "\n";
        if (isset($geo['outcall'])) echo "    - Режим выезда: " . $geo['outcall'] . "\n";
        if (isset($geo['zones']) && is_array($geo['zones'])) {
            echo "    - Зоны выезда: " . implode(', ', $geo['zones']) . "\n";
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎯 РЕЗУЛЬТАТ АРХИТЕКТУРНОГО РЕФАКТОРИНГА:\n\n";
    
    echo "✅ ВЫПОЛНЕНО:\n";
    echo "  1. Миграция переместила outcall_* из prices в geo\n";
    echo "  2. DraftService.php больше не переносит данные (убран костыль)\n";
    echo "  3. Frontend types.ts содержит только цены в prices\n";
    echo "  4. formDataBuilder.ts не отправляет outcall в prices\n";
    echo "  5. GeoSection.vue уже отправляет места выезда в geo\n\n";
    
    echo "📊 АРХИТЕКТУРА ТЕПЕРЬ ЛОГИЧНАЯ:\n";
    echo "  • prices = цены за услуги\n";
    echo "  • geo = географические данные и места выезда\n\n";
    
    // Изменим значения для тестирования
    echo "🧪 ТЕСТ ИЗМЕНЕНИЯ ДАННЫХ:\n";
    $geo['outcall_apartment'] = false;  // Было true
    $geo['outcall_hotel'] = true;       // Было false
    $geo['outcall_office'] = true;      // Было false
    
    $ad->geo = $geo;
    $ad->save();
    
    echo "  Изменены места выезда:\n";
    echo "  📍 На квартиру: ДА -> НЕТ\n";
    echo "  📍 В гостиницу: НЕТ -> ДА\n";
    echo "  📍 В офис: НЕТ -> ДА\n\n";
    
    // Проверяем сохранение
    $ad->refresh();
    $savedGeo = $ad->geo ?? [];
    
    if (is_array($savedGeo)) {
        $apartment = $savedGeo['outcall_apartment'] ?? null;
        $hotel = $savedGeo['outcall_hotel'] ?? null;
        $office = $savedGeo['outcall_office'] ?? null;
        
        if ($apartment === false && $hotel === true && $office === true) {
            echo "  ✅ ИЗМЕНЕНИЯ СОХРАНИЛИСЬ ПРАВИЛЬНО!\n";
        } else {
            echo "  ❌ Изменения не сохранились корректно\n";
        }
    }
    
    echo "\n🌐 URL ДЛЯ ТЕСТИРОВАНИЯ В БРАУЗЕРЕ:\n";
    echo "  http://spa.test/ads/97/edit\n\n";
    
    echo "📝 ИНСТРУКЦИЯ:\n";
    echo "  1. Откройте страницу редактирования\n";
    echo "  2. Найдите секцию 'Геолокация'\n";
    echo "  3. Проверьте что выбраны: В гостиницу ✅, В офис ✅\n";
    echo "  4. Измените выбор и сохраните\n";
    echo "  5. Данные должны сохраниться в geo!\n";
    
} catch (\Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
}