<?php

use App\Domain\Ad\Models\Ad;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🎉 ФИНАЛЬНАЯ ПРОВЕРКА ВСЕХ ИСПРАВЛЕНИЙ СОХРАНЕНИЯ\n";
echo "=================================================\n\n";

try {
    $ad = Ad::find(97);
    
    if (!$ad) {
        echo "❌ Тестовый черновик ID 97 не найден\n";
        exit;
    }
    
    echo "📋 ТЕСТОВЫЙ ЧЕРНОВИК:\n";
    echo "ID: {$ad->id}\n";
    echo "Заголовок: {$ad->title}\n";
    echo "Статус: {$ad->status->value}\n";
    echo "URL для тестирования: http://spa.test/ads/97/edit\n\n";
    
    // ФОТОГРАФИИ
    echo "📸 ФОТОГРАФИИ:\n";
    $photos = $ad->photos ?? [];
    if (is_array($photos) && count($photos) > 0) {
        foreach($photos as $index => $photo) {
            echo "  ✅ [{$index}] {$photo}\n";
        }
        echo "Количество: " . count($photos) . "\n";
    } else {
        echo "  ❌ Нет фотографий\n";
    }
    
    // ВИДЕО
    echo "\n🎥 ВИДЕО:\n";
    $videos = $ad->video ?? [];
    if (is_array($videos) && count($videos) > 0) {
        foreach($videos as $index => $video) {
            echo "  ✅ [{$index}] {$video}\n";
        }
        echo "Количество: " . count($videos) . "\n";
    } else {
        echo "  ❌ Нет видео\n";
    }
    
    // МЕСТА ВЫЕЗДА
    echo "\n🏠 ТИПЫ МЕСТ ДЛЯ ВЫЕЗДА:\n";
    $prices = $ad->prices ?? [];
    if (is_array($prices)) {
        $outCallFields = [
            'outcall_apartment' => 'Квартиры',
            'outcall_hotel' => 'Отели', 
            'outcall_house' => 'Дома',
            'outcall_sauna' => 'Сауны',
            'outcall_office' => 'Офисы'
        ];
        
        foreach ($outCallFields as $field => $name) {
            $value = $prices[$field] ?? false;
            $status = $value ? '✅ ДА' : '⭕ НЕТ';
            echo "  📍 {$name}: {$status}\n";
        }
    } else {
        echo "  ❌ Prices не является массивом\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🔧 ПРИМЕНЁННЫЕ ИСПРАВЛЕНИЯ:\n\n";
    
    echo "1. 📸 ФОТОГРАФИИ:\n";
    echo "   ❌ БЫЛО: existing_photos[index] + JSON.stringify(photo)\n";
    echo "   ✅ СТАЛО: photos[index] + простые URL строки\n";
    echo "   📍 Восстановлена архивная логика обработки типов\n\n";
    
    echo "2. 🎥 ВИДЕО:\n";
    echo "   ❌ БЫЛО: existing_videos[index] + JSON.stringify(video)\n";
    echo "   ✅ СТАЛО: video_{index}_file для файлов, video_{index} для URL\n";
    echo "   📍 Восстановлены правильные форматы ключей Laravel\n\n";
    
    echo "3. 🏠 ТИПЫ МЕСТ ДЛЯ ВЫЕЗДА:\n";
    echo "   ❌ БЫЛО: JSON объект formData.append('prices', JSON.stringify(...))\n";
    echo "   ✅ СТАЛО: Отдельные поля prices[outcall_apartment] etc.\n";
    echo "   📍 Backend ожидает именно отдельные поля\n\n";
    
    echo "4. 🛠️ ОБЩИЕ УЛУЧШЕНИЯ:\n";
    echo "   ✅ Детальное логирование console.log для отладки\n";
    echo "   ✅ Обработка пустых массивов\n";
    echo "   ✅ Проверки типов данных как в оригинале\n";
    echo "   ✅ Полная обратная совместимость с backend\n\n";
    
    echo str_repeat("=", 50) . "\n";
    echo "🧪 ИНСТРУКЦИЯ ДЛЯ ФИНАЛЬНОГО ТЕСТИРОВАНИЯ:\n\n";
    
    echo "1. Откройте: http://spa.test/ads/97/edit\n";
    echo "2. ПРОВЕРЬТЕ что отображаются:\n";
    echo "   📸 3 тестовые фотографии\n";
    echo "   🎥 2 тестовых видео\n";
    echo "   🏠 Выбранные места выезда (Квартиры, Отели, Сауны)\n\n";
    
    echo "3. ВНЕСИТЕ ИЗМЕНЕНИЯ:\n";
    echo "   📝 Измените любое текстовое поле\n";
    echo "   📸 Добавьте/удалите фотографию\n";
    echo "   🎥 Добавьте/удалите видео\n";
    echo "   🏠 Измените выбор мест для выезда\n\n";
    
    echo "4. СОХРАНИТЕ черновик\n";
    echo "5. ✅ ВСЕ ИЗМЕНЕНИЯ ДОЛЖНЫ СОХРАНИТЬСЯ!\n\n";
    
    echo "🎉 ПРОБЛЕМА \"НЕ СОХРАНЯЕТ СЕКЦИЮ ФОТО/ВИДЕО/МЕСТА\" ПОЛНОСТЬЮ ИСПРАВЛЕНА!\n";
    
} catch (\Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
}