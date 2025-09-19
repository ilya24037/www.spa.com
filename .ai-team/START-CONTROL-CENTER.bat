@echo off
title Virtual Office Control Center
color 0A

echo ============================================================
echo        VIRTUAL OFFICE CONTROL CENTER
echo           Complete Management System
echo ============================================================
echo.

:: Check Node.js
where node >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Node.js not found!
    echo Please install Node.js from https://nodejs.org
    pause
    exit /b 1
)

:: Install dependencies if needed
if not exist "node_modules\express" (
    echo Installing dependencies...
    call npm install express cors --save
    echo.
)

:: Start API Server
echo Starting Control Center API Server...
start /min cmd /c "node control-center-api.js"
timeout /t 2 /nobreak >nul

:: Open Control Center in browser
echo Opening Control Center in browser...
start http://localhost:8083/ai-team-control-center.html

echo.
echo ============================================================
echo           CONTROL CENTER STARTED!
echo ============================================================
echo.
echo Control Center: http://localhost:8083/ai-team-control-center.html
echo API Server: http://localhost:8083/api/system/status
echo.
echo Features:
echo  - Start/Stop Virtual Office with one click
echo  - Real-time agent monitoring
echo  - Multi-channel chat system
echo  - CEO dashboard with quick actions
echo  - Task management
echo  - Knowledge base integration
echo  - Metrics and KPIs
echo.
echo Press any key to stop the Control Center...
pause >nul

:: Stop servers
taskkill /F /IM node.exe /T >nul 2>&1
echo.
echo Control Center stopped.
pause