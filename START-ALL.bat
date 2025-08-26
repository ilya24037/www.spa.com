@echo off
title AI Team Complete Startup
color 0A

echo.
echo ========================================
echo      AI TEAM COMPLETE STARTUP SYSTEM
echo           SPA Platform Project
echo ========================================
echo.

:: Step 1: Kill old processes
echo [1/4] Cleaning up old processes...
taskkill /F /IM powershell.exe >nul 2>&1
taskkill /F /FI "WINDOWTITLE eq *Chat*" >nul 2>&1
taskkill /F /FI "WINDOWTITLE eq *claude*" >nul 2>&1
for /l %%i in (8080,1,8090) do (
    for /f "tokens=5" %%a in ('netstat -aon ^| findstr :%%i') do (
        taskkill /F /PID %%a >nul 2>&1
    )
)
timeout /t 2 >nul

:: Step 2: Start Chat Server
echo [2/4] Starting Chat Server on port 8082...
start "Chat Server" powershell -WindowStyle Minimized -NoExit -ExecutionPolicy Bypass -Command "& { Write-Host 'AI Team Chat Server Started' -ForegroundColor Green; & 'C:\www.spa.com\chat-server-clean.ps1' -Port 8082 }"
timeout /t 3 >nul

:: Step 3: Open Dashboard
echo [3/4] Opening Dashboard in browser...
start "" "http://localhost:8082/ai-team-dashboard-channels.html"
timeout /t 2 >nul

:: Step 4: Start AI Agents via PowerShell
echo [4/4] Starting AI Team agents via manager...
echo.
powershell -Command "& {& 'C:\www.spa.com\ai-agent-manager.ps1' -Action 'start-all'}" >nul 2>&1
echo AI Team started in background!
echo.
echo NOTE: AI agents are running in background mode.
echo They will respond in chat when you mention them.

echo.
echo ========================================
echo         ALL SYSTEMS STARTED!
echo ========================================
echo.
echo Chat Server: http://localhost:8082
echo AI Team: TeamLead, Backend, Frontend, DevOps
echo Dashboard opened in browser
echo.
echo Ready to work! Write in chat:
echo   @teamlead [your task]
echo   @all [team task]
echo.
echo Press any key to view system status...
pause >nul

:: Show status
echo.
echo RUNNING PROCESSES:
echo ==================
tasklist | findstr "powershell claude cmd" | findstr /V "findstr"
echo.
echo To stop everything, close this window or press Ctrl+C
echo.
pause