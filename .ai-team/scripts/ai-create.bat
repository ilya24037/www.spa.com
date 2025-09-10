@echo off
chcp 65001 >nul
echo.
echo 🚀 AI Создание компонента
echo ========================
echo.

if "%1"=="" goto :usage
if "%2"=="" goto :usage

powershell -ExecutionPolicy Bypass -File scripts\ai\ai-create.ps1 -Type %1 -Name %2
goto :end

:usage
echo ❌ Ошибка: укажите тип и имя
echo.
echo Использование: ai-create [тип] [имя]
echo.
echo Типы:
echo   - feature : Создать feature (фичу)
echo   - entity  : Создать entity (сущность)  
echo   - widget  : Создать widget (виджет)
echo   - page    : Создать page (страницу)
echo   - domain  : Создать domain (домен)
echo.
echo Примеры:
echo   ai-create feature user-filter
echo   ai-create entity review
echo   ai-create widget dashboard
echo   ai-create domain Payment
echo.

:end
pause