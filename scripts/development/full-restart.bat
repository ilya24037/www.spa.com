@echo off
title AI Team Chat Full Restart
color 0A

echo.
echo ========================================
echo    FULL RESTART - AI TEAM CHAT
echo ========================================
echo.

:: Kill ALL PowerShell processes (careful!)
echo Stopping ALL PowerShell processes...
taskkill /F /IM powershell.exe >nul 2>&1
timeout /t 2 >nul

:: Kill any process using ports
echo Freeing ports 8080-8090...
for /l %%i in (8080,1,8090) do (
    for /f "tokens=5" %%a in ('netstat -aon ^| findstr :%%i') do (
        taskkill /F /PID %%a >nul 2>&1
    )
)
timeout /t 2 >nul

:: Find free port
set PORT=8082
echo.
echo Using port %PORT%...
echo.

:: Start server on new port
echo Starting chat server on port %PORT%...
start "Chat Server %PORT%" powershell -NoExit -ExecutionPolicy Bypass -Command "& { Write-Host 'AI Team Chat Server' -ForegroundColor Cyan; Write-Host 'Port: %PORT%' -ForegroundColor Yellow; & 'C:\www.spa.com\chat-server-clean.ps1' -Port %PORT% }"

timeout /t 3 >nul

:: Open browser with new dashboard
echo Opening dashboard in browser...
start "" "http://localhost:%PORT%/ai-team-dashboard-channels.html"

echo.
echo ========================================
echo    SERVER STARTED ON PORT %PORT%
echo ========================================
echo.
echo Dashboard with channels: http://localhost:%PORT%
echo.
echo If port %PORT% is busy, edit this file and change PORT to 8083, 8084, etc.
echo.
pause