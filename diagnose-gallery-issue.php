<?php

echo "🔍 ДИАГНОСТИКА ПРОБЛЕМЫ С ГАЛЕРЕЕЙ\n\n";

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=laravel_auth", "root", "Animatori2025!");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "📋 1. ПРОВЕРЯЕМ ДАННЫЕ МАСТЕРА ID 1:\n";
    
    $masterStmt = $pdo->prepare("SELECT * FROM master_profiles WHERE id = 1");
    $masterStmt->execute();
    $master = $masterStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($master) {
        echo "✅ Master найден:\n";
        echo "   ID: {$master['id']}\n";
        echo "   User ID: {$master['user_id']}\n";
        echo "   Display Name: {$master['display_name']}\n";
        echo "   Slug: {$master['slug']}\n";
        
        // Проверяем все активные объявления этого пользователя
        echo "\n📋 2. ВСЕ АКТИВНЫЕ ОБЪЯВЛЕНИЯ USER ID {$master['user_id']}:\n";
        
        $adStmt = $pdo->prepare("SELECT id, title, photos FROM ads WHERE user_id = ? AND status = 'active' ORDER BY id");
        $adStmt->execute([$master['user_id']]);
        $ads = $adStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($ads as $i => $ad) {
            echo "\n   📝 Объявление " . ($i + 1) . ":\n";
            echo "     ID: {$ad['id']}\n";
            echo "     Title: {$ad['title']}\n";
            echo "     Has photos: " . ($ad['photos'] ? 'YES' : 'NO') . "\n";
            
            if ($ad['photos']) {
                // Применяем исправленное декодирование
                $photosArray = json_decode(json_decode($ad['photos'], true), true);
                
                if (is_array($photosArray)) {
                    echo "     Photos count: " . count($photosArray) . "\n";
                    foreach ($photosArray as $j => $photoUrl) {
                        echo "       " . ($j + 1) . ". $photoUrl\n";
                    }
                    
                    // Если это первое объявление с фотографиями, проверяем файлы
                    if ($i === 0 && count($photosArray) > 0) {
                        echo "\n   📸 ПРОВЕРКА ФИЗИЧЕСКИХ ФАЙЛОВ (первое объявление):\n";
                        foreach ($photosArray as $j => $photoUrl) {
                            $fullPath = "C:/www.spa.com/public" . $photoUrl;
                            $exists = file_exists($fullPath);
                            $size = $exists ? filesize($fullPath) : 0;
                            
                            echo "     " . ($j + 1) . ". " . basename($photoUrl) . " - ";
                            echo $exists ? "✅ EXISTS ($size bytes)" : "❌ MISSING";
                            echo "\n";
                        }
                    }
                } else {
                    echo "     ❌ JSON decode failed\n";
                }
            }
        }
        
        echo "\n📋 3. ПРОВЕРЯЕМ Vue КОМПОНЕНТ:\n";
        $vueFile = "C:/www.spa.com/resources/js/Pages/Masters/Show.vue";
        if (file_exists($vueFile)) {
            $vueContent = file_get_contents($vueFile);
            
            // Проверяем наличие PhotoGallery компонента
            if (strpos($vueContent, 'PhotoGallery') !== false) {
                echo "✅ PhotoGallery компонент найден в Vue файле\n";
            } else {
                echo "❌ PhotoGallery компонент НЕ найден в Vue файле\n";
            }
            
            // Проверяем галерею
            if (strpos($vueContent, 'galleryImages') !== false) {
                echo "✅ galleryImages computed найден\n";
            } else {
                echo "❌ galleryImages computed НЕ найден\n";
            }
            
            // Проверяем ImageGalleryModal
            if (strpos($vueContent, 'ImageGalleryModal') !== false) {
                echo "✅ ImageGalleryModal компонент найден\n";
            } else {
                echo "❌ ImageGalleryModal компонент НЕ найден\n";
            }
        }
        
        echo "\n📋 4. ПРОВЕРЯЕМ МАРШРУТ:\n";
        $routesFile = "C:/www.spa.com/routes/web.php";
        if (file_exists($routesFile)) {
            $routesContent = file_get_contents($routesFile);
            if (strpos($routesContent, 'MasterController@show') !== false || strpos($routesContent, 'MasterController::class') !== false) {
                echo "✅ MasterController маршрут найден\n";
            } else {
                echo "❌ MasterController маршрут НЕ найден\n";
            }
        }
        
        echo "\n🎯 ВОЗМОЖНЫЕ ПРИЧИНЫ ПРОБЛЕМЫ:\n";
        
        if (count($ads) === 0) {
            echo "❌ 1. НЕТ активных объявлений с фотографиями\n";
        } else {
            echo "✅ 1. Есть активные объявления с фотографиями\n";
        }
        
        echo "\n❓ 2. MasterController может не вызываться (проверьте browser network tab)\n";
        echo "❓ 3. Vue компонент может не получать данные\n";
        echo "❓ 4. Галерея может быть скрыта CSS\n";
        echo "❓ 5. Кэш браузера показывает старые данные\n";
        
        echo "\n📱 СЛЕДУЮЩИЕ ШАГИ:\n";
        echo "1. Откройте Developer Tools в браузере (F12)\n";
        echo "2. Перейдите на Network tab\n";
        echo "3. Обновите страницу (Ctrl+F5)\n";
        echo "4. Найдите запрос к /masters/klassiceskii-massaz-ot-anny-1\n";
        echo "5. Проверьте Response - есть ли там поле photos?\n";
        echo "6. Проверьте Console на ошибки JavaScript\n";
        
    } else {
        echo "❌ Master с ID 1 НЕ найден!\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Ошибка БД: " . $e->getMessage() . "\n";
}