@echo off
chcp 65001 >nul
echo Запуск простой проверки импортов...
cd /d "C:\www.spa.com\scripts"
powershell -ExecutionPolicy Bypass -File ".\simple-import-check.ps1"
echo.
echo Для детальной информации запустите:
echo powershell -ExecutionPolicy Bypass -File ".\simple-import-check.ps1" -Detailed
pause