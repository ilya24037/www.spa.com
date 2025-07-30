@echo off
chcp 65001 >nul
echo 🔍 ПРОВЕРКА ДАННЫХ SCHEDULE В БД
echo.
echo 📊 Проверяю данные черновика 137...
php check_ad_data.php
echo.
pause