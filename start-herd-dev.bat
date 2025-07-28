@echo off
chcp 65001 >nul
title SPA Platform - Development with Herd
color 0A

echo.
echo ==========================================
echo 🚀 SPA Platform - Development с Herd
echo ==========================================
echo.

echo 📋 Проверка окружения...
echo.

REM Проверяем Herd
where herd >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Laravel Herd не найден в PATH
    echo 💡 Убедитесь что Herd установлен и добавлен в PATH
    echo.
    pause
    exit /b 1
) else (
    echo ✅ Laravel Herd найден
)

REM Проверяем Node.js
where node >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Node.js не найден в PATH
    echo 💡 Установите Node.js с https://nodejs.org
    echo.
    pause
    exit /b 1
) else (
    echo ✅ Node.js: 
    node --version
)

REM Проверяем npm
where npm >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ npm не найден
    pause
    exit /b 1
) else (
    echo ✅ npm: 
    npm --version
)

echo.
echo 🔧 Подготовка проекта...

REM Очищаем кеши Laravel
echo 📦 Очистка кешей...
php artisan cache:clear >nul 2>&1
php artisan config:clear >nul 2>&1
php artisan route:clear >nul 2>&1
php artisan view:clear >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Кеши очищены
) else (
    echo ⚠️  Ошибка очистки кешей
)

REM Проверяем зависимости npm
echo 📦 Проверка зависимостей npm...
if not exist "node_modules\" (
    echo 🔄 Установка npm зависимостей...
    npm install
    if %errorlevel% neq 0 (
        echo ❌ Ошибка установки npm зависимостей
        pause
        exit /b 1
    )
) else (
    echo ✅ npm зависимости найдены
)

echo.
echo 🌐 Информация о серверах:
echo.
echo 📌 Laravel сервер (через Herd): http://spa.test
echo 📌 Vite dev сервер: http://localhost:5173
echo 📌 Админ панель Herd: доступна через иконку в трее
echo.

REM Проверяем работу Herd
echo 🔍 Проверка статуса Herd...
herd status >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Herd активен
) else (
    echo ⚠️  Herd может быть не активен. Проверьте настройки.
)

echo.
echo 🏗️  Запуск Vite dev сервера...
echo.
echo 📖 После запуска откройте в браузере:
echo    👉 http://spa.test - главная страница
echo    👉 http://spa.test/additem - создание объявления
echo    👉 http://spa.test/profile/items/draft/all - черновики
echo.
echo 🛑 Для остановки нажмите Ctrl+C
echo.

REM Запускаем Vite
npm run dev

echo.
echo 🔄 Vite остановлен. Нажмите любую клавишу для выхода...
pause >nul 