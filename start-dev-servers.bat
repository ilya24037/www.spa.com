@echo off
chcp 65001 >nul
echo ===========================================
echo 🚀 Запуск серверов SPA Platform
echo ===========================================
echo.

echo 📋 Проверка окружения...
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP не найден в PATH
    pause
    exit /b 1
)

where node >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Node.js не найден в PATH
    pause
    exit /b 1
)

echo ✅ PHP: 
php --version | findstr "PHP"
echo ✅ Node.js: 
node --version

echo.
echo 🔧 Очистка кешей...
php artisan cache:clear >nul 2>&1
php artisan config:clear >nul 2>&1
php artisan route:clear >nul 2>&1
php artisan view:clear >nul 2>&1
echo ✅ Кеши очищены

echo.
echo 🏗️  Сборка frontend...
npm run build
echo ✅ Frontend собран

echo.
echo 🌐 Запуск серверов...
echo.
echo 📌 Laravel сервер: http://spa.test
echo 📌 Вы можете остановить серверы нажав Ctrl+C
echo.

start "Laravel Server" cmd /k "php artisan serve --host=spa.test --port=80"
timeout /t 3 >nul
start "Vite Dev Server" cmd /k "npm run dev"

echo.
echo ✅ Серверы запущены!
echo 📖 Откройте http://spa.test в браузере
echo.
pause 