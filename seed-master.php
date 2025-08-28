<?php

// Простой SQL для вставки тестового мастера
echo "🔧 SQL для создания тестового мастера:\n\n";

$sql = "
-- Создание тестового пользователя
INSERT IGNORE INTO users (id, name, email, password, email_verified_at, created_at, updated_at) 
VALUES (3, 'Елена', 'elena@spa.test', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW(), NOW());

-- Создание тестового мастера  
INSERT IGNORE INTO master_profiles (id, user_id, name, slug, display_name, specialty, description, rating, reviews_count, completion_rate, experience, location, city, phone, price_from, price_to, status, is_verified, is_premium, created_at, updated_at)
VALUES (
    3, 
    3,
    'Елена', 
    'sportivnyj-massaz-ot-eleny',
    'Елена - Спортивный массаж',
    'Массаж',
    'Профессиональный спортивный массаж. Опыт более 5 лет. Работаю с спортсменами и людьми, ведущими активный образ жизни.',
    4.8,
    27,
    '98%',
    '5+ лет',
    'Москва',
    'Москва', 
    '+7 (999) 123-45-67',
    2000,
    5000,
    'active',
    1,
    1,
    NOW(),
    NOW()
);
";

echo $sql;
echo "\n\n📋 Копируй этот SQL и выполни в phpMyAdmin или через команду:\n";
echo "mysql -u root -p spa_database < seed-master.sql\n";