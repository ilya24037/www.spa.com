<?php

echo "🔍 ФИНАЛЬНАЯ ДИАГНОСТИКА ПРОБЛЕМЫ С ФОТОГРАФИЯМИ МАСТЕРА\n\n";

try {
    // Подключение к БД
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=laravel_auth", "root", "Animatori2025!");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "📋 1. ПРОВЕРЯЕМ MASTER PROFILE:\n";
    $stmt = $pdo->prepare("SELECT * FROM master_profiles WHERE user_id = 1 LIMIT 1");
    $stmt->execute();
    $master = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($master) {
        echo "✅ Master Profile найден:\n";
        echo "   ID: {$master['id']}\n";
        echo "   Display Name: {$master['display_name']}\n"; 
        echo "   User ID: {$master['user_id']}\n";
        echo "   Slug: {$master['slug']}\n";
        echo "   Avatar: " . ($master['avatar'] ?? 'NULL') . "\n";
        
        $expectedUrl = "klassiceskii-massaz-ot-anny-{$master['id']}";
        echo "   Expected URL: /masters/$expectedUrl\n";
        
    } else {
        echo "❌ Master Profile с User ID 1 НЕ найден!\n";
        exit(1);
    }
    
    echo "\n📋 2. ПРОВЕРЯЕМ АКТИВНЫЕ ОБЪЯВЛЕНИЯ:\n";
    $stmt = $pdo->prepare("SELECT id, title, photos FROM ads WHERE user_id = 1 AND status = 'active'");
    $stmt->execute();
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Найдено активных объявлений: " . count($ads) . "\n";
    
    $photoFound = false;
    foreach ($ads as $ad) {
        echo "\n   📝 Ad ID {$ad['id']}: {$ad['title']}\n";
        if ($ad['photos']) {
            $photos = json_decode($ad['photos'], true);
            if (is_array($photos) && count($photos) > 0) {
                echo "   ✅ Фотографий: " . count($photos) . "\n";
                echo "   📸 Первое фото: {$photos[0]}\n";
                
                // Проверяем физический файл
                $photoPath = "C:/www.spa.com/public" . $photos[0];
                if (file_exists($photoPath)) {
                    echo "   ✅ Файл существует: " . basename($photoPath) . "\n";
                    $photoFound = true;
                } else {
                    echo "   ❌ Файл НЕ существует: $photoPath\n";
                }
                break; // Берем первое объявление с фото
            }
        }
    }
    
    echo "\n📋 3. ПРОВЕРЯЕМ ЛОГИ LARAVEL:\n";
    $logPath = "C:/www.spa.com/storage/logs/laravel.log";
    if (file_exists($logPath)) {
        $logContent = file_get_contents($logPath);
        $todayDate = date('Y-m-d');
        
        // Ищем сегодняшние записи MasterController
        if (strpos($logContent, 'MasterController') !== false) {
            echo "✅ Записи MasterController найдены в логах\n";
            
            // Ищем конкретно отладочные записи
            if (strpos($logContent, '🎯 MasterController::show вызван') !== false) {
                echo "✅ MasterController::show ВЫЗЫВАЛСЯ\n";
            } else {
                echo "❌ MasterController::show НЕ вызывался\n";
            }
        } else {
            echo "❌ Записи MasterController НЕ найдены в логах\n";
        }
    } else {
        echo "❌ Лог файл не найден\n";
    }
    
    echo "\n📋 4. ПРОВЕРЯЕМ МАРШРУТЫ:\n";
    $routesPath = "C:/www.spa.com/routes/web.php";
    if (file_exists($routesPath)) {
        $routesContent = file_get_contents($routesPath);
        if (strpos($routesContent, 'masters.show') !== false) {
            echo "✅ Маршрут masters.show найден в web.php\n";
            
            // Извлекаем строку маршрута
            preg_match('/Route::get.*masters.*->name\([\'"]masters\.show[\'"].*/', $routesContent, $matches);
            if (!empty($matches)) {
                echo "   Маршрут: " . trim($matches[0]) . "\n";
            }
        } else {
            echo "❌ Маршрут masters.show НЕ найден в web.php\n";
        }
    }
    
    echo "\n🎯 ДИАГНОЗ:\n";
    
    if (!$photoFound) {
        echo "❌ ПРОБЛЕМА: Нет доступных фотографий в объявлениях\n";
        echo "РЕШЕНИЕ: Нужно загрузить фотографии в активные объявления\n";
    } elseif (!strpos(file_get_contents($logPath), 'MasterController')) {
        echo "❌ ПРОБЛЕМА: MasterController не вызывается\n";
        echo "РЕШЕНИЕ: Проблема с маршрутизацией или кэшем\n";
        echo "КОМАНДЫ:\n";
        echo "  php artisan route:cache\n";
        echo "  php artisan config:cache\n";
        echo "  php artisan view:cache\n";
    } else {
        echo "❓ ПРОБЛЕМА: Неясна, требуется дополнительная диагностика\n";
        echo "ДЕЙСТВИЕ: Проверьте браузер как указано в debug-instructions.md\n";
    }
    
    echo "\n📋 URL ДЛЯ ТЕСТИРОВАНИЯ:\n";
    echo "http://spa.test/masters/klassiceskii-massaz-ot-anny-{$master['id']}\n";
    
} catch (PDOException $e) {
    echo "❌ Ошибка БД: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Общая ошибка: " . $e->getMessage() . "\n";
}