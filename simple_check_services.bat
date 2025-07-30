@echo off
chcp 65001 >nul
echo ========================================
echo ПРОВЕРКА ПОЛЕЙ SERVICES
echo ========================================
echo.

echo Проверяем поля services в таблице ads...
php artisan tinker --execute="echo '=== ПОЛЯ SERVICES ==='; $columns = DB::select('SHOW COLUMNS FROM ads'); foreach($columns as $col) { if(strpos($col->Field, 'service') !== false) { echo 'FIELD: ' . $col->Field . ' TYPE: ' . $col->Type; } }"

echo.
echo Проверяем данные черновика ID 137...
php artisan tinker --execute="echo '=== ДАННЫЕ ЧЕРНОВИКА 137 ==='; $ad = App\Models\Ad::find(137); if($ad) { echo 'Title: ' . $ad->title; echo 'Services: ' . json_encode($ad->services); echo 'Services Additional Info: ' . $ad->services_additional_info; } else { echo 'Черновик не найден'; }"

echo.
echo ========================================
echo ПРОВЕРКА ЗАВЕРШЕНА!
echo ========================================
echo.
pause 