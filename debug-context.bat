@echo off
cd /d "C:\www.spa.com"
echo Debug AI Context
echo ================
echo.

echo Current directory: %CD%
echo.

echo Checking if AI_CONTEXT.md exists...
if exist AI_CONTEXT.md (
    echo ✅ AI_CONTEXT.md found!
    echo File size:
    dir AI_CONTEXT.md
    echo.
    echo Trying to open with different methods:
    echo.
    echo Method 1: Direct notepad call
    notepad.exe AI_CONTEXT.md
    echo.
    echo Method 2: Windows explorer
    explorer.exe AI_CONTEXT.md
    echo.
) else (
    echo ❌ AI_CONTEXT.md NOT found!
    echo.
    echo Let's generate it first...
    php artisan ai:context --quick
    echo.
    if exist AI_CONTEXT.md (
        echo ✅ Now AI_CONTEXT.md exists!
        echo Opening with notepad...
        notepad.exe AI_CONTEXT.md
    ) else (
        echo ❌ Still no AI_CONTEXT.md after generation
        echo.
        echo Checking for errors...
        php artisan ai:context --quick
    )
)

echo.
pause