@echo off
echo ========================================
echo   Остановка SPA Platform
echo ========================================
echo.

:: Останавливаем процесс PHP
echo Останавливаем Laravel сервер...
taskkill /F /IM php.exe 2>nul
if %errorlevel%==0 (
    echo [OK] Laravel сервер остановлен
) else (
    echo [!] Laravel сервер не запущен
)

:: Останавливаем Node процессы
echo Останавливаем Node.js процессы...
taskkill /F /IM node.exe 2>nul
if %errorlevel%==0 (
    echo [OK] Node.js процессы остановлены
) else (
    echo [!] Node.js процессы не запущены
)

echo.
echo ========================================
echo   Все сервисы остановлены
echo ========================================
echo.
pause