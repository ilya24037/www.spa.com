@echo off
chcp 65001 >nul
title Laravel Migrations
echo =====================================
echo     ВЫПОЛНЕНИЕ МИГРАЦИЙ LARAVEL
echo =====================================
echo.

cd /d %~dp0

echo 🔍 Проверяем статус миграций...
php artisan migrate:status
echo.

echo 📋 Выполняем миграции...
php artisan migrate
echo.

echo ✅ Готово!
echo.
pause 