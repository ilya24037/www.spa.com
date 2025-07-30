@echo off
chcp 65001 >nul
echo 🚀 ВЫПОЛНЕНИЕ МИГРАЦИИ УСЛУГ
echo.

echo 📋 Выполняем миграцию для добавления полей services...
php artisan migrate --path=database/migrations/2025_07_28_134752_add_services_fields_to_ads_table.php

echo.
echo 🔍 Проверяем результат...
php artisan tinker --execute="echo 'Поля services в таблице ads:'; \$columns = \DB::select('SHOW COLUMNS FROM ads'); foreach(\$columns as \$col) { if(strpos(\$col->Field, 'service') !== false) { echo '✅ ' . \$col->Field . ' - ' . \$col->Type . PHP_EOL; } }"

echo.
echo ✅ Миграция выполнена!
echo.
pause 