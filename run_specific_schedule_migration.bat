@echo off
chcp 65001 >nul
echo 🎯 ВЫПОЛНЕНИЕ КОНКРЕТНОЙ МИГРАЦИИ SCHEDULE
echo.
echo 📝 Запускаю только миграцию schedule...
php artisan migrate --path=database/migrations/2025_07_30_085744_add_schedule_fields_to_ads_table.php
echo.
echo ✅ Проверяю результат...
php artisan tinker --execute="echo 'Schedule fields:'; try { DB::select('SELECT schedule, schedule_notes FROM ads LIMIT 1'); echo 'Fields exist!'; } catch(Exception $e) { echo 'Fields missing: ' . $e->getMessage(); }"
echo.
pause