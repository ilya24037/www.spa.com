@echo off
echo Starting import analysis...
cd /d "C:\www.spa.com\scripts"
powershell -ExecutionPolicy Bypass -File ".\import-check-en.ps1"
echo.
echo For detailed info run:
echo powershell -ExecutionPolicy Bypass -File ".\import-check-en.ps1" -Detailed
pause