@echo off
chcp 65001 >nul
echo 🔍 ПРОВЕРКА ЛОГОВ PHOTOS DEBUG
echo.
echo 📋 Последние 20 строк лога Laravel:
tail -n 20 storage/logs/laravel.log
echo.
echo 📋 Поиск строк с Photos:
findstr "Photos" storage/logs/laravel.log | tail -n 5
echo.
pause