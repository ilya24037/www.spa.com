@echo off
echo 🔄 Перезапуск SPA Platform...
powershell.exe -ExecutionPolicy Bypass -File "scripts\restart.ps1" %*
pause 