@echo off
title Virtual Office - English Version
color 0E
chcp 65001 >nul 2>&1

echo.
echo ========================================
echo      VIRTUAL OFFICE 3.2 - EN
echo ========================================
echo.

:: Check dependencies
echo Checking dependencies...
where node >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Node.js is not installed!
    echo Please install Node.js to run the chat server.
    pause
    exit /b 1
)

where claude >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Claude is not installed!
    echo Please install Claude Code: npm install -g @anthropic/claude-cli
    pause
    exit /b 1
)

where python >nul 2>&1
if errorlevel 1 (
    echo [WARNING] Python is not installed!
    echo Some features will not be available.
    echo.
)

:: Set the base path
set BASE_PATH=C:\www.spa.com\.ai-team
cd /d %BASE_PATH%

:: Step 1: Clean old processes
echo [1/7] Cleaning old processes...
taskkill /F /FI "WINDOWTITLE eq *AI*Agent*" >nul 2>&1
taskkill /F /FI "WINDOWTITLE eq *Chat*Server*" >nul 2>&1
taskkill /F /FI "WINDOWTITLE eq *Virtual*Office*" >nul 2>&1
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8082') do (
    taskkill /F /PID %%a >nul 2>&1
)
timeout /t 2 >nul

:: Step 2: Initialize Virtual Office structure
echo [2/7] Initializing Virtual Office structure...
if not exist "virtual-office\inbox" mkdir "virtual-office\inbox"
if not exist "virtual-office\outbox" mkdir "virtual-office\outbox"
if not exist "virtual-office\tasks" mkdir "virtual-office\tasks"
if not exist "virtual-office\channels\general" mkdir "virtual-office\channels\general"
if not exist "virtual-office\channels\standup" mkdir "virtual-office\channels\standup"
if not exist "virtual-office\channels\help" mkdir "virtual-office\channels\help"
if not exist "virtual-office\metrics" mkdir "virtual-office\metrics"
if not exist "virtual-office\reports" mkdir "virtual-office\reports"

:: Create inbox folders for each agent
for %%A in (teamlead backend frontend qa devops) do (
    if not exist "virtual-office\inbox\%%A" mkdir "virtual-office\inbox\%%A"
    if not exist "virtual-office\outbox\%%A" mkdir "virtual-office\outbox\%%A"
)

:: Step 3: Clear chat for fresh start
echo [3/7] Preparing chat system...
echo [%time:~0,5%] [SYSTEM]: Virtual Office startup initiated > chat.md
echo.

:: Step 4: Start chat server (FIXED PATH)
echo [4/7] Starting chat server on port 8082...
start "Chat Server" /min cmd /c "node ai-team-server.cjs"
timeout /t 3 >nul

:: Step 5: Open dashboard
echo [5/7] Opening dashboard in browser...
start "" "http://localhost:8082/ai-team-dashboard-channels.html"
timeout /t 2 >nul

:: Step 6: Start AI agents with V2 launcher
echo [6/7] Starting AI agents with Virtual Office support...
echo.

:: Change to scripts directory for launching agents
cd scripts

:: Start TeamLead first with V2 launcher
echo   Starting TeamLead (Coordinator)...
start "TeamLead Agent" /min powershell -ExecutionPolicy Bypass -File "ai-agent-launcher-v2.ps1" -Role "TeamLead" -Mode "virtual-office"
timeout /t 3 >nul

:: Start other agents
echo   Starting Backend (Laravel Developer)...
start "Backend Agent" /min powershell -ExecutionPolicy Bypass -File "ai-agent-launcher-v2.ps1" -Role "Backend" -Mode "virtual-office"
timeout /t 2 >nul

echo   Starting Frontend (Vue.js Developer)...
start "Frontend Agent" /min powershell -ExecutionPolicy Bypass -File "ai-agent-launcher-v2.ps1" -Role "Frontend" -Mode "virtual-office"
timeout /t 2 >nul

echo   Starting QA (Test Engineer)...
start "QA Agent" /min powershell -ExecutionPolicy Bypass -File "ai-agent-launcher-v2.ps1" -Role "QA" -Mode "virtual-office"
timeout /t 2 >nul

