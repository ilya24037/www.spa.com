@echo off
chcp 65001 >nul

echo ======================================
echo  FINAL ENCODING TEST FOR CURSOR
echo ======================================
echo.

echo Step 1: Testing basic commands
echo.

echo npm version:
npm --version 2>nul || echo "npm - ERROR or not found"
echo.

echo composer version:
composer --version 2>nul || echo "composer - ERROR or not found"
echo.

echo chcp (codepage):
chcp 2>nul || echo "chcp - ERROR or not found"
echo.

echo Step 2: Testing Laravel (if available)
php artisan --version 2>nul || echo "Laravel not found in current directory (this is OK)"
echo.

echo Step 3: Testing PowerShell script
powershell -ExecutionPolicy Bypass -File "fix-terminal-encoding.ps1"
echo.

echo ======================================
echo If all commands show correct output without garbage characters,
echo the encoding fix is working properly!
echo ======================================
echo.
pause