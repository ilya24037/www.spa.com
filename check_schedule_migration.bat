@echo off
chcp 65001 >nul
echo 🔍 ПРОВЕРКА МИГРАЦИИ SCHEDULE
echo.
echo 📊 Проверяю статус всех миграций...
php artisan migrate:status | findstr schedule
echo.
echo 📋 Проверяю структуру таблицы ads...
php artisan tinker --execute="echo 'Columns in ads table:'; foreach(DB::select('SHOW COLUMNS FROM ads') as $col) { if(str_contains($col->Field, 'schedule')) echo $col->Field . ' - ' . $col->Type . PHP_EOL; }"
echo.
pause