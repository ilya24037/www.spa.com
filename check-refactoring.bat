@echo off
echo ========================================
echo FINAL REFACTORING CHECK
echo ========================================
echo.

echo 1. Checking old folders...
if exist "app\Models" (
    echo    [X] app\Models still exists!
) else (
    echo    [OK] app\Models deleted
)

if exist "app\Services" (
    echo    [X] app\Services still exists!
) else (
    echo    [OK] app\Services deleted
)

if exist "app\Http" (
    echo    [X] app\Http still exists!
) else (
    echo    [OK] app\Http deleted
)

echo.
echo 2. Checking application health...
php artisan optimize:clear
if %errorlevel% neq 0 (
    echo    [X] Cache clear failed!
    exit /b 1
) else (
    echo    [OK] Cache cleared
)

echo.
echo 3. Checking routes...
php artisan route:list --columns=uri,name > nul 2>&1
if %errorlevel% neq 0 (
    echo    [X] Routes check failed!
    exit /b 1
) else (
    echo    [OK] Routes working
)

echo.
echo 4. Testing ad creation...
php artisan test:create-ad > test-result.txt 2>&1
if %errorlevel% neq 0 (
    echo    [X] Test command failed!
    type test-result.txt
    exit /b 1
) else (
    echo    [OK] Test command passed
)

echo.
echo ========================================
echo REFACTORING COMPLETED SUCCESSFULLY!
echo ========================================