@echo off
title Test Enhanced Virtual Office

echo ============================================================
echo           TESTING ENHANCED VIRTUAL OFFICE
echo ============================================================
echo.

:: Test 1: Check directories
echo [TEST 1] Checking directory structure...
if exist "virtual-office\inbox" (
    echo [OK] Inbox directory exists
) else (
    echo [FAIL] Inbox directory missing
)

if exist "teamlead\CLAUDE_ENHANCED.md" (
    echo [OK] TeamLead enhanced instructions found
) else (
    echo [FAIL] TeamLead instructions missing
)

if exist "backend\CLAUDE_ENHANCED.md" (
    echo [OK] Backend enhanced instructions found
) else (
    echo [FAIL] Backend instructions missing
)
echo.

:: Test 2: Check Claude
echo [TEST 2] Checking Claude installation...
where claude >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Claude is installed
    claude --version
) else (
    echo [FAIL] Claude not found
)
echo.

:: Test 3: Check PowerShell
echo [TEST 3] Checking PowerShell...
powershell -Command "Write-Host '[OK] PowerShell is working' -ForegroundColor Green"
echo.

:: Test 4: Test message sending
echo [TEST 4] Testing message system...
echo Creating test message...

:: Create test message
echo {"from":"test","to":"teamlead","message":"Test message","timestamp":"%date% %time%"} > virtual-office\inbox\teamlead\test.json

if exist "virtual-office\inbox\teamlead\test.json" (
    echo [OK] Test message created successfully
    del "virtual-office\inbox\teamlead\test.json" 2>nul
) else (
    echo [FAIL] Could not create test message
)
echo.

:: Test 5: Check Node.js
echo [TEST 5] Checking Node.js...
node --version >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Node.js is installed
    node --version
) else (
    echo [FAIL] Node.js not found
)
echo.

:: Test 6: Check knowledge files
echo [TEST 6] Checking knowledge integration...
if exist "..\docs\KNOWLEDGE_MAP_2025.md" (
    echo [OK] Knowledge map found
) else (
    echo [WARNING] Knowledge map not found
)

if exist "..\docs\LESSONS" (
    echo [OK] LESSONS directory found
) else (
    echo [WARNING] LESSONS directory not found
)
echo.

:: Results
echo ============================================================
echo                      TEST RESULTS
echo ============================================================
echo.
echo If all tests passed, you can run:
echo START-ENHANCED-OFFICE-FIXED.bat
echo.
echo If some tests failed, please:
echo 1. Install missing components
echo 2. Check file permissions
echo 3. Verify directory structure
echo.
pause