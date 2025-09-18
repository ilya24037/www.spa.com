@echo off
title AI Team Final Startup
color 0A

echo.
echo ========================================
echo         AI TEAM STARTUP SYSTEM
echo              FINAL VERSION
echo ========================================
echo.

:: Step 1: Clean old processes
echo [1/5] Cleaning old processes...
taskkill /F /FI "WINDOWTITLE eq *AI*Agent*" >nul 2>&1
taskkill /F /FI "WINDOWTITLE eq *Chat*Server*" >nul 2>&1
taskkill /F /FI "WINDOWTITLE eq *claude*" >nul 2>&1
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8082') do (
    taskkill /F /PID %%a >nul 2>&1
)
timeout /t 2 >nul

:: Step 2: Clear chat for fresh start
echo [2/5] Preparing chat system...
cd ..
echo [%time:~0,5%] [SYSTEM]: AI Team startup initiated > chat.md
cd scripts
echo.

:: Step 3: Start chat server
echo [3/5] Starting chat server on port 8082...
cd ..
start "Chat Server" /min cmd /c "node ai-team-server.cjs"
cd scripts
timeout /t 3 >nul

:: Step 4: Open dashboard
echo [4/5] Opening dashboard in browser...
start "" "http://localhost:8082/ai-team-dashboard-channels.html"
timeout /t 2 >nul

:: Step 5: Start agents using new launcher
echo [5/5] Starting AI agents...
echo.

:: Start TeamLead first
echo   Starting TeamLead...
start "TeamLead Launcher" /min powershell -ExecutionPolicy Bypass -File "ai-agent-launcher-v2.ps1" -Role "TeamLead"
timeout /t 3 >nul

:: Start other agents
echo   Starting Backend...
start "Backend Launcher" /min powershell -ExecutionPolicy Bypass -File "ai-agent-launcher-v2.ps1" -Role "Backend"
timeout /t 2 >nul

echo   Starting Frontend...
start "Frontend Launcher" /min powershell -ExecutionPolicy Bypass -File "ai-agent-launcher-v2.ps1" -Role "Frontend"
timeout /t 2 >nul

echo   Starting QA...
start "QA Launcher" /min powershell -ExecutionPolicy Bypass -File "ai-agent-launcher-v2.ps1" -Role "QA"
timeout /t 2 >nul

echo   Starting DevOps...
start "DevOps Launcher" /min powershell -ExecutionPolicy Bypass -File "ai-agent-launcher-v2.ps1" -Role "DevOps"
timeout /t 2 >nul

:: Final message
echo.
echo ========================================
echo     AI TEAM STARTED SUCCESSFULLY!
echo ========================================
echo.
echo Chat: http://localhost:8082
echo Team: TeamLead, Backend, Frontend, QA, DevOps
echo.
echo Test the system:
echo   1. Go to the browser dashboard
echo   2. Type: @all check connection
echo   3. Wait for agents to respond
echo.
echo If agents don't respond within 30 seconds:
echo   - Run TEST-AGENTS.bat to debug
echo   - Check if Claude is installed: claude --version
echo.
pause