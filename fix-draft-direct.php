<?php
// Прямое подключение к БД для исправления проблемы
$host = 'localhost';
$db = 'laravel_auth';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Подключение к БД успешно\n\n";
    
    // Увеличиваем буферы сортировки
    try {
        $pdo->exec("SET SESSION sort_buffer_size = 2097152"); // 2MB
        $pdo->exec("SET SESSION read_rnd_buffer_size = 2097152"); // 2MB
        echo "✅ Буферы увеличены\n\n";
    } catch (Exception $e) {
        echo "⚠️ Не удалось увеличить буферы: " . $e->getMessage() . "\n\n";
    }
    
    // Получаем проблемные черновики
    echo "🔍 Поиск черновиков с большими данными в photos...\n";
    
    $stmt = $pdo->prepare("
        SELECT id, user_id, title, 
               LENGTH(photos) as photos_length,
               LENGTH(services) as services_length,
               updated_at
        FROM ads 
        WHERE status = 'draft'
        ORDER BY id DESC
        LIMIT 10
    ");
    $stmt->execute();
    $drafts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Найдено черновиков: " . count($drafts) . "\n\n";
    
    foreach ($drafts as $draft) {
        echo "📝 ID: {$draft['id']}\n";
        echo "   User: {$draft['user_id']}\n";
        echo "   Title: {$draft['title']}\n";
        echo "   Photos size: {$draft['photos_length']} bytes\n";
        echo "   Services size: {$draft['services_length']} bytes\n";
        
        // Если photos слишком большой, очищаем
        if ($draft['photos_length'] > 10000) {
            echo "   ⚠️ Photos слишком большой! Очищаем...\n";
            $updateStmt = $pdo->prepare("UPDATE ads SET photos = '[]' WHERE id = ?");
            $updateStmt->execute([$draft['id']]);
            echo "   ✅ Очищено\n";
        }
        
        // Если services слишком большой, очищаем
        if ($draft['services_length'] > 10000) {
            echo "   ⚠️ Services слишком большой! Очищаем...\n";
            $updateStmt = $pdo->prepare("UPDATE ads SET services = '{}' WHERE id = ?");
            $updateStmt->execute([$draft['id']]);
            echo "   ✅ Очищено\n";
        }
        
        echo "\n";
    }
    
    // Оптимизируем таблицу
    echo "🔧 Оптимизация таблицы ads...\n";
    $pdo->exec("OPTIMIZE TABLE ads");
    echo "✅ Таблица оптимизирована\n\n";
    
    echo "✅ Все исправления применены!\n";
    echo "Теперь попробуйте открыть черновик снова.\n";
    
} catch (PDOException $e) {
    echo "❌ Ошибка БД: " . $e->getMessage() . "\n";
}