@echo off
chcp 65001 >nul
echo 🚀 ВЫПОЛНЕНИЕ МИГРАЦИИ SCHEDULE
echo.
echo 📝 Запускаю миграцию для добавления полей schedule...
php artisan migrate
echo.
echo ✅ Миграция выполнена!
echo 📊 Проверяю статус миграций...
php artisan migrate:status | findstr schedule
echo.
pause