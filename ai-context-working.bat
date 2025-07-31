@echo off
color 0A
title AI Context Generator

echo.
echo AI Context Generator
echo ===================
echo.

cd /d C:\www.spa.com

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

if "%choice%"=="1" goto quick_context
if "%choice%"=="2" goto normal_context  
if "%choice%"=="3" goto full_context
if "%choice%"=="4" goto auto_context
if "%choice%"=="5" goto open_context
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
    echo COPY ALL TEXT (Ctrl+A, Ctrl+C) AND PASTE TO AI CHAT
) else (
    echo ERROR: AI_CONTEXT.md not created
)
pause
goto menu

:normal_context
cls
echo Running normal analysis...
echo.
php artisan ai:context 2>nul
if exist AI_CONTEXT.md (
    echo Analysis complete! Opening file...
    start notepad AI_CONTEXT.md
) else (
    echo ERROR: File not created
)
pause
goto menu

:full_context
cls
echo Full project analysis...
echo.
echo This will take some time...
php artisan ai:context --full 2>nul
if exist AI_CONTEXT.md (
    echo Full analysis ready! Opening file...
    start notepad AI_CONTEXT.md
    echo.
    echo File contains:
    echo - Detailed project structure
    echo - Analysis of all components  
    echo - Code quality metrics
    echo - Full recommendations
) else (
    echo ERROR: File not created
)
pause
goto menu

:auto_context
cls
echo Auto generation...
php artisan ai:context --auto 2>nul
echo.
echo Context updated in AI_CONTEXT.md
pause
goto menu

:open_context
cls
echo Opening AI_CONTEXT.md...
if exist AI_CONTEXT.md (
    start notepad AI_CONTEXT.md
    echo File opened!
    echo.
    echo Copy all text and paste to AI chat
) else (
    echo File not found. Create context first (option 1-3)
)
pause
goto menu