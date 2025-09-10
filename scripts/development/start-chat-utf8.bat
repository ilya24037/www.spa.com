@echo off
title AI Team Chat (UTF-8 Fixed)
color 0A
chcp 65001 >nul

echo.
echo ========================================
echo    AI TEAM CHAT - UTF-8 FIXED
echo ========================================
echo.

:: Остановка старого сервера если запущен
echo Останавливаем старые процессы...
taskkill /F /FI "WINDOWTITLE eq Chat Server*" >nul 2>&1
timeout /t 2 >nul

:: Запуск нового сервера с исправленной кодировкой
echo Запускаем сервер с поддержкой русского языка...
start "Chat Server UTF-8" powershell -NoExit -ExecutionPolicy Bypass -File "chat-server-utf8.ps1" -Port 8080

timeout /t 3 >nul

:: Открытие в браузере
echo Открываем чат в браузере...
start "" "http://localhost:8080"

echo.
echo ========================================
echo    СЕРВЕР ЗАПУЩЕН УСПЕШНО!
echo ========================================
echo.
echo Кодировка UTF-8 активна!
echo Русский текст теперь должен отображаться корректно.
echo.
echo Чат доступен по адресу: http://localhost:8080
echo.
pause