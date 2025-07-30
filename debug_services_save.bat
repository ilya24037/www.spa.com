@echo off
chcp 65001 >nul
echo ========================================
echo ОТЛАДКА СОХРАНЕНИЯ УСЛУГ
echo ========================================
echo.

echo 1. Проверяем логи Laravel...
echo Последние записи в storage/logs/laravel.log:
if exist "storage\logs\laravel.log" (
    powershell "Get-Content 'storage\logs\laravel.log' | Select-Object -Last 20"
) else (
    echo Файл логов не найден
)

echo.
echo 2. Проверяем данные черновика до сохранения...
php artisan tinker --execute="echo '=== ДАННЫЕ ДО СОХРАНЕНИЯ ==='; $ad = App\Models\Ad::find(137); if($ad) { echo 'Services: ' . json_encode($ad->services); }"

echo.
echo ========================================
echo ИНСТРУКЦИИ ДЛЯ ТЕСТИРОВАНИЯ:
echo ========================================
echo 1. Откройте черновик в браузере
echo 2. Выберите несколько услуг
echo 3. Сохраните черновик
echo 4. Запустите этот файл снова
echo.
pause 