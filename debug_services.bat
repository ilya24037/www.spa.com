@echo off
chcp 65001 >nul
echo 🐛 ОТЛАДКА ПРОБЛЕМ С УСЛУГАМИ
echo.

echo 📋 Проверяем данные черновика в БД...
php artisan tinker --execute="echo 'Данные черновика ID 137:'; \$ad = \App\Models\Ad::find(137); if(\$ad) { echo 'Title: ' . \$ad->title . PHP_EOL; echo 'Services: ' . json_encode(\$ad->services) . PHP_EOL; echo 'Services Additional Info: ' . \$ad->services_additional_info . PHP_EOL; } else { echo 'Черновик не найден' . PHP_EOL; }"

echo.
echo 🔍 Проверяем структуру таблицы ads...
php artisan tinker --execute="echo 'Структура таблицы ads:'; \$columns = \DB::select('SHOW COLUMNS FROM ads'); foreach(\$columns as \$col) { echo \$col->Field . ' - ' . \$col->Type . PHP_EOL; }"

echo.
echo ✅ Отладка завершена!
echo.
pause 