@echo off
cd /d %~dp0

echo ?? ��������� Laravel + Vite...

start cmd /k "php artisan serve"
start cmd /k "npm run dev"
timeout /t 3 > nul
start http://127.0.0.1:8000/

echo ?? �� ��������. ����� ��������!
pause
