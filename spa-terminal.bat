@echo off
title SPA Platform - Developer Terminal
color 0A
cls

:MENU
echo =====================================
echo    SPA PLATFORM - DEVELOPER MENU
echo =====================================
echo.
echo   1. Открыть PowerShell 7
echo   2. Открыть Windows PowerShell  
echo   3. Открыть Command Prompt (cmd)
echo   4. Запустить Laravel сервер
echo   5. Запустить Vite (npm run dev)
echo   6. Выполнить миграции
echo   7. Очистить кеш Laravel
echo   8. Показать статус проекта
echo   9. Открыть MySQL консоль
echo   0. Выход
echo.
echo =====================================
set /p choice="Выберите опцию (0-9): "

if "%choice%"=="1" goto PWSH
if "%choice%"=="2" goto POWERSHELL
if "%choice%"=="3" goto CMD
if "%choice%"=="4" goto SERVE
if "%choice%"=="5" goto VITE
if "%choice%"=="6" goto MIGRATE
if "%choice%"=="7" goto CACHE
if "%choice%"=="8" goto STATUS
if "%choice%"=="9" goto MYSQL
if "%choice%"=="0" goto EXIT

echo Неверный выбор! Попробуйте снова.
pause
cls
goto MENU

:PWSH
where pwsh >nul 2>&1
if %errorlevel%==0 (
    start "SPA Terminal - PowerShell 7" pwsh -NoExit -Command "cd '%~dp0'; cls; Write-Host 'SPA Platform - PowerShell 7' -ForegroundColor Cyan"
) else (
    echo PowerShell 7 не установлен!
    echo Скачайте с: https://github.com/PowerShell/PowerShell/releases
    pause
)
goto MENU

:POWERSHELL
start "SPA Terminal - PowerShell" powershell -NoExit -Command "cd '%~dp0'; cls; Write-Host 'SPA Platform - Windows PowerShell' -ForegroundColor Blue"
goto MENU

:CMD
start "SPA Terminal - CMD" cmd /k "cd /d %~dp0 && cls && echo SPA Platform - Command Prompt"
goto MENU

:SERVE
echo Запускаем Laravel сервер...
start "Laravel Server" cmd /k "cd /d %~dp0 && php artisan serve"
echo Сервер запущен на http://localhost:8000
pause
goto MENU

:VITE
echo Запускаем Vite dev server...
start "Vite Dev Server" cmd /k "cd /d %~dp0 && npm run dev"
pause
goto MENU

:MIGRATE
echo Выполняем миграции...
cd /d %~dp0
php artisan migrate
pause
goto MENU

:CACHE
echo Очищаем кеш Laravel...
cd /d %~dp0
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo Кеш очищен!
pause
goto MENU

:STATUS
cls
echo ===== СТАТУС ПРОЕКТА =====
echo.
cd /d %~dp0
echo PHP версия:
php -v | findstr /i "PHP"
echo.
echo Laravel версия:
php artisan --version
echo.
echo Статус миграций:
php artisan migrate:status | findstr /i "pending"
echo.
pause
goto MENU

:MYSQL
set /p dbname="Введите имя БД (по умолчанию spa_db): "
if "%dbname%"=="" set dbname=spa_db
start "MySQL Console" cmd /k "mysql -u root -p %dbname%"
goto MENU

:EXIT
exit 