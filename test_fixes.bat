@echo off
chcp 65001 >nul
echo === ТЕСТИРОВАНИЕ ИСПРАВЛЕНИЙ ===
echo.
echo Очищаем кеши...
php artisan cache:clear
php artisan config:clear
php artisan view:clear
echo.
echo === ПРОВЕРКА ДАННЫХ ЧЕРНОВИКА ===
php check_ad_data.php
echo.
echo === ГОТОВО ===
echo Теперь откройте браузер и проверьте:
echo 1. Загружаются ли фотографии
echo 2. Сохраняется ли график работы
echo 3. Смотрите логи в консоли браузера
pause