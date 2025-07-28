@echo off
chcp 65001 >nul
title SPA Platform - Production Build
color 0E

echo.
echo ==========================================
echo 🏗️  SPA Platform - Production Build
echo ==========================================
echo.

echo 📦 Очистка кешей...
php artisan cache:clear >nul 2>&1
php artisan config:clear >nul 2>&1
php artisan route:clear >nul 2>&1
php artisan view:clear >nul 2>&1
echo ✅ Кеши очищены

echo.
echo 🔨 Сборка для продакшена...
npm run build

if %errorlevel% equ 0 (
    echo.
    echo ✅ Сборка завершена успешно!
    echo 📁 Файлы созданы в public/build/
    echo 🌐 Сайт готов для продакшена
) else (
    echo.
    echo ❌ Ошибка сборки!
    echo 💡 Проверьте логи выше
)

echo.
echo 📖 Для запуска в продакшене используйте:
echo    - Laravel сервер через Herd
echo    - Файлы из public/build автоматически подключатся
echo.
pause 