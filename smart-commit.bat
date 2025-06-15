@echo off
chcp 65001 >nul
color 0A
title Умный помощник разработчика v3.0

echo.
echo ╔═══════════════════════════════════════╗
echo ║     🤖 УМНЫЙ ПОМОЩНИК v3.0           ║  
echo ║     Максимальная автоматизация       ║
echo ╚═══════════════════════════════════════╝
echo.

cd /d D:\www.spa.com

:menu
echo 🚀 Что хотите сделать?
echo.
echo [1] 📝 Быстрый отчет для ИИ (основное)
echo [2] 📊 Полный анализ проекта 
echo [3] 🔬 Детальный дамп (все метрики)
echo [4] 💾 Сохранить в GitHub Desktop
echo [5] ⚡ Автоматическое слежение
echo [6] 📈 Показать статистику проекта
echo [7] 📂 Открыть AI_CONTEXT.md
echo [0] ❌ Выход
echo.

set /p choice="Выберите (0-7): "

if "%choice%"=="1" goto quick_report
if "%choice%"=="2" goto full_analysis
if "%choice%"=="3" goto complete_dump
if "%choice%"=="4" goto github_save
if "%choice%"=="5" goto auto_watch
if "%choice%"=="6" goto show_stats
if "%choice%"=="7" goto open_context
if "%choice%"=="0" exit
goto menu

:quick_report
cls
echo 📝 Создаю быстрый отчет...
echo.
php artisan ai:context --quick
echo.
echo ✅ Готово! Основной отчет в AI_CONTEXT.md
echo.
echo 💡 Теперь:
echo    1. Откройте AI_CONTEXT.md
echo    2. Скопируйте весь текст (Ctrl+A, Ctrl+C)
echo    3. Вставьте в чат с ИИ помощником
echo.
pause
goto menu

:full_analysis
cls  
echo 📊 Запускаю полный анализ...
echo.
php artisan ai:context
echo.
echo ✅ Полный анализ готов! Файл: AI_CONTEXT.md
pause
goto menu

:complete_dump
cls
echo 🔬 Максимально детальный анализ...
echo.
php artisan ai:context --full
echo.
echo ✅ Детальный дамп готов со всеми метриками!
pause
goto menu

:github_save
cls
echo 💾 Инструкция для GitHub Desktop:
echo.
echo 📋 ПОШАГОВАЯ ИНСТРУКЦИЯ:
echo    1. Откройте GitHub Desktop
echo    2. Увидите список измененных файлов слева
echo    3. В поле "Summary" напишите что сделали
echo    4. Нажмите синюю кнопку "Commit to main"
echo    5. Нажмите "Push origin" (отправить в GitHub)
echo.
echo ✅ После этого изменения будут сохранены!
echo.
pause
goto menu

:auto_watch
cls
echo ⚡ Запускаю автоматическое слежение...
echo.
echo 💡 Система будет каждые 5 минут обновлять AI_CONTEXT.md
echo    Вы всегда будете иметь актуальный отчет для ИИ!
echo.
echo ⚠️  Для остановки нажмите Ctrl+C
echo.
start auto-context.bat
echo ✅ Автослежение запущено в отдельном окне!
pause
goto menu

:show_stats
cls
echo 📈 Быстрая статистика проекта:
echo.
php artisan ai:context --quick --auto
echo.
echo 📄 Полный отчет в AI_CONTEXT.md
pause
goto menu

:open_context
cls
echo 📂 Открываю AI_CONTEXT.md...
if exist AI_CONTEXT.md (
    start notepad AI_CONTEXT.md
    echo ✅ Файл открыт в блокноте!
) else (
    echo ❌ Файл AI_CONTEXT.md не найден
    echo 💡 Сначала создайте отчет (пункт 1, 2 или 3)
)
echo.
pause
goto menu