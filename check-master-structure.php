<?php

// Проверка структуры таблицы master_profiles
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=laravel_auth", "root", "Animatori2025!");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 СТРУКТУРА ТАБЛИЦЫ MASTER_PROFILES\n\n";
    
    // Получаем структуру таблицы
    $stmt = $pdo->query("DESCRIBE master_profiles");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Колонки в таблице:\n";
    foreach($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']}) " . ($column['Null'] === 'YES' ? '[nullable]' : '') . "\n";
    }
    
    echo "\n📊 Данные (первые 3 записи):\n";
    $stmt = $pdo->query("SELECT * FROM master_profiles LIMIT 3");
    $masters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($masters as $i => $master) {
        echo "\n" . ($i + 1) . ". Мастер:\n";
        foreach($master as $key => $value) {
            $displayValue = $value ? (strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value) : 'NULL';
            echo "   $key: $displayValue\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}