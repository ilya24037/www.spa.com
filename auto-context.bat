@echo off
chcp 65001 >nul
title Автоматическое слежение за проектом
color 0E

echo.
echo ╔════════════════════════════════════╗
echo ║    ⚡ АВТОМАТИЧЕСКОЕ СЛЕЖЕНИЕ      ║
echo ║    Обновление контекста для ИИ     ║
echo ╚════════════════════════════════════╝
echo.

cd /d D:\www.spa.com

echo 🚀 Система запущена!
echo 📝 Файл AI_CONTEXT.md будет обновляться каждые 5 минут
echo ⚠️  Для остановки нажмите Ctrl+C
echo.

:loop
echo %date% %time% - Обновляю контекст для ИИ...
php artisan ai:context --auto --quick

if %ERRORLEVEL% EQU 0 (
    echo ✅ Контекст обновлен успешно!
) else (
    echo ❌ Ошибка при обновлении контекста
)

echo 💤 Ожидаю 5 минут до следующего обновления...
echo.

timeout /t 300 >nul
goto loop