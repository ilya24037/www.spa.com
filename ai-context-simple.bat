@echo off
title AI Context Generator
echo.
echo AI Context Generator
echo ==================
echo.
cd /d "C:\www.spa.com"

:start
echo Generating AI context...
php artisan ai:context --quick
echo.
echo Done! Press any key to continue...
pause >nul

if exist AI_CONTEXT.md (
    echo Opening AI_CONTEXT.md...
    start notepad AI_CONTEXT.md
    echo.
    echo File opened in notepad!
) else (
    echo ERROR: AI_CONTEXT.md not found!
)

echo.
echo Press any key to exit...
pause >nul