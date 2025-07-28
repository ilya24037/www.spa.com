@echo off
chcp 65001 >nul
title SPA Platform - Остановка серверов
color 0C
cls

echo =====================================
echo   SPA PLATFORM - ОСТАНОВКА СЕРВЕРОВ
echo =====================================
echo.

echo 🛑 Останавливаем серверы...
echo.

:: Останавливаем процессы Laravel сервера (php artisan serve)
echo 📡 Останавливаем Laravel сервер...
taskkill /f /im php.exe >nul 2>&1

:: Останавливаем процессы Node.js (Vite)
echo ⚡ Останавливаем Vite dev сервер...
taskkill /f /im node.exe >nul 2>&1

:: Останавливаем процессы по портам
echo 🔌 Освобождаем порты...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8000') do taskkill /f /pid %%a >nul 2>&1
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :5173') do taskkill /f /pid %%a >nul 2>&1

echo.
echo ✅ Серверы остановлены!
echo.
echo 📊 Освобожденные порты:
echo   🌐 Laravel:  8000
echo   ⚡ Vite:     5173
echo.

timeout /t 3 /nobreak >nul
echo 🎯 Готово! Нажмите любую клавишу...
pause >nul 