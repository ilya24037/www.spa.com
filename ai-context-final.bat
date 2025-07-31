@echo off
chcp 65001 >nul
color 0A
title AI Context Generator v1.0

echo.
echo ╔═══════════════════════════════════════╗
echo ║     🤖 AI CONTEXT GENERATOR          ║  
echo ║     Анализ проекта для ИИ           ║
echo ╚═══════════════════════════════════════╝
echo.

cd /d C:\www.spa.com

:menu
echo 🚀 Выберите режим генерации:
echo.
echo [1] 📝 Быстрый контекст + открыть файл
echo [2] 📊 Обычный анализ проекта
echo [3] 🔬 Полный анализ (все метрики)
echo [4] 🤖 Автоматический режим (без вопросов)
echo [5] 📂 Открыть существующий AI_CONTEXT.md
echo [0] ❌ Выход
echo.

set /p choice="Выберите (0-5): "

if "%choice%"=="1" goto quick_context
if "%choice%"=="2" goto normal_context
if "%choice%"=="3" goto full_context
if "%choice%"=="4" goto auto_context
if "%choice%"=="5" goto open_context
if "%choice%"=="0" exit
goto menu

:quick_context
cls
echo 📝 Создаю быстрый отчет...
echo.
php artisan ai:context --quick 2>nul
if exist AI_CONTEXT.md (
    echo ✅ Готово! Открываю файл...
    start notepad AI_CONTEXT.md
    echo.
    echo 💡 СКОПИРУЙТЕ ВЕСЬ ТЕКСТ (Ctrl+A, Ctrl+C) И ВСТАВЬТЕ В ЧАТ С ИИ
) else (
    echo ❌ Ошибка: файл AI_CONTEXT.md не создан
)
pause
goto menu

:normal_context
cls
echo 📊 Запускаю обычный анализ...
echo.
php artisan ai:context 2>nul
if exist AI_CONTEXT.md (
    echo ✅ Анализ завершен! Открываю файл...
    start notepad AI_CONTEXT.md
) else (
    echo ❌ Ошибка: файл не создан
)
pause
goto menu

:full_context
cls
echo 🔬 Полный анализ проекта...
echo.
echo ⏳ Это займет немного времени...
php artisan ai:context --full 2>nul
if exist AI_CONTEXT.md (
    echo ✅ Полный анализ готов! Открываю файл...
    start notepad AI_CONTEXT.md
    echo.
    echo 💡 В файле содержится:
    echo    - Детальная структура проекта
    echo    - Анализ всех компонентов
    echo    - Метрики качества кода
    echo    - Полные рекомендации
) else (
    echo ❌ Ошибка: файл не создан
)
pause
goto menu

:auto_context
cls
echo 🤖 Автоматическая генерация...
php artisan ai:context --auto 2>nul
echo.
echo ✅ Контекст обновлен в AI_CONTEXT.md
pause
goto menu

:open_context
cls
echo 📂 Открываю AI_CONTEXT.md...
if exist AI_CONTEXT.md (
    start notepad AI_CONTEXT.md
    echo ✅ Файл открыт!
    echo.
    echo 💡 Скопируйте весь текст и вставьте в чат с ИИ
) else (
    echo ❌ Файл не найден. Сначала создайте контекст (пункт 1-3)
)
pause
goto menu