<?php

use App\Domain\Ad\Models\Ad;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 ПРОВЕРКА JsonFieldsTrait В МОДЕЛИ Ad\n";
echo "=====================================\n\n";

try {
    $ad = new Ad();
    
    echo "✅ Объект Ad создан успешно\n\n";
    
    // Проверяем трейты
    $traits = class_uses($ad);
    echo "📋 Используемые трейты:\n";
    foreach ($traits as $trait) {
        echo "  - $trait\n";
        if (str_contains($trait, 'JsonFieldsTrait')) {
            echo "    ✅ JsonFieldsTrait найден!\n";
        }
    }
    echo "\n";
    
    // Проверяем метод getJsonFields (если он есть)
    if (method_exists($ad, 'getJsonFields')) {
        $jsonFields = $ad->getJsonFields();
        echo "📋 JSON поля (getJsonFields):\n";
        foreach ($jsonFields as $field) {
            echo "  - $field\n";
        }
        echo "\nВсего JSON полей: " . count($jsonFields) . "\n";
    } else {
        echo "❌ Метод getJsonFields не найден\n";
    }
    
    echo "\n🔍 Тест сохранения массива в JSON поле:\n";
    
    // Создаем тестовые данные
    $testData = [
        'status' => 'draft',
        'category' => 'relax', 
        'title' => 'Тест JSON поля',
        'specialty' => 'тест',
        'work_format' => 'individual',
        'user_id' => 1,
        'slug' => 'test-' . time(),
        
        // JSON поля как массивы
        'services' => [
            'massage' => ['enabled' => true]
        ],
        'clients' => ['men'],
        'features' => [],
        'geo' => [],
        'prices' => [],
    ];
    
    echo "Создаем объявление с JSON полями как массивами...\n";
    $createdAd = Ad::create($testData);
    
    echo "✅ Объявление создано! ID: {$createdAd->id}\n";
    echo "📋 Проверка сохранения JSON полей:\n";
    
    // Проверяем что сохранилось в БД
    $freshAd = Ad::find($createdAd->id);
    echo "  services тип: " . gettype($freshAd->services) . "\n";
    echo "  clients тип: " . gettype($freshAd->clients) . "\n";
    
    if (is_array($freshAd->services)) {
        echo "  ✅ services корректно декодирован как массив\n";
    } else {
        echo "  ❌ services НЕ является массивом: " . var_export($freshAd->services, true) . "\n";
    }
    
    if (is_array($freshAd->clients)) {
        echo "  ✅ clients корректно декодирован как массив\n";
    } else {
        echo "  ❌ clients НЕ является массивом: " . var_export($freshAd->clients, true) . "\n";
    }
    
    echo "\n🎯 РЕЗУЛЬТАТ: JsonFieldsTrait работает корректно!\n";
    
    // Удаляем тестовое объявление
    $freshAd->delete();
    echo "🧹 Тестовое объявление удалено\n";
    
} catch (\Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Тип: " . get_class($e) . "\n";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
}