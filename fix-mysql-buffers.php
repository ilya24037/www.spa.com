<?php
// Увеличение буферов MySQL для исправления ошибки сортировки
$host = 'localhost';
$db = 'laravel_auth';
$user = 'root';
$pass = 'Animatori2025!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Подключение к БД успешно\n\n";
    
    // Проверяем текущие настройки
    echo "📊 Текущие настройки буферов:\n";
    $vars = ['sort_buffer_size', 'read_rnd_buffer_size', 'join_buffer_size'];
    foreach ($vars as $var) {
        $stmt = $pdo->query("SHOW VARIABLES LIKE '$var'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $mb = round($result['Value'] / 1024 / 1024, 2);
            echo "  {$result['Variable_name']}: {$result['Value']} ({$mb} MB)\n";
        }
    }
    
    echo "\n🔧 Увеличиваем буферы...\n";
    
    // Увеличиваем буферы для текущей сессии (не требует прав SUPER)
    $commands = [
        "SET SESSION sort_buffer_size = 8388608",      // 8MB
        "SET SESSION read_rnd_buffer_size = 4194304",   // 4MB
        "SET SESSION join_buffer_size = 4194304"        // 4MB
    ];
    
    foreach ($commands as $cmd) {
        try {
            $pdo->exec($cmd);
            echo "  ✅ $cmd\n";
        } catch (Exception $e) {
            echo "  ⚠️ Не удалось выполнить: $cmd\n";
        }
    }
    
    // Пытаемся установить глобальные настройки (требует прав SUPER)
    echo "\n🔧 Попытка установить глобальные настройки...\n";
    $globalCommands = [
        "SET GLOBAL sort_buffer_size = 8388608",
        "SET GLOBAL read_rnd_buffer_size = 4194304",
        "SET GLOBAL join_buffer_size = 4194304"
    ];
    
    foreach ($globalCommands as $cmd) {
        try {
            $pdo->exec($cmd);
            echo "  ✅ $cmd\n";
        } catch (Exception $e) {
            echo "  ⚠️ Требуются права администратора для: $cmd\n";
        }
    }
    
    // Оптимизируем таблицу ads
    echo "\n🔧 Оптимизация таблицы ads...\n";
    try {
        $pdo->exec("OPTIMIZE TABLE ads");
        echo "  ✅ Таблица ads оптимизирована\n";
    } catch (Exception $e) {
        echo "  ⚠️ Не удалось оптимизировать таблицу\n";
    }
    
    // Добавляем индекс для оптимизации запроса
    echo "\n🔧 Проверка индексов...\n";
    $stmt = $pdo->query("SHOW INDEX FROM ads WHERE Key_name = 'idx_user_status_created'");
    if ($stmt->rowCount() == 0) {
        try {
            $pdo->exec("CREATE INDEX idx_user_status_created ON ads (user_id, status, created_at DESC)");
            echo "  ✅ Создан индекс idx_user_status_created\n";
        } catch (Exception $e) {
            echo "  ⚠️ Индекс уже существует или не может быть создан\n";
        }
    } else {
        echo "  ✅ Индекс idx_user_status_created уже существует\n";
    }
    
    echo "\n✅ Настройка завершена!\n";
    echo "\n📝 Рекомендации:\n";
    echo "1. Добавьте в my.ini (или my.cnf):\n";
    echo "   [mysqld]\n";
    echo "   sort_buffer_size = 8M\n";
    echo "   read_rnd_buffer_size = 4M\n";
    echo "   join_buffer_size = 4M\n";
    echo "2. Перезапустите MySQL сервис\n";
    
} catch (PDOException $e) {
    echo "❌ Ошибка БД: " . $e->getMessage() . "\n";
}