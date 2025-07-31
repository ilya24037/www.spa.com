@echo off
title AI Context Generator
cd /d "C:\www.spa.com"

echo.
echo AI Context Generator (Herd Path)
echo ================================
echo.
echo Generating context...
echo.

"C:\Users\user1\.config\herd\bin\php.bat" artisan ai:context --quick 2>nul

echo.
echo Done! Check AI_CONTEXT.md file
echo.
if exist AI_CONTEXT.md (
    echo Opening file...
    notepad.exe AI_CONTEXT.md
) else (
    echo File not created
)

echo.
echo Press any key to exit...
pause >nul