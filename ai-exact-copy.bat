@echo off
chcp 65001 >nul
color 0A
title AI Context Generator

echo.
echo AI Context Generator
echo ===================
echo.

cd /d D:\www.spa.com

:menu
echo Choose option:
echo.
echo [1] Quick context + open file
echo [0] Exit
echo.

set /p choice="Enter choice: "

if "%choice%"=="1" goto quick_context
if "%choice%"=="0" exit
goto menu

:quick_context
cls
echo Creating quick report...
echo.
php artisan ai:context --quick 2>nul
if exist AI_CONTEXT.md (
    echo Done! Opening file...
    start notepad AI_CONTEXT.md
    echo.
    echo COPY ALL TEXT AND PASTE TO AI CHAT
) else (
    echo ERROR: AI_CONTEXT.md not created
)
pause
goto menu