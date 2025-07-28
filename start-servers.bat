@echo off
chcp 65001 >nul
title SPA Platform - Запуск серверов
color 0B
cls

echo =====================================
echo    SPA PLATFORM - ЗАПУСК СЕРВЕРОВ
echo =====================================
echo.
echo 🚀 Запускаем Laravel сервер и Vite...
echo.

cd /d %~dp0

echo 📋 Проверяем окружение...
echo.

:: Проверяем PHP
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP не найден! Установите PHP и добавьте в PATH
    pause
    exit /b 1
)

:: Проверяем Node.js
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Node.js не найден! Установите Node.js
    pause
    exit /b 1
)

:: Проверяем .env файл
if not exist .env (
    echo ❌ Файл .env не найден! Скопируйте .env.example в .env
    pause
    exit /b 1
)

echo ✅ Окружение готово!
echo.

echo 🔄 Очищаем кеш Laravel...
php artisan cache:clear >nul 2>&1
php artisan config:clear >nul 2>&1
php artisan route:clear >nul 2>&1

echo 📦 Проверяем зависимости...
if not exist vendor (
    echo 📥 Устанавливаем Composer зависимости...
    composer install
)

if not exist node_modules (
    echo 📥 Устанавливаем NPM зависимости...
    npm install
)

echo.
echo 🚀 Запускаем серверы...
echo.

:: Запускаем Laravel сервер в отдельном окне
echo 📡 Запускаем Laravel сервер (http://localhost:8000)
start "Laravel Server" cmd /k "chcp 65001 && title Laravel Server && color 0A && php artisan serve"

:: Небольшая пауза
timeout /t 2 /nobreak >nul

:: Запускаем Vite dev сервер в отдельном окне  
echo ⚡ Запускаем Vite dev сервер (http://localhost:5173)
start "Vite Dev Server" cmd /k "chcp 65001 && title Vite Dev Server && color 0E && npm run dev"

:: Небольшая пауза
timeout /t 2 /nobreak >nul

echo.
echo ✅ Серверы запущены!
echo.
echo 📊 Открытые серверы:
echo   🌐 Laravel:  http://localhost:8000
echo   ⚡ Vite:     http://localhost:5173
echo.
echo 💡 Советы:
echo   • Для остановки серверов закройте окна или нажмите Ctrl+C
echo   • Laravel сервер обслуживает API и веб-страницы
echo   • Vite сервер компилирует и обновляет фронтенд в реальном времени
echo.

set /p open="Открыть сайт в браузере? (y/n): "
if /i "%open%"=="y" (
    echo 🌐 Открываем сайт...
    start http://localhost:8000
)

echo.
echo 🎯 Нажмите любую клавишу для возврата в меню...
pause >nul 