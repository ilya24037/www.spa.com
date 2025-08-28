<?php

// Проверка фотографий мастеров
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=laravel_auth", "root", "Animatori2025!");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 ПРОВЕРКА ФОТОГРАФИЙ МАСТЕРОВ\n\n";
    
    // Проверяем структуру master_profiles
    echo "📋 Поля связанные с фото в master_profiles:\n";
    $stmt = $pdo->query("DESCRIBE master_profiles");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($columns as $column) {
        if (strpos($column['Field'], 'photo') !== false || 
            strpos($column['Field'], 'avatar') !== false || 
            strpos($column['Field'], 'image') !== false ||
            strpos($column['Field'], 'folder') !== false) {
            echo "  - {$column['Field']} ({$column['Type']}) " . ($column['Null'] === 'YES' ? '[nullable]' : '') . "\n";
        }
    }
    
    echo "\n📸 Данные о фотографиях мастеров:\n";
    $stmt = $pdo->query("SELECT id, display_name, avatar, folder_name FROM master_profiles ORDER BY id");
    $masters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($masters as $master) {
        echo "\n🧑 Master ID {$master['id']}: {$master['display_name']}\n";
        echo "   Avatar: " . ($master['avatar'] ?? 'NULL') . "\n";
        echo "   Folder: " . ($master['folder_name'] ?? 'NULL') . "\n";
        
        // Проверяем есть ли связанные фото в других таблицах
        $photoTables = ['photos', 'master_photos', 'media', 'master_media'];
        foreach($photoTables as $table) {
            try {
                $photoStmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE master_id = ? OR user_id = (SELECT user_id FROM master_profiles WHERE id = ?)");
                $photoStmt->execute([$master['id'], $master['id']]);
                $count = $photoStmt->fetchColumn();
                if ($count > 0) {
                    echo "   $table: $count фото\n";
                }
            } catch (Exception $e) {
                // Таблица может не существовать
            }
        }
        
        // Проверяем физические папки если указано folder_name
        if ($master['folder_name']) {
            $folderPath = "C:/www.spa.com/public/images/masters/" . $master['folder_name'];
            if (is_dir($folderPath)) {
                $files = glob($folderPath . "/*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
                echo "   Физические файлы: " . count($files) . " в папке $folderPath\n";
                if ($files) {
                    foreach(array_slice($files, 0, 3) as $file) {
                        echo "     - " . basename($file) . "\n";
                    }
                }
            } else {
                echo "   Папка $folderPath НЕ существует\n";
            }
        }
    }
    
    echo "\n💡 РЕКОМЕНДАЦИИ:\n";
    echo "1. Добавить тестовые фотографии в public/images/masters/\n";
    echo "2. Обновить Masters/Show.vue для загрузки реальных данных из БД\n";
    echo "3. Создать placeholder изображения для мастеров без фото\n";
    
} catch (PDOException $e) {
    echo "❌ Ошибка БД: " . $e->getMessage() . "\n";
}