echo   Starting DevOps (Infrastructure)...
start "DevOps Agent" /min powershell -ExecutionPolicy Bypass -File "ai-agent-launcher-v2.ps1" -Role "DevOps" -Mode "virtual-office"
timeout /t 3 >nul

:: Return to base directory
cd ..

:: Step 7: Start automation services
echo [7/7] Starting automation services...

:: Start channel automators (from scripts directory)
cd scripts
echo   Starting channel automators...
start "TeamLead Channels" /min powershell -ExecutionPolicy Bypass -File "channel-automator.ps1" -Action monitor -Agent teamlead
start "Backend Channels" /min powershell -ExecutionPolicy Bypass -File "channel-automator.ps1" -Action monitor -Agent backend
start "Frontend Channels" /min powershell -ExecutionPolicy Bypass -File "channel-automator.ps1" -Action monitor -Agent frontend
start "QA Channels" /min powershell -ExecutionPolicy Bypass -File "channel-automator.ps1" -Action monitor -Agent qa
start "DevOps Channels" /min powershell -ExecutionPolicy Bypass -File "channel-automator.ps1" -Action monitor -Agent devops
timeout /t 2 >nul

:: Start metrics watchers
echo   Starting metrics auto-updaters...
start "Backend Metrics" /min powershell -ExecutionPolicy Bypass -File "agent-action-wrapper.ps1" -Agent backend -Action watch
start "Frontend Metrics" /min powershell -ExecutionPolicy Bypass -File "agent-action-wrapper.ps1" -Agent frontend -Action watch
start "QA Metrics" /min powershell -ExecutionPolicy Bypass -File "agent-action-wrapper.ps1" -Agent qa -Action watch
start "DevOps Metrics" /min powershell -ExecutionPolicy Bypass -File "agent-action-wrapper.ps1" -Agent devops -Action watch

:: Return to base directory
cd ..

echo.
echo ========================================
echo     VIRTUAL OFFICE STARTED!
echo ========================================
echo.
echo AVAILABLE INTERFACES:
echo.
echo   1. Web Dashboard:     http://localhost:8082
echo   2. CEO Control Panel: python virtual-office\ceo_interface.py
echo   3. System Monitor:    python virtual-office\monitor.py
echo.
echo TEAM (5 agents):
echo   - TeamLead - Coordinator
echo   - Backend - Laravel Developer
echo   - Frontend - Vue.js Developer
echo   - QA - Test Engineer
echo   - DevOps - Infrastructure
echo.
echo QUICK COMMANDS:
echo   Create task:  powershell scripts\task-manager.ps1 -Action create -Title "Task" -Assignee backend
echo   Send message: powershell scripts\message-router.ps1 -Action send -From ceo -To backend -Message "Text"
echo   View tasks:   powershell scripts\task-manager.ps1 -Action list
echo.
echo ========================================
echo.

:: Ask user what to do next
echo What would you like to do?
echo.
echo   1. Open CEO Interface (Python)
echo   2. View System Monitor
echo   3. Just keep everything running
echo   0. Exit
echo.

set /p choice="Select option: "

if "%choice%"=="1" (
    echo.
    echo Starting CEO Interface...
    python virtual-office\ceo_interface_en.py
) else if "%choice%"=="2" (
    echo.
    echo Opening System Monitor in new window...
    start "System Monitor" cmd /c "python virtual-office\monitor.py"
) else if "%choice%"=="0" (
    echo.
    echo Shutting down Virtual Office...
    taskkill /F /FI "WINDOWTITLE eq *Virtual*Office*" >nul 2>&1
    taskkill /F /FI "WINDOWTITLE eq *Agent*" >nul 2>&1
    taskkill /F /FI "WINDOWTITLE eq *Monitor*" >nul 2>&1
    taskkill /F /FI "WINDOWTITLE eq *Chat*Server*" >nul 2>&1
    exit
) else (
    echo.
    echo Virtual Office is running in background.
    echo You can close this window safely.
    echo.
    pause
)