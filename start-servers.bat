@echo off
echo 🚀 Запуск серверов SPA Platform...
powershell.exe -ExecutionPolicy Bypass -File "scripts\start-servers.ps1" %*
pause 