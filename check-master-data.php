<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ПРОВЕРКА ДАННЫХ МАСТЕРА НА KRAKOZYABRY ===\n";

try {
    // Получаем мастера с ID 3 (из URL ошибки)
    $master = \App\Domain\Master\Models\MasterProfile::find(3);
    
    if (!$master) {
        echo "❌ Мастер с ID 3 не найден!\n";
        exit;
    }
    
    echo "✅ Мастер найден: {$master->display_name}\n";
    echo "---\n";
    
    // Проверяем все поля на krakozyabry
    $fields = [
        'display_name' => $master->display_name,
        'specialty' => $master->specialty,
        'description' => $master->description,
        'bio' => $master->bio,
        'education' => $master->education,
        'certificates' => $master->certificates,
        'features' => $master->features,
        'meta_title' => $master->meta_title,
        'meta_description' => $master->meta_description,
        'district' => $master->district,
        'metro_station' => $master->metro_station,
        'city' => $master->city,
        'salon_address' => $master->salon_address
    ];
    
    $hasKrakozyabry = false;
    
    foreach ($fields as $fieldName => $value) {
        if ($value === null) continue;
        
        // Проверяем на krakozyabry (типичные паттерны)
        $patterns = [
            '/Рџ/', '/РџРѕ/', '/РџРѕРєР°/', '/РѕС‚Р·С‹РІРѕРІ/', '/РњР°СЃС‚РµСЂ/',
            '/РЅР° РѕСЃРЅРѕРІРµ/', '/РЅРµС‚ РѕС‚Р·С‹РІРѕРІ/', '/РЎС‚Р°РЅСЊС‚Рµ/',
            '/РїРµСЂРІС‹Рј/', '/РѕСЃС‚Р°РІРёС‚/', '/Рѕ СЂР°Р±РѕС‚Рµ/', '/РјР°СЃС‚РµСЂР°/'
        ];
        
        $isKrakozyabry = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $isKrakozyabry = true;
                break;
            }
        }
        
        if ($isKrakozyabry) {
            echo "❌ KRAKOZYABRY в поле {$fieldName}: {$value}\n";
            $hasKrakozyabry = true;
        } else {
            echo "✅ Поле {$fieldName}: {$value}\n";
        }
    }
    
    // Проверяем связанные данные
    echo "---\n";
    echo "Проверяем связанные данные...\n";
    
    // Услуги
    if ($master->services) {
        foreach ($master->services as $service) {
            echo "Услуга: {$service->name}\n";
            if ($service->description) {
                echo "  Описание: {$service->description}\n";
            }
        }
    }
    
    // Отзывы
    if ($master->reviews) {
        foreach ($master->reviews as $review) {
            echo "Отзыв: {$review->comment}\n";
        }
    }
    
    if ($hasKrakozyabry) {
        echo "\n🚨 ОБНАРУЖЕНЫ KRAKOZYABRY В ДАННЫХ МАСТЕРА!\n";
        echo "Это вызывает ошибку JSON encoding!\n";
    } else {
        echo "\n✅ Krakozyabry в данных мастера НЕ НАЙДЕНЫ\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "=== КОНЕЦ ПРОВЕРКИ ===\n";
