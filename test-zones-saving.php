<?php

use App\Domain\Ad\Models\Ad;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🏘️ ТЕСТ СОХРАНЕНИЯ РАЙОНОВ ВЫЕЗДА (ZONES)\n";
echo "==========================================\n\n";

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
    
    // Проверяем geo данные
    $geo = $ad->geo;
    
    // Если geo строка - декодируем JSON
    if (is_string($geo)) {
        $geo = json_decode($geo, true) ?: [];
    } elseif (!is_array($geo)) {
        $geo = [];
    }
    
    echo "🌍 ТЕКУЩИЕ ДАННЫЕ В GEO:\n";
    echo str_repeat("-", 40) . "\n";
    
    if (is_array($geo)) {
        // Проверяем outcall режим
        $outcall = $geo['outcall'] ?? 'none';
        echo "📍 Режим выезда (outcall): {$outcall}\n";
        echo "  • none = Не выезжаю\n";
        echo "  • city = По всему городу\n";
        echo "  • zones = В выбранные зоны ✅\n\n";
        
        // Проверяем zones
        if (isset($geo['zones']) && is_array($geo['zones'])) {
            $zones = $geo['zones'];
            echo "🏘️ ВЫБРАННЫЕ РАЙОНЫ (zones):\n";
            if (count($zones) > 0) {
                foreach ($zones as $i => $zone) {
                    echo "  " . ($i + 1) . ". {$zone}\n";
                }
                echo "\n  ✅ Всего выбрано районов: " . count($zones) . "\n";
            } else {
                echo "  ❌ Районы не выбраны (пустой массив)\n";
            }
        } else {
            echo "🏘️ РАЙОНЫ: ❌ Поле zones отсутствует или не массив\n";
        }
        
        // Места выезда
        echo "\n🏠 МЕСТА ВЫЕЗДА:\n";
        $places = [
            'outcall_apartment' => 'На квартиру',
            'outcall_hotel' => 'В гостиницу',
            'outcall_office' => 'В офис'
        ];
        foreach ($places as $key => $name) {
            $value = $geo[$key] ?? false;
            echo "  • {$name}: " . ($value ? '✅' : '❌') . "\n";
        }
    } else {
        echo "❌ geo не является массивом\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🧪 ТЕСТ ИЗМЕНЕНИЯ РАЙОНОВ:\n\n";
    
    // Устанавливаем режим "В выбранные зоны" и добавляем районы
    $geo['outcall'] = 'zones';
    $geo['zones'] = [
        'Дзержинский район',
        'Индустриальный район',
        'Ленинский район'
    ];
    
    $ad->geo = $geo;
    $ad->save();
    
    echo "✅ Установлены тестовые данные:\n";
    echo "  • Режим выезда: В выбранные зоны\n";
    echo "  • Районы:\n";
    echo "    1. Дзержинский район\n";
    echo "    2. Индустриальный район\n";
    echo "    3. Ленинский район\n\n";
    
    // Проверяем сохранение
    $ad->refresh();
    $savedGeo = $ad->geo ?? [];
    
    if (is_array($savedGeo)) {
        $savedOutcall = $savedGeo['outcall'] ?? 'none';
        $savedZones = $savedGeo['zones'] ?? [];
        
        echo "📊 ПРОВЕРКА СОХРАНЕНИЯ:\n";
        echo "  • Режим выезда: {$savedOutcall} ";
        echo ($savedOutcall === 'zones' ? '✅' : '❌') . "\n";
        
        echo "  • Районы сохранены: ";
        if (is_array($savedZones) && count($savedZones) === 3) {
            echo "✅ (" . count($savedZones) . " района)\n";
            foreach ($savedZones as $i => $zone) {
                echo "    " . ($i + 1) . ". {$zone}\n";
            }
        } else {
            echo "❌\n";
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🔧 ПРИМЕНЕННОЕ ИСПРАВЛЕНИЕ:\n\n";
    
    echo "✅ Добавлен watcher для geoData.zones в GeoSection.vue\n";
    echo "  • При изменении zones через ZoneSelector\n";
    echo "  • Автоматически вызывается emitGeoData()\n";
    echo "  • Данные отправляются в form.geo\n\n";
    
    echo "📝 КОД ИСПРАВЛЕНИЯ:\n";
    echo "```javascript\n";
    echo "watch(() => geoData.zones, () => {\n";
    echo "  emitGeoData() // Отправляем изменения\n";
    echo "}, { deep: true })\n";
    echo "```\n\n";
    
    echo "🌐 URL ДЛЯ ТЕСТИРОВАНИЯ:\n";
    echo "  http://spa.test/ads/97/edit\n\n";
    
    echo "📋 ИНСТРУКЦИЯ:\n";
    echo "  1. Откройте страницу редактирования\n";
    echo "  2. В секции 'Геолокация' выберите 'В выбранные зоны'\n";
    echo "  3. Нажмите 'Выберите районы'\n";
    echo "  4. Отметьте нужные районы\n";
    echo "  5. Нажмите 'Сохранить черновик'\n";
    echo "  6. Районы должны сохраниться! ✅\n";
    
} catch (\Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
}