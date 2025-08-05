@echo off
echo === MASS IMPORT UPDATE TOOL ===
echo.
echo Available options:
echo 1. Preview mode (see what will be changed)
echo 2. Update all files with backups
echo 3. Update specific file
echo 4. Cancel
echo.
set /p choice="Choose option (1-4): "

cd /d "C:\www.spa.com\scripts"

if "%choice%"=="1" (
    echo.
    echo Running preview mode...
    powershell -ExecutionPolicy Bypass -File ".\mass-import-update.ps1" -Preview
) else if "%choice%"=="2" (
    echo.
    echo WARNING: This will modify files! Backups will be created.
    set /p confirm="Continue? (y/n): "
    if /i "%confirm%"=="y" (
        powershell -ExecutionPolicy Bypass -File ".\mass-import-update.ps1"
    )
) else if "%choice%"=="3" (
    echo.
    set /p filename="Enter file path (e.g., Pages/Home.vue): "
    powershell -ExecutionPolicy Bypass -File ".\mass-import-update.ps1" -TargetFile "%filename%"
) else (
    echo Cancelled.
)

echo.
pause