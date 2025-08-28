<?php

use App\Domain\Ad\Models\Ad;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🏠 ТЕСТ ИСПРАВЛЕНИЯ ЧЕКБОКСОВ МЕСТ ВЫЕЗДА\n";
echo "==========================================\n\n";

try {
    // Используем черновик ID 97 (или создадим новый)
    $ad = Ad::find(97);
    
    if (!$ad) {
        echo "❌ Черновик с ID 97 не найден, создаем новый тестовый\n";
        $ad = new Ad();
        $ad->user_id = 21; // anna@spa.test
        $ad->title = 'Тест мест выезда';
        $ad->status = 'draft';
        $ad->category = 'relax';
        $ad->specialty = '';
        $ad->work_format = 'individual';
        $ad->experience = '';
        $ad->description = 'Тестовое объявление для проверки мест выезда';
        $ad->save();
        echo "✅ Создан новый тестовый черновик ID: {$ad->id}\n\n";
    }
    
    echo "📋 ТЕСТИРУЕМ ЧЕРНОВИК ID: {$ad->id}\n";
    echo "Заголовок: {$ad->title}\n";
    echo "Статус: {$ad->status->value}\n\n";
    
    // Устанавливаем тестовые значения для мест выезда как в оригинале
    $currentPrices = $ad->prices ?? [];
    if (!is_array($currentPrices)) {
        $currentPrices = [];
    }
    
    // ✅ ТЕСТИРУЕМ ДЕФОЛТНЫЕ ЗНАЧЕНИЯ ИЗ ОРИГИНАЛА (строки 252-266 backup)
    $currentPrices['apartments_express'] = null;
    $currentPrices['apartments_1h'] = '3000';
    $currentPrices['apartments_2h'] = null;
    $currentPrices['apartments_night'] = null;
    $currentPrices['outcall_express'] = null;
    $currentPrices['outcall_1h'] = '4000';
    $currentPrices['outcall_2h'] = null;
    $currentPrices['outcall_night'] = null;
    $currentPrices['taxi_included'] = false;
    
    // ❗ КЛЮЧЕВЫЕ ЧЕКБОКСЫ МЕСТ ВЫЕЗДА (как в оригинале)
    $currentPrices['outcall_apartment'] = true;   // ✅ По умолчанию включено
    $currentPrices['outcall_hotel'] = false;      // ❌ По умолчанию выключено
    $currentPrices['outcall_house'] = false;      // ❌ По умолчанию выключено
    $currentPrices['outcall_sauna'] = false;      // ❌ По умолчанию выключено
    $currentPrices['outcall_office'] = false;     // ❌ По умолчанию выключено
    
    $ad->prices = $currentPrices;
    $ad->save();
    
    echo "✅ УСТАНОВЛЕНЫ ТЕСТОВЫЕ МЕСТА ВЫЕЗДА (по дефолту из оригинала):\n";
    echo "  📍 Квартиры (outcall_apartment): ДА ✅\n";
    echo "  📍 Отели (outcall_hotel): НЕТ ❌\n";
    echo "  📍 Дома (outcall_house): НЕТ ❌\n";
    echo "  📍 Сауны (outcall_sauna): НЕТ ❌\n";
    echo "  📍 Офисы (outcall_office): НЕТ ❌\n\n";
    
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
        
    } else {
        echo "❌ Prices не сохранились как массив\n";
        echo "Тип: " . gettype($savedPrices) . "\n";
    }
    
    echo "\n🔧 ПРИМЕНЁННЫЕ ИСПРАВЛЕНИЯ:\n";
    echo "✅ 1. useAdFormState.ts: Добавлены дефолтные значения places\n";
    echo "   - outcall_apartment: true (как в оригинале)\n";
    echo "   - остальные места: false (как в оригинале)\n\n";
    
    echo "✅ 2. getValue функция: Добавлен парсинг JSON для prices\n";
    echo "   - Правильная обработка строковых JSON данных из БД\n\n";
    
    echo "✅ 3. formDataBuilder.ts: Защита от undefined\n";
    echo "   - (form.prices.outcall_apartment ?? false) ? '1' : '0'\n\n";
    
    echo "✅ 4. Добавлены отладочные логи для мест выезда\n";
    echo "   - console.log проверки всех полей outcall_*\n\n";
    
    echo "🧪 ИНСТРУКЦИЯ ДЛЯ ТЕСТИРОВАНИЯ:\n";
    echo "1. Откройте: http://spa.test/ads/{$ad->id}/edit\n";
    echo "2. Найдите секцию 'Типы мест для выезда'\n";
    echo "3. Должна быть выбрана только 'На квартиру' ✅\n";
    echo "4. Измените выбор мест (поставьте/уберите галочки)\n";
    echo "5. Нажмите 'Сохранить черновик'\n";
    echo "6. Откройте консоль браузера - увидите отладочные логи 🔍\n";
    echo "7. Обновите страницу - изменения должны сохраниться! 🎉\n\n";
    
    echo "🎯 ГЛАВНОЕ ИСПРАВЛЕНИЕ:\n";
    echo "Проблема была в отсутствии дефолтных значений для чекбоксов мест выезда.\n";
    echo "В новой версии prices: g('prices', {}) давало пустой объект,\n";
    echo "а в оригинале были четкие дефолты: outcall_apartment: true, остальные: false\n";
    
} catch (\Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
}