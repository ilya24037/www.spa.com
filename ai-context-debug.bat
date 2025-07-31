@echo off
echo Debug AI Context
echo.
echo Current directory: %CD%
echo.
echo Checking PHP...
where php
echo.
echo Checking artisan...
dir artisan
echo.
echo Press any key to try running command...
pause
echo.
echo Running: php artisan ai:context --quick
php artisan ai:context --quick
echo.
echo Exit code: %ERRORLEVEL%
echo.
pause