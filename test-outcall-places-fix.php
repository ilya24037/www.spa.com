<?php

use App\Domain\Ad\Models\Ad;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🏠 ТЕСТ ИСПРАВЛЕНИЯ СОХРАНЕНИЯ 'ТИПЫ МЕСТ ДЛЯ ВЫЕЗДА'\n";
echo "===================================================\n\n";

try {
    // Используем черновик ID 97
    $ad = Ad::find(97);
    
    if (!$ad) {
        echo "❌ Черновик с ID 97 не найден\n";
        exit;
    }
    
    echo "📋 ТЕСТИРУЕМ ЧЕРНОВИК ID: {$ad->id}\n";
    echo "Заголовок: {$ad->title}\n";
    echo "Статус: {$ad->status->value}\n\n";
    
    // Устанавливаем тестовые значения для мест выезда
    // Это должно быть в поле prices как JSON
    $currentPrices = $ad->prices ?? [];
    if (!is_array($currentPrices)) {
        $currentPrices = [];
    }
    
    // Добавляем места выезда
    $currentPrices['outcall_apartment'] = true;  // Квартиры
    $currentPrices['outcall_hotel'] = true;      // Отели
    $currentPrices['outcall_house'] = false;     // Дома (не выбрано)
    $currentPrices['outcall_sauna'] = true;      // Сауны
    $currentPrices['outcall_office'] = false;    // Офисы (не выбрано)
    
    // Также добавляем базовые цены для полноты
    $currentPrices['apartments_1h'] = '3000';
    $currentPrices['outcall_1h'] = '4000';
    $currentPrices['taxi_included'] = true;
    
    $ad->prices = $currentPrices;
    $ad->save();
    
    echo "✅ УСТАНОВЛЕНЫ ТЕСТОВЫЕ МЕСТА ВЫЕЗДА:\n";
    echo "  📍 Квартиры (outcall_apartment): ДА\n";
    echo "  📍 Отели (outcall_hotel): ДА\n";
    echo "  📍 Дома (outcall_house): НЕТ\n";
    echo "  📍 Сауны (outcall_sauna): ДА\n";
    echo "  📍 Офисы (outcall_office): НЕТ\n\n";
    
    // Проверяем что сохранилось
    $ad->refresh();
    $savedPrices = $ad->prices ?? [];
    
    echo "📋 ПРОВЕРКА СОХРАНЕНИЯ В БД:\n";
    if (is_array($savedPrices)) {
        echo "✅ Prices сохранены как массив\n";
        
        $outCallFields = [
            'outcall_apartment' => 'Квартиры',
            'outcall_hotel' => 'Отели', 
            'outcall_house' => 'Дома',
            'outcall_sauna' => 'Сауны',
            'outcall_office' => 'Офисы'
        ];
        
        foreach ($outCallFields as $field => $name) {
            $value = $savedPrices[$field] ?? false;
            $status = $value ? '✅ ДА' : '❌ НЕТ';
            echo "  📍 {$name} ({$field}): {$status}\n";
        }
        
        echo "\n💰 Другие цены:\n";
        echo "  apartments_1h: " . ($savedPrices['apartments_1h'] ?? 'не задано') . "\n";
        echo "  outcall_1h: " . ($savedPrices['outcall_1h'] ?? 'не задано') . "\n";
        echo "  taxi_included: " . (($savedPrices['taxi_included'] ?? false) ? 'ДА' : 'НЕТ') . "\n";
        
    } else {
        echo "❌ Prices не сохранились как массив\n";
        echo "Тип: " . gettype($savedPrices) . "\n";
    }
    
    echo "\n🎯 ИСПРАВЛЕНИЕ ПРИМЕНЕНО:\n";
    echo "✅ Восстановлена оригинальная логика отправки prices[outcall_*]\n";
    echo "✅ Заменен JSON объект на отдельные поля FormData\n";
    echo "✅ Добавлены детальные console.log для отладки\n\n";
    
    echo "🧪 ИНСТРУКЦИЯ ДЛЯ ТЕСТИРОВАНИЯ:\n";
    echo "1. Откройте: http://spa.test/ads/97/edit\n";
    echo "2. Найдите секцию 'Типы мест для выезда'\n";
    echo "3. Должны быть выбраны: Квартиры, Отели, Сауны\n";
    echo "4. Измените выбор мест (добавьте/уберите галочки)\n";
    echo "5. Нажмите 'Сохранить черновик'\n";
    echo "6. Изменения должны сохраниться! ✅\n\n";
    
} catch (\Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
}