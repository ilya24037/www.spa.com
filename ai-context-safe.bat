@echo off
title AI Context Generator - Safe Mode
cd /d "C:\www.spa.com"

echo AI Context Generator - Safe Mode
echo ==================================
echo.

:menu
echo Choose option:
echo [1] Test PHP command
echo [2] Test artisan
echo [3] Generate context (safe)
echo [4] Open existing file
echo [0] Exit
echo.
set /p choice="Enter choice: "

if "%choice%"=="1" goto test_php
if "%choice%"=="2" goto test_artisan
if "%choice%"=="3" goto safe_generate
if "%choice%"=="4" goto open_file
if "%choice%"=="0" exit
echo Invalid choice. Try again.
pause
goto menu

:test_php
cls
echo Testing PHP...
echo.
php --version
echo.
echo PHP test complete.
pause
goto menu

:test_artisan
cls
echo Testing artisan...
echo.
php artisan --version
echo.
echo Artisan test complete.
pause
goto menu

:safe_generate
cls
echo Generating context (safe mode)...
echo.
echo Step 1: Testing command...
php artisan list | findstr ai:context
echo.
echo Step 2: Running command with error handling...
php artisan ai:context --quick 2>&1
echo.
echo Step 3: Checking result...
if exist AI_CONTEXT.md (
    dir AI_CONTEXT.md
    echo.
    echo ✅ File exists! Opening...
    notepad.exe AI_CONTEXT.md
) else (
    echo ❌ File not found
)
echo.
echo Press any key to continue...
pause
goto menu

:open_file
cls
if exist AI_CONTEXT.md (
    echo Opening existing AI_CONTEXT.md...
    notepad.exe AI_CONTEXT.md
) else (
    echo AI_CONTEXT.md not found
)
pause
goto menu