@echo off
echo === Laravel Status Check ===
echo.
echo PHP Version:
php -v
echo.
echo Laravel Version:
php artisan --version
echo.
echo Migration Status:
php artisan migrate:status
echo.
pause 