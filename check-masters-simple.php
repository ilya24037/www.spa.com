<?php

// Простая проверка мастеров через PDO
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=laravel_auth", "root", "Animatori2025!");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 ПРОВЕРКА МАСТЕРОВ В БД\n\n";
    
    // Проверяем таблицу master_profiles
    $stmt = $pdo->query("SHOW TABLES LIKE 'master_profiles'");
    if ($stmt->rowCount() === 0) {
        echo "❌ Таблица master_profiles не существует\n";
        echo "Возможно, нужно создать миграцию или использовать другую таблицу\n\n";
        
        // Проверяем другие таблицы
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "📋 Доступные таблицы:\n";
        foreach($tables as $table) {
            echo "  - $table\n";
        }
        exit;
    }
    
    // Считаем мастеров
    $count = $pdo->query("SELECT COUNT(*) FROM master_profiles")->fetchColumn();
    echo "📊 Всего мастеров: $count\n\n";
    
    if ($count > 0) {
        // Выбираем первых 3 мастеров
        $stmt = $pdo->query("SELECT id, name, slug, status, city, rating FROM master_profiles LIMIT 3");
        $masters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📝 Первые мастера:\n";
        foreach($masters as $master) {
            echo "  ID: {$master['id']}, Name: {$master['name']}, Slug: {$master['slug']}\n";
            echo "  Status: {$master['status']}, City: {$master['city']}, Rating: {$master['rating']}\n";
            echo "  URL: http://spa.test/masters/{$master['slug']}-{$master['id']}\n\n";
        }
    } else {
        echo "❌ Мастеров в БД нет - нужно создать тестовые данные\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Ошибка подключения к БД: " . $e->getMessage() . "\n";
    echo "Возможно, нужно проверить настройки подключения в .env\n";
}