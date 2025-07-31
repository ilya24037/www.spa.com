@echo off
title AI Context Generator

echo.
echo AI Context Generator
echo ===================
echo.

cd /d "C:\www.spa.com"

:menu
echo Choose option:
echo.
echo [1] Quick context + open file
echo [2] Normal analysis
echo [3] Full analysis
echo [4] Auto mode
echo [5] Open existing file
echo [0] Exit
echo.

set /p choice="Enter choice (0-5): "

if "%choice%"=="1" goto quick
if "%choice%"=="2" goto normal
if "%choice%"=="3" goto full
if "%choice%"=="4" goto auto
if "%choice%"=="5" goto open
if "%choice%"=="0" exit

echo Invalid choice: %choice%
echo Please enter a number from 0 to 5
echo.
echo Press any key to return to menu...
pause
goto menu

:quick
cls
echo Generating quick context...
echo.
php artisan ai:context --quick
echo.
echo Command finished. Exit code: %ERRORLEVEL%
echo.
if exist AI_CONTEXT.md (
    echo ✅ Done! Opening file...
    start "" notepad.exe "AI_CONTEXT.md"
    echo.
    echo 💡 Copy all text and paste to AI chat
    echo.
) else (
    echo ❌ ERROR: File not created
    echo.
    echo Let's check what happened...
    echo Current directory: %CD%
    dir AI_CONTEXT.md 2>nul || echo File definitely not found
    echo.
)
echo.
echo ===========================================
echo ✅ GENERATION COMPLETED!
echo ===========================================
echo.
echo Press any key to return to menu...
echo.
echo Press any key to return to menu...
pause
goto menu

:normal
cls
echo Running normal analysis...
php artisan ai:context
if exist AI_CONTEXT.md (
    echo Done! Opening file...
    start "" notepad.exe "AI_CONTEXT.md"
) else (
    echo ERROR: File not created
)
echo.
echo Press any key to return to menu...
pause
goto menu

:full
cls
echo Running full analysis...
php artisan ai:context --full
if exist AI_CONTEXT.md (
    echo Done! Opening file...
    start "" notepad.exe "AI_CONTEXT.md"
) else (
    echo ERROR: File not created
)
echo.
echo Press any key to return to menu...
pause
goto menu

:auto
cls
echo Auto generation...
php artisan ai:context --auto
echo Done! Context updated in AI_CONTEXT.md
echo.
echo Press any key to return to menu...
pause
goto menu

:open
cls
echo Opening AI_CONTEXT.md...
if exist AI_CONTEXT.md (
    start "" notepad.exe "AI_CONTEXT.md"
    echo File opened!
) else (
    echo File not found. Create context first.
)
echo.
echo Press any key to return to menu...
pause
goto menu