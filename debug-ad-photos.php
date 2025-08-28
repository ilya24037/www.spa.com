<?php

// Диагностика фотографий объявления
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=laravel_auth", "root", "Animatori2025!");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 ДИАГНОСТИКА ФОТОГРАФИЙ ОБЪЯВЛЕНИЯ ID 55\n\n";
    
    // Проверяем объявление ID 55
    $stmt = $pdo->prepare("SELECT * FROM ads WHERE id = 55");
    $stmt->execute();
    $ad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ad) {
        echo "✅ Объявление найдено:\n";
        echo "   ID: {$ad['id']}\n";
        echo "   Title: {$ad['title']}\n";
        echo "   User ID: {$ad['user_id']}\n";
        echo "   Status: {$ad['status']}\n";
        
        // Проверяем поля связанные с фото
        foreach($ad as $key => $value) {
            if (strpos($key, 'photo') !== false || strpos($key, 'image') !== false || strpos($key, 'media') !== false) {
                echo "   $key: " . ($value ?? 'NULL') . "\n";
            }
        }
        
        echo "\n📸 Поиск фотографий в связанных таблицах:\n";
        
        // Проверяем различные возможные таблицы фото
        $photoTables = [
            'photos' => ['ad_id', 'user_id'],
            'ad_photos' => ['ad_id'],
            'media' => ['model_id', 'user_id'],
            'ad_media' => ['ad_id'],
            'files' => ['ad_id', 'user_id']
        ];
        
        foreach($photoTables as $table => $columns) {
            try {
                foreach($columns as $column) {
                    $searchValue = ($column === 'user_id') ? $ad['user_id'] : $ad['id'];
                    
                    $photoStmt = $pdo->prepare("SELECT * FROM $table WHERE $column = ?");
                    $photoStmt->execute([$searchValue]);
                    $photos = $photoStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if ($photos) {
                        echo "\n   ✅ Найдены фото в таблице $table (по $column = $searchValue):\n";
                        foreach($photos as $photo) {
                            foreach($photo as $key => $value) {
                                if (strlen($value) > 100) $value = substr($value, 0, 100) . '...';
                                echo "     $key: " . ($value ?? 'NULL') . "\n";
                            }
                            echo "     ---\n";
                        }
                    }
                }
            } catch (Exception $e) {
                // Таблица может не существовать
            }
        }
        
        // Проверяем физические файлы в папках
        echo "\n📁 Проверка физических файлов:\n";
        $possiblePaths = [
            "C:/www.spa.com/public/images/ads/{$ad['id']}/",
            "C:/www.spa.com/public/images/users/{$ad['user_id']}/",
            "C:/www.spa.com/public/storage/ads/{$ad['id']}/",
            "C:/www.spa.com/storage/app/public/ads/{$ad['id']}/"
        ];
        
        foreach($possiblePaths as $path) {
            if (is_dir($path)) {
                $files = glob($path . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
                echo "   ✅ Папка $path: " . count($files) . " файлов\n";
                foreach(array_slice($files, 0, 3) as $file) {
                    echo "     - " . basename($file) . "\n";
                }
            }
        }
        
    } else {
        echo "❌ Объявление с ID 55 НЕ найдено!\n";
    }
    
    echo "\n💡 СЛЕДУЮЩИЕ ШАГИ:\n";
    echo "1. Найти где хранятся фотографии объявлений\n";
    echo "2. Проверить MasterController - какие данные он передает\n";
    echo "3. Исправить galleryImages в Masters/Show.vue\n";
    
} catch (PDOException $e) {
    echo "❌ Ошибка БД: " . $e->getMessage() . "\n";
}