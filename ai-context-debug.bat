@echo off
chcp 65001 >nul

echo.
echo 🔍 Поиск debug кода в проекте...
echo.

echo 📋 Проверка наличия debug кода...
findstr /R /N /S "console\.log" resources\js\ 2>nul
if %ERRORLEVEL% EQU 0 (
    echo.
    echo ❌ Найден debug код в следующих файлах:
    echo.
) else (
    echo ✅ console.log не найден в resources\js\
)

echo.
echo 📋 Проверка Backap папки...
findstr /R /N /S "console\.log" Backap\ 2>nul
if %ERRORLEVEL% EQU 0 (
    echo.
    echo ❌ Найден debug код в Backap файлах:
    echo.
) else (
    echo ✅ console.log не найден в Backap\
)

echo.
echo 📋 Проверка Laravel логов...
findstr /R /N /S "Log::info\|Log::debug\|Log::error" app\ 2>nul
if %ERRORLEVEL% EQU 0 (
    echo.
    echo ❌ Найдены Laravel логи:
    echo.
) else (
    echo ✅ Laravel логи не найдены в app\
)

echo.
echo 🔍 ДЕТАЛЬНЫЙ ПОИСК:
echo ================

echo.
echo 🎯 CONSOLE.LOG в JS файлах:
findstr /R /N /S "console\.log" resources\js\ Backap\ 2>nul | findstr /V "node_modules"

echo.
echo 🎯 LARAVEL ЛОГИ в PHP файлах:
findstr /R /N /S "Log::" app\ 2>nul

echo.
echo ✅ Поиск завершен!
