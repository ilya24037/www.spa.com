<?php
// Временный скрипт для проверки и установки лимитов памяти

// Увеличиваем лимиты памяти и времени выполнения
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);
ini_set('post_max_size', '50M');
ini_set('upload_max_filesize', '10M');
ini_set('max_file_uploads', 10);

// Показываем текущие настройки
echo "<h2>Текущие настройки PHP:</h2>";
echo "<pre>";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "</pre>";

// Проверяем настройки MySQL
try {
    $pdo = new PDO('mysql:host=localhost;dbname=laravel_auth', 'root', '');
    $stmt = $pdo->query("SHOW VARIABLES LIKE '%max_allowed_packet%'");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Настройки MySQL:</h2>";
    echo "<pre>";
    foreach ($result as $row) {
        echo $row['Variable_name'] . ": " . $row['Value'] . "\n";
    }
    echo "</pre>";
    
    // Пытаемся увеличить max_allowed_packet
    $pdo->exec("SET GLOBAL max_allowed_packet = 67108864"); // 64MB
    echo "<p style='color: green;'>✅ max_allowed_packet установлен в 64MB</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Ошибка подключения к БД: " . $e->getMessage() . "</p>";
}

echo "<h2>Рекомендации:</h2>";
echo "<ol>";
echo "<li>Добавьте в файл <code>public/index.php</code> в самое начало:<br>";
echo "<code>ini_set('memory_limit', '512M');</code></li>";
echo "<li>Обновите настройки в <code>php.ini</code> если возможно</li>";
echo "<li>Перезапустите веб-сервер после изменений</li>";
echo "</ol>";