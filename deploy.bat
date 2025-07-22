@echo off
echo 🚀 Запуск деплоя SPA Platform...
powershell.exe -ExecutionPolicy Bypass -File "scripts\deploy.ps1" %*
pause 