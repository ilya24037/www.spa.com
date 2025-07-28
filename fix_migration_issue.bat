@echo off
chcp 65001 >nul
title Исправление миграций
echo =====================================
echo    ИСПРАВЛЕНИЕ ПРОБЛЕМЫ С МИГРАЦИЯМИ
echo =====================================
echo.

echo 🔧 Помечаем проблемную миграцию как выполненную...
mysql -u root -p spa_db -e "INSERT IGNORE INTO migrations (migration, batch) VALUES ('2024_12_19_000000_create_master_media_tables', 30);"

echo.
echo ✅ Теперь выполняем остальные миграции...
php artisan migrate

echo.
echo 📊 Проверяем статус миграций...
php artisan migrate:status

echo.
echo ✅ Готово!
pause 