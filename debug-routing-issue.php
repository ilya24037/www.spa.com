<?php

// Диагностика проблемы роутинга
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=laravel_auth", "root", "Animatori2025!");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 ДИАГНОСТИКА ПРОБЛЕМЫ РОУТИНГА\n\n";
    
    // Проверяем мастера с ID 52
    $stmt = $pdo->prepare("SELECT * FROM master_profiles WHERE id = 52");
    $stmt->execute();
    $master52 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($master52) {
        echo "✅ Мастер ID 52 найден:\n";
        echo "   ID: {$master52['id']}\n";
        echo "   Display Name: " . ($master52['display_name'] ?? 'NULL') . "\n";
        echo "   Slug: " . ($master52['slug'] ?? 'NULL') . "\n";
        
        $correctUrl = "http://spa.test/masters/" . ($master52['slug'] ?? 'master-52') . "-" . $master52['id'];
        echo "   Правильный URL: $correctUrl\n\n";
    } else {
        echo "❌ Мастер с ID 52 НЕ найден!\n\n";
    }
    
    // Проверяем все мастера
    echo "📋 Все мастера в БД:\n";
    $stmt = $pdo->query("SELECT id, display_name, slug FROM master_profiles ORDER BY id");
    $allMasters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($allMasters as $master) {
        $displayName = $master['display_name'] ?? 'NULL';
        $slug = $master['slug'] ?? 'NULL';
        echo "   ID: {$master['id']}, Display: $displayName, Slug: $slug\n";
        
        if ($slug && $slug !== 'NULL') {
            echo "      URL: http://spa.test/masters/{$slug}-{$master['id']}\n";
        } else {
            echo "      URL: http://spa.test/masters/master-{$master['id']}\n";
        }
        echo "\n";
    }
    
    // Проверяем может ли быть проблема с данными, которые Frontend получает
    echo "🔧 ВОЗМОЖНЫЕ ПРИЧИНЫ ОШИБКИ:\n";
    echo "1. Frontend получает данные в неправильном формате\n";
    echo "2. В MasterCard.vue передается неправильный slug\n";
    echo "3. API возвращает мастера с некорректными данными\n";
    echo "4. Есть мастера без slug или с неправильным slug\n\n";
    
    echo "💡 РЕШЕНИЕ: Нужно проверить какие данные приходят в MasterCard.vue\n";
    echo "Добавить console.log в goToProfile() метод для отладки\n";
    
} catch (PDOException $e) {
    echo "❌ Ошибка БД: " . $e->getMessage() . "\n";
}