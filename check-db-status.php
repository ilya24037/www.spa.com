<?php
// Проверка статуса БД с правильными учетными данными
$host = 'localhost';
$db = 'laravel_auth';
$user = 'root';
$pass = 'Animatori2025!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Подключение к БД успешно\n\n";
    
    // Проверяем настройки MySQL
    echo "📊 Настройки MySQL:\n";
    echo "==================\n";
    
    $variables = [
        'max_allowed_packet',
        'sort_buffer_size', 
        'read_rnd_buffer_size',
        'innodb_buffer_pool_size',
        'max_connections'
    ];
    
    foreach ($variables as $var) {
        $stmt = $pdo->query("SHOW VARIABLES LIKE '$var'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $value = $result['Value'];
            $mb = round($value / 1024 / 1024, 2);
            echo "  {$result['Variable_name']}: {$value} ({$mb} MB)\n";
        }
    }
    
    echo "\n📈 Статистика таблицы ads:\n";
    echo "==========================\n";
    
    // Статистика по таблице ads
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total_ads,
            SUM(status = 'draft') as drafts,
            SUM(status = 'active') as active,
            AVG(LENGTH(photos)) as avg_photos_size,
            MAX(LENGTH(photos)) as max_photos_size,
            AVG(LENGTH(services)) as avg_services_size,
            MAX(LENGTH(services)) as max_services_size,
            AVG(LENGTH(geo)) as avg_geo_size,
            MAX(LENGTH(geo)) as max_geo_size
        FROM ads
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "  Всего объявлений: {$stats['total_ads']}\n";
    echo "  Черновиков: {$stats['drafts']}\n";
    echo "  Активных: {$stats['active']}\n";
    echo "\n  Размеры полей:\n";
    echo "  - photos avg: " . round($stats['avg_photos_size']) . " bytes, max: {$stats['max_photos_size']} bytes\n";
    echo "  - services avg: " . round($stats['avg_services_size']) . " bytes, max: {$stats['max_services_size']} bytes\n";
    echo "  - geo avg: " . round($stats['avg_geo_size']) . " bytes, max: {$stats['max_geo_size']} bytes\n";
    
    // Если есть слишком большие поля, предупреждаем
    if ($stats['max_photos_size'] > 10000 || $stats['max_services_size'] > 10000 || $stats['max_geo_size'] > 10000) {
        echo "\n⚠️ ВНИМАНИЕ: Обнаружены слишком большие поля!\n";
        echo "Рекомендуется запустить fix-drafts-laravel.php для очистки.\n";
    } else {
        echo "\n✅ Все поля в пределах нормы\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Ошибка БД: " . $e->getMessage() . "\n";
}