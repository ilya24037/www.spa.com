<?php

// Диагностика связи между ads и master_profiles
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=laravel_auth", "root", "Animatori2025!");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 ДИАГНОСТИКА: ADS vs MASTER_PROFILES\n\n";
    
    // Проверяем ads таблицу
    echo "📋 Ads таблица:\n";
    $adsStmt = $pdo->query("SELECT id, title, user_id, status FROM ads ORDER BY id LIMIT 10");
    $ads = $adsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($ads as $ad) {
        echo "   Ad ID: {$ad['id']}, Title: {$ad['title']}, User ID: {$ad['user_id']}, Status: {$ad['status']}\n";
    }
    
    echo "\n📋 Master Profiles таблица:\n";
    $mastersStmt = $pdo->query("SELECT id, display_name, user_id, slug FROM master_profiles ORDER BY id");
    $masters = $mastersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($masters as $master) {
        echo "   Master ID: {$master['id']}, Name: {$master['display_name']}, User ID: {$master['user_id']}, Slug: {$master['slug']}\n";
    }
    
    echo "\n🔗 ПРОБЛЕМА НАЙДЕНА!\n";
    echo "MasterController использует ads таблицу вместо master_profiles!\n";
    echo "Поэтому ID не совпадают - это ID объявлений, а не мастеров.\n\n";
    
    echo "💡 РЕШЕНИЕ:\n";
    echo "1. MasterController должен работать с master_profiles\n";
    echo "2. Или нужен маппинг между ads и masters\n";
    echo "3. Или использовать правильный контроллер для мастеров\n";
    
    // Проверим есть ли связь user_id
    echo "\n🔗 Связь через user_id:\n";
    foreach($ads as $ad) {
        $masterStmt = $pdo->prepare("SELECT id, display_name, slug FROM master_profiles WHERE user_id = ?");
        $masterStmt->execute([$ad['user_id']]);
        $relatedMaster = $masterStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($relatedMaster) {
            echo "   Ad {$ad['id']} -> Master {$relatedMaster['id']} (User {$ad['user_id']})\n";
            echo "     Ad: {$ad['title']}\n";
            echo "     Master: {$relatedMaster['display_name']}\n";
            echo "     Правильный URL: http://spa.test/masters/{$relatedMaster['slug']}-{$relatedMaster['id']}\n\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Ошибка БД: " . $e->getMessage() . "\n";
}