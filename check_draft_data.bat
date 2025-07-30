@echo off
chcp 65001 >nul
echo ========================================
echo ПРОВЕРКА ДАННЫХ ЧЕРНОВИКА
echo ========================================
echo.

echo Проверяем данные черновика ID 137...
php artisan tinker --execute="echo '=== ДАННЫЕ ЧЕРНОВИКА 137 ==='; $ad = App\Models\Ad::find(137); if($ad) { echo 'Title: ' . $ad->title; echo 'Services: ' . json_encode($ad->services); echo 'Services Additional Info: ' . $ad->services_additional_info; } else { echo 'Черновик не найден'; }"

echo.
echo ========================================
echo ПРОВЕРКА ЗАВЕРШЕНА!
echo ========================================
echo.
pause 