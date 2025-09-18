@echo off
title Simple AI Team Startup
color 0B

echo ========================================
echo   SIMPLE AI TEAM LAUNCHER (NO VIRTUAL OFFICE)
echo ========================================
echo.
echo This is a simplified version without Virtual Office features.
echo It will just start the chat server and basic agents.
echo.

:: Kill old processes
echo Cleaning old processes...
taskkill /F /FI "WINDOWTITLE eq *Chat*Server*" >nul 2>&1
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8082') do (
    taskkill /F /PID %%a >nul 2>&1
)
timeout /t 2 >nul

:: Move to ai-team directory
cd /d C:\www.spa.com\.ai-team

:: Clear chat
echo Preparing chat...
echo [%time:~0,5%] [SYSTEM]: Simple AI Team started > chat.md
echo.

:: Start chat server
echo Starting chat server...
start "Chat Server" /min cmd /c "node ai-team-server.cjs"
timeout /t 3 >nul

:: Open dashboard
echo Opening dashboard...
start "" "http://localhost:8082/ai-team-dashboard-channels.html"
echo.

:: Show instructions
echo ========================================
echo     CHAT SERVER STARTED!
echo ========================================
echo.
echo The chat server is running at: http://localhost:8082
echo.
echo To start agents manually, open new terminals and run:
echo   cd C:\www.spa.com\.ai-team
echo   claude --dangerously-skip-permissions "You are Backend. Monitor chat.md"
echo.
echo Or use the scripts in the scripts folder:
echo   cd scripts
echo   powershell -ExecutionPolicy Bypass -File ai-agent-launcher.ps1 -Role Backend
echo.
echo Press any key to exit (server will keep running)...
pause >nul