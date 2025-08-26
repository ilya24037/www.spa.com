@echo off
title TeamLead System
color 0A

echo.
echo ========================================
echo       TEAMLEAD DUAL MODE SYSTEM
echo ========================================
echo.

:: Step 1: Clean old processes
echo [1/3] Cleaning old processes...
taskkill /F /FI "WINDOWTITLE eq *Chat*Server*" >nul 2>&1
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8082') do (
    taskkill /F /PID %%a >nul 2>&1
)
timeout /t 2 >nul

:: Step 2: Start chat server
echo [2/3] Starting chat server...
start "Chat Server" /min powershell -WindowStyle Minimized -ExecutionPolicy Bypass -File "chat-server-clean.ps1" -Port 8082
timeout /t 3 >nul

:: Step 3: Open dashboard
echo [3/3] Opening dashboard...
start "" "http://localhost:8082/ai-team-dashboard-channels.html"
echo.

echo ========================================
echo     CHAT SYSTEM READY!
echo ========================================
echo.
echo Now start TeamLead in another window:
echo   Run: START-TEAMLEAD-DUAL.bat
echo.
echo TeamLead will work in TWO modes:
echo   1. TERMINAL - direct communication with you
echo   2. CHAT - responds to @teamlead in browser
echo.
echo You can:
echo   - Show screenshots in terminal
echo   - Write @teamlead in chat
echo   - TeamLead coordinates both channels
echo.
pause