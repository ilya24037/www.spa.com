@echo off
title Virtual Office - Test Components
color 0A

echo ========================================
echo     VIRTUAL OFFICE - TEST SCRIPT
echo ========================================
echo.

:: Test 1: Check dependencies
echo [TEST 1] Checking dependencies...
echo.

echo Checking Node.js...
where node >nul 2>&1
if errorlevel 1 (
    echo   [FAIL] Node.js not found
) else (
    for /f "tokens=*" %%i in ('node --version') do echo   [OK] Node.js: %%i
)

echo Checking Claude...
where claude >nul 2>&1
if errorlevel 1 (
    echo   [FAIL] Claude not found
) else (
    for /f "tokens=*" %%i in ('claude --version 2^>nul') do echo   [OK] Claude: %%i
)

echo Checking Python...
where python >nul 2>&1
if errorlevel 1 (
    echo   [WARN] Python not found (optional)
) else (
    for /f "tokens=*" %%i in ('python --version') do echo   [OK] Python: %%i
)

echo.
echo ========================================
echo.

:: Test 2: Check file structure
echo [TEST 2] Checking file structure...
echo.

set BASE_PATH=C:\www.spa.com\.ai-team

if exist "%BASE_PATH%\ai-team-server.cjs" (
    echo   [OK] Chat server found: ai-team-server.cjs
) else (
    echo   [FAIL] Chat server not found!
)

if exist "%BASE_PATH%\scripts\ai-agent-launcher-v2.ps1" (
    echo   [OK] Agent launcher V2 found
) else (
    echo   [FAIL] Agent launcher V2 not found!
)

if exist "%BASE_PATH%\virtual-office" (
    echo   [OK] Virtual Office directory exists
) else (
    echo   [WARN] Virtual Office directory not found - will be created
)

echo.
echo ========================================
echo.

:: Test 3: Test chat server
echo [TEST 3] Testing chat server...
echo.

:: Kill any existing server on port 8082
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8082') do (
    echo   Killing existing process on port 8082...
    taskkill /F /PID %%a >nul 2>&1
)

:: Try to start the server
cd /d %BASE_PATH%
echo   Starting server...
start "Test Server" /min cmd /c "node ai-team-server.cjs"
timeout /t 3 >nul

:: Check if server is running
netstat -an | findstr :8082 | findstr LISTENING >nul
if errorlevel 1 (
    echo   [FAIL] Server not listening on port 8082
) else (
    echo   [OK] Server is running on port 8082
)

echo.
echo ========================================
echo.

:: Test 4: Test PowerShell scripts
echo [TEST 4] Testing PowerShell scripts...
echo.

cd scripts

:: Test if PowerShell can run scripts
powershell -Command "Get-ExecutionPolicy" >nul 2>&1
if errorlevel 1 (
    echo   [FAIL] PowerShell not working properly
) else (
    echo   [OK] PowerShell is working
)

:: Check if we can bypass execution policy
powershell -ExecutionPolicy Bypass -Command "Write-Host 'Test'" >nul 2>&1
if errorlevel 1 (
    echo   [FAIL] Cannot bypass PowerShell execution policy
) else (
    echo   [OK] Can bypass execution policy
)

cd ..

echo.
echo ========================================
echo.

:: Test 5: Quick agent test
echo [TEST 5] Quick agent command test...
echo.

echo Testing Claude command format...
claude --help >nul 2>&1
if errorlevel 1 (
    echo   [FAIL] Claude command not working
) else (
    echo   [OK] Claude command works
)

echo.
echo ========================================
echo     TEST RESULTS SUMMARY
echo ========================================
echo.
echo If all tests passed [OK], you can run:
echo   START-VIRTUAL-OFFICE-FIX.bat
echo.
echo If you see [FAIL] errors, fix them first:
echo   - Node.js: Install from https://nodejs.org
echo   - Claude: npm install -g @anthropic/claude-cli
echo   - Python: Install from https://python.org (optional)
echo.
echo Press any key to close test window...
pause >nul

:: Clean up test server
taskkill /F /FI "WINDOWTITLE eq Test Server" >nul 2>&1