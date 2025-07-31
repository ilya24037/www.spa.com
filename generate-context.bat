@echo off
cd /d "C:\www.spa.com"
php artisan ai:context --auto
echo.
echo File AI_CONTEXT.md is ready!
pause