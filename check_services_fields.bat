@echo off
chcp 65001 >nul
echo ========================================
echo ПРОВЕРКА ПОЛЕЙ SERVICES В ТАБЛИЦЕ ADS
echo ========================================
echo.

echo Проверяем поля services в таблице ads...
php artisan tinker --execute="echo 'Поля services в таблице ads:'; $columns = DB::select('SHOW COLUMNS FROM ads'); foreach($columns as $col) { if(strpos($col->Field, 'service') !== false) { echo $col->Field . ' - ' . $col->Type . PHP_EOL; } }"

echo.
echo ========================================
echo ПРОВЕРКА ЗАВЕРШЕНА!
echo ========================================
echo.
pause 