@echo off
title AI Context - Debug
echo Step 1: Starting...
pause

echo Step 2: Setting directory...
cd /d C:\www.spa.com
echo Current directory: %CD%
pause

echo Step 3: Testing PHP...
php --version
pause

echo Step 4: Testing artisan...
php artisan --version
pause

echo Step 5: Running AI context command...
php artisan ai:context --quick
pause

echo Step 6: Checking file...
if exist AI_CONTEXT.md (
    echo File exists!
    dir AI_CONTEXT.md
) else (
    echo File not found!
)
pause

echo Step 7: Opening file...
if exist AI_CONTEXT.md (
    start notepad AI_CONTEXT.md
    echo File opened in notepad
) else (
    echo Cannot open - file missing
)
pause

echo All steps completed!
pause