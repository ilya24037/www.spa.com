@echo off
chcp 65001 >nul
echo 🔧 СОЗДАНИЕ МИГРАЦИИ ДЛЯ ПОЛЕЙ SCHEDULE
echo.
echo 📝 Создаю миграцию add_schedule_fields_to_ads_table...
php artisan make:migration add_schedule_fields_to_ads_table --table=ads
echo.
echo ✅ Миграция создана!
echo 📂 Проверьте папку database/migrations/
echo.
pause