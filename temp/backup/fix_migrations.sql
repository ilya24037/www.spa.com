-- Помечаем проблемную миграцию как выполненную
INSERT INTO migrations (migration, batch) 
VALUES ('2024_12_19_000000_create_master_media_tables', 30)
ON DUPLICATE KEY UPDATE batch = 30;

-- Проверяем, что таблица bookings не существует
-- Если существует, удаляем её для переустановки
DROP TABLE IF EXISTS bookings;

-- Проверяем таблицу schedules
DROP TABLE IF EXISTS schedules; 