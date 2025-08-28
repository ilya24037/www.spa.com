<?php

echo "🔍 ПРОВЕРКА ФОТОГРАФИЙ ОБЪЯВЛЕНИЯ ID 55\n\n";

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=laravel_auth", "root", "Animatori2025!");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM ads WHERE id = 55");
    $stmt->execute();
    $ad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ad) {
        echo "✅ Объявление найдено:\n";
        echo "   ID: {$ad['id']}\n";
        echo "   Title: {$ad['title']}\n";
        echo "   Status: {$ad['status']}\n";
        echo "   User ID: {$ad['user_id']}\n";
        echo "\n📸 Анализ поля photos:\n";
        echo "   Raw value: " . ($ad['photos'] ?? 'NULL') . "\n";
        echo "   Type: " . gettype($ad['photos']) . "\n";
        echo "   Length: " . strlen($ad['photos'] ?? '') . "\n";
        
        if (!empty($ad['photos'])) {
            echo "   Is JSON: " . (json_decode($ad['photos']) !== null ? 'YES' : 'NO') . "\n";
            
            $decoded = json_decode($ad['photos'], true);
            if (is_array($decoded)) {
                echo "   ✅ JSON массив с " . count($decoded) . " элементами:\n";
                foreach ($decoded as $i => $photo) {
                    echo "     " . ($i + 1) . ". $photo\n";
                    
                    // Проверяем физический файл
                    $fullPath = "C:/www.spa.com/public" . $photo;
                    $exists = file_exists($fullPath);
                    echo "        Файл " . ($exists ? "СУЩЕСТВУЕТ" : "НЕ СУЩЕСТВУЕТ") . "\n";
                }
            } else {
                echo "   ❌ Не удалось декодировать JSON\n";
                echo "   JSON error: " . json_last_error_msg() . "\n";
            }
        } else {
            echo "   ❌ Поле photos пустое или NULL\n";
        }
        
        // Проверим все поля, которые могут содержать фото
        echo "\n📋 Проверка всех полей на наличие фото:\n";
        foreach ($ad as $field => $value) {
            if (strpos($field, 'photo') !== false || 
                strpos($field, 'image') !== false ||
                strpos($field, 'media') !== false ||
                strpos($field, 'gallery') !== false) {
                echo "   $field: " . ($value ?? 'NULL') . "\n";
            }
        }
        
    } else {
        echo "❌ Объявление ID 55 НЕ найдено!\n";
        
        // Найдем любые объявления с фотографиями
        echo "\n🔍 Поиск объявлений с фотографиями:\n";
        $stmt = $pdo->query("SELECT id, title, photos FROM ads WHERE photos IS NOT NULL AND photos != '' AND photos != '[]' LIMIT 5");
        $adsWithPhotos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($adsWithPhotos) > 0) {
            echo "Найдено " . count($adsWithPhotos) . " объявлений с фотографиями:\n";
            foreach ($adsWithPhotos as $ad) {
                echo "   ID {$ad['id']}: {$ad['title']}\n";
                $photos = json_decode($ad['photos'], true);
                echo "     Фото: " . (is_array($photos) ? count($photos) . " шт" : 'неверный формат') . "\n";
            }
        } else {
            echo "❌ НЕ найдено объявлений с фотографиями!\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Ошибка БД: " . $e->getMessage() . "\n";
}