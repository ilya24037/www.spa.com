@echo off
title AI Team Chat Restart
color 0A

echo.
echo ========================================
echo    RESTARTING AI TEAM CHAT
echo ========================================
echo.

:: Kill all old processes
echo Stopping all old servers...
taskkill /F /FI "WINDOWTITLE eq *Chat*" >nul 2>&1
taskkill /F /FI "WINDOWTITLE eq *chat*" >nul 2>&1
taskkill /F /IM powershell.exe /FI "WINDOWTITLE eq *" >nul 2>&1
timeout /t 2 >nul

:: Try to free port 8080
echo Freeing port 8080...
for /f "tokens=5" %%a in ('netstat -aon ^| find ":8080"') do taskkill /F /PID %%a >nul 2>&1
timeout /t 1 >nul

:: Start on port 8081 as backup
echo Starting chat server on port 8081...
start "Chat Server 8081" powershell -NoExit -ExecutionPolicy Bypass -Command "& { $port = 8081; Write-Host 'Starting on port' $port -ForegroundColor Cyan; & 'C:\www.spa.com\chat-server-clean.ps1' -Port $port }"

timeout /t 3 >nul

:: Open browser
echo Opening dashboard in browser...
start "" "http://localhost:8081"

echo.
echo ========================================
echo    SERVER RESTARTED ON PORT 8081
echo ========================================
echo.
echo Dashboard: http://localhost:8081
echo.
pause