@echo off
echo Checking application health...
echo.

echo 1. Updating composer autoload...
composer dump-autoload
if %errorlevel% neq 0 (
    echo ERROR: Composer autoload failed!
    exit /b 1
)

echo.
echo 2. Clearing Laravel caches...
php artisan optimize:clear
if %errorlevel% neq 0 (
    echo ERROR: Cache clear failed!
    exit /b 1
)

echo.
echo 3. Checking route list...
php artisan route:list --columns=uri,name,action > routes-check.txt 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Routes check failed!
    type routes-check.txt
    exit /b 1
) else (
    echo Routes OK!
)

echo.
echo 4. Running migrations status...
php artisan migrate:status > migration-check.txt 2>&1
if %errorlevel% neq 0 (
    echo WARNING: Migration check failed
    type migration-check.txt
) else (
    echo Migrations OK!
)

echo.
echo 5. Running tests...
php artisan test --stop-on-failure
if %errorlevel% neq 0 (
    echo ERROR: Tests failed!
    exit /b 1
)

echo.
echo ========================================
echo APPLICATION HEALTH CHECK PASSED!
echo ========================================