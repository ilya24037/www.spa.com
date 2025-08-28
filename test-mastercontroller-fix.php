<?php

echo "🔧 ТЕСТ ИСПРАВЛЕНИЯ MasterController\n\n";

// Симуляция исправленной логики MasterController
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=laravel_auth", "root", "Animatori2025!");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "📋 1. ИМИТИРУЕМ ЗАГРУЗКУ ПРОФИЛЯ МАСТЕРА:\n";
    
    // Находим master profile как в контроллере
    $masterStmt = $pdo->prepare("SELECT * FROM master_profiles WHERE id = 1");
    $masterStmt->execute();
    $profile = $masterStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        echo "❌ Master Profile не найден!\n";
        exit(1);
    }
    
    echo "✅ Master Profile найден:\n";
    echo "   ID: {$profile['id']}\n";
    echo "   User ID: {$profile['user_id']}\n";
    echo "   Display Name: {$profile['display_name']}\n";
    
    echo "\n📋 2. ИМИТИРУЕМ ЗАГРУЗКУ ФОТОГРАФИЙ ИЗ ОБЪЯВЛЕНИЙ:\n";
    
    // Ищем активное объявление пользователя мастера
    $adStmt = $pdo->prepare("SELECT * FROM ads WHERE user_id = ? AND status = 'active' LIMIT 1");
    $adStmt->execute([$profile['user_id']]);
    $ad = $adStmt->fetch(PDO::FETCH_ASSOC);
    
    $adPhotos = [];
    
    if ($ad) {
        echo "✅ Активное объявление найдено:\n";
        echo "   ID: {$ad['id']}\n";
        echo "   Title: {$ad['title']}\n";
        echo "   Has photos: " . ($ad['photos'] ? 'YES' : 'NO') . "\n";
        
        if ($ad['photos']) {
            echo "\n📸 ПРИМЕНЯЕМ ИСПРАВЛЕННОЕ ДЕКОДИРОВАНИЕ:\n";
            echo "   Raw JSON: " . substr($ad['photos'], 0, 100) . "...\n";
            
            // ИСПРАВЛЕННАЯ ЛОГИКА: двойное декодирование
            $photosArray = json_decode(json_decode($ad['photos'], true), true);
            
            echo "   First decode result: " . gettype(json_decode($ad['photos'], true)) . "\n";
            echo "   Second decode result: " . gettype($photosArray) . "\n";
            echo "   Is array: " . (is_array($photosArray) ? 'YES' : 'NO') . "\n";
            echo "   Array count: " . (is_array($photosArray) ? count($photosArray) : 'N/A') . "\n";
            
            if (is_array($photosArray) && count($photosArray) > 0) {
                $adPhotos = array_map(function($photoUrl) {
                    return [
                        'url' => $photoUrl,
                        'thumbnail_url' => $photoUrl,
                        'alt' => 'Фото мастера'
                    ];
                }, $photosArray);
                
                echo "\n✅ ФОТОГРАФИИ УСПЕШНО ОБРАБОТАНЫ:\n";
                echo "   Количество фото: " . count($adPhotos) . "\n";
                foreach ($adPhotos as $i => $photo) {
                    echo "   " . ($i + 1) . ". {$photo['url']}\n";
                    
                    // Проверяем физический файл
                    $fullPath = "C:/www.spa.com/public" . $photo['url'];
                    $exists = file_exists($fullPath);
                    echo "      Файл: " . ($exists ? "СУЩЕСТВУЕТ" : "НЕ СУЩЕСТВУЕТ") . "\n";
                }
            } else {
                echo "❌ Не удалось обработать фотографии\n";
            }
        }
    } else {
        echo "❌ Активное объявление НЕ найдено\n";
    }
    
    echo "\n📋 3. ИМИТИРУЕМ ВОЗВРАТ ДАННЫХ В Inertia:\n";
    
    // Создаем данные как в контроллере
    $masterArray = [
        'id' => $profile['id'],
        'display_name' => $profile['display_name'],
        'user_id' => $profile['user_id'],
        // ... другие поля мастера
    ];
    
    // Добавляем фотографии как в контроллере
    if (!empty($adPhotos)) {
        $masterArray['photos'] = $adPhotos;
    }
    
    $inertiaData = [
        'master' => $masterArray,
        'gallery' => !empty($adPhotos) ? $adPhotos : [],
        // ... другие данные
    ];
    
    echo "✅ Данные для Inertia подготовлены:\n";
    echo "   master.id: {$inertiaData['master']['id']}\n";
    echo "   master.photos: " . (isset($inertiaData['master']['photos']) ? count($inertiaData['master']['photos']) . ' фото' : 'НЕТ') . "\n";
    echo "   gallery: " . count($inertiaData['gallery']) . " элементов\n";
    
    echo "\n🎯 РЕЗУЛЬТАТ:\n";
    if (!empty($adPhotos)) {
        echo "✅ ИСПРАВЛЕНИЕ РАБОТАЕТ!\n";
        echo "   - Фотографии успешно загружены из объявлений\n";
        echo "   - JSON декодирование исправлено\n";
        echo "   - Данные готовы для Vue компонента\n";
        
        echo "\n📱 СЛЕДУЮЩИЙ ШАГ:\n";
        echo "Откройте http://spa.test/masters/klassiceskii-massaz-ot-anny-1 в браузере\n";
        echo "Фотографии теперь должны отображаться!\n";
    } else {
        echo "❌ ИСПРАВЛЕНИЕ НЕ РАБОТАЕТ\n";
        echo "Нужна дополнительная диагностика\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Ошибка БД: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Общая ошибка: " . $e->getMessage() . "\n";
}