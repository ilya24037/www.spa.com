@echo off
chcp 65001 >nul
echo ========================================
echo ПРОВЕРКА СТАТУСА МИГРАЦИЙ
echo ========================================
echo.

echo Проверяем статус миграций...
php artisan migrate:status

echo.
echo Проверяем наличие полей services в таблице ads...
php artisan tinker --execute="echo 'Проверяем поля в таблице ads:'; \$columns = \DB::select('SHOW COLUMNS FROM ads'); foreach(\$columns as \$col) { if(strpos(\$col->Field, 'service') !== false) { echo \$col->Field . ' - ' . \$col->Type . PHP_EOL; } }"

echo.
echo ========================================
echo ПРОВЕРКА ЗАВЕРШЕНА!
echo ========================================
echo.
pause 