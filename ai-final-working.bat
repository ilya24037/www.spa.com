@echo off
color 0A
title AI Context Generator

echo.
echo AI Context Generator
echo ===================
echo.

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
echo Current directory: %CD%
echo.
echo Running command...
"C:\Users\user1\.config\herd\bin\php.bat" artisan ai:context --quick 2>nul
echo Command completed
echo.
if exist AI_CONTEXT.md (
    echo Done! Opening file...
    notepad.exe AI_CONTEXT.md
    echo.
    echo COPY ALL TEXT AND PASTE TO AI CHAT
) else (
    echo ERROR: AI_CONTEXT.md not created
    echo Checking current directory contents:
    dir *.md
)
echo.
echo Press any key to return to menu...
pause
goto menu