@echo off
title Virtual Office Startup System
color 0E

echo.
echo ========================================
echo      VIRTUAL OFFICE - AI TEAM 3.0
echo ========================================
echo.

:: Check Python installation
python --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Python is not installed!
    echo Please install Python 3.8+ to use Virtual Office features.
    echo.
    echo Starting basic AI Team without Virtual Office...
    timeout /t 3 >nul
    cd scripts
    call START-AI-TEAM-FINAL.bat
    exit
)

echo [1/7] Initializing Virtual Office structure...
cd scripts
powershell -ExecutionPolicy Bypass -File "create-virtual-office.ps1" >nul 2>&1
cd ..

echo [2/7] Starting Chat Server...
start "Chat Server" /min cmd /c "node ai-team-server.cjs"
timeout /t 3 >nul

echo [3/7] Opening Dashboard...
start "" "http://localhost:8082/ai-team-dashboard-channels.html"
timeout /t 2 >nul

echo [4/7] Starting AI Agents with Virtual Office support...
echo.

:: Start all agents with V2 launcher (auto-reads prompts)
echo   Starting TeamLead (V2 with auto-prompts)...
start "TeamLead Agent" /min powershell -ExecutionPolicy Bypass -File "scripts\ai-agent-launcher-v2.ps1" -Role "TeamLead" -Mode "virtual-office"
timeout /t 2 >nul

echo   Starting Backend (V2 with auto-prompts)...
start "Backend Agent" /min powershell -ExecutionPolicy Bypass -File "scripts\ai-agent-launcher-v2.ps1" -Role "Backend" -Mode "virtual-office"
timeout /t 2 >nul

echo   Starting Frontend (V2 with auto-prompts)...
start "Frontend Agent" /min powershell -ExecutionPolicy Bypass -File "scripts\ai-agent-launcher-v2.ps1" -Role "Frontend" -Mode "virtual-office"
timeout /t 2 >nul

echo   Starting QA (V2 with auto-prompts)...
start "QA Agent" /min powershell -ExecutionPolicy Bypass -File "scripts\ai-agent-launcher-v2.ps1" -Role "QA" -Mode "virtual-office"
timeout /t 2 >nul

echo   Starting DevOps (V2 with auto-prompts)...
start "DevOps Agent" /min powershell -ExecutionPolicy Bypass -File "scripts\ai-agent-launcher-v2.ps1" -Role "DevOps" -Mode "virtual-office"
timeout /t 3 >nul

echo.
echo [4.5/7] Starting automation services...

:: Start channel automators for each agent
echo   Starting channel automators...
start "TeamLead Channels" /min powershell -ExecutionPolicy Bypass -File "scripts\channel-automator.ps1" -Action monitor -Agent teamlead
start "Backend Channels" /min powershell -ExecutionPolicy Bypass -File "scripts\channel-automator.ps1" -Action monitor -Agent backend
start "Frontend Channels" /min powershell -ExecutionPolicy Bypass -File "scripts\channel-automator.ps1" -Action monitor -Agent frontend
start "QA Channels" /min powershell -ExecutionPolicy Bypass -File "scripts\channel-automator.ps1" -Action monitor -Agent qa
start "DevOps Channels" /min powershell -ExecutionPolicy Bypass -File "scripts\channel-automator.ps1" -Action monitor -Agent devops
timeout /t 2 >nul

:: Start metrics watchers
echo   Starting metrics auto-updaters...
start "Backend Metrics" /min powershell -ExecutionPolicy Bypass -File "scripts\agent-action-wrapper.ps1" -Agent backend -Action watch
start "Frontend Metrics" /min powershell -ExecutionPolicy Bypass -File "scripts\agent-action-wrapper.ps1" -Agent frontend -Action watch
start "QA Metrics" /min powershell -ExecutionPolicy Bypass -File "scripts\agent-action-wrapper.ps1" -Agent qa -Action watch
start "DevOps Metrics" /min powershell -ExecutionPolicy Bypass -File "scripts\agent-action-wrapper.ps1" -Agent devops -Action watch
timeout /t 2 >nul

echo.
echo [5/7] Starting Virtual Office Monitor...
start "VO Monitor" /min cmd /c "python virtual-office\monitor.py"
timeout /t 2 >nul

echo [6/7] Starting Message Router...
start "Message Router" /min powershell -ExecutionPolicy Bypass -File "scripts\message-router.ps1" -Action monitor
timeout /t 2 >nul

echo [7/7] CEO Interface ready...
echo.

:: Show options
echo ========================================
echo     VIRTUAL OFFICE STARTED!
echo ========================================
echo.
echo 🏢 AVAILABLE INTERFACES:
echo.
echo   1. Web Dashboard:     http://localhost:8082
echo   2. CEO Control Panel: python virtual-office\ceo_interface.py
echo   3. System Monitor:    python virtual-office\monitor.py
echo   4. Task Manager:      powershell scripts\task-manager.ps1
echo   5. Message Router:    powershell scripts\message-router.ps1
echo.
echo 👥 TEAM (5 agents):
echo   • TeamLead - Coordinator
echo   • Backend - Laravel Developer
echo   • Frontend - Vue.js Developer
echo   • QA - Test Engineer (NEW!)
echo   • DevOps - Infrastructure
echo.
echo 📋 QUICK COMMANDS:
echo   Create task:  powershell scripts\task-manager.ps1 -Action create -Title "Task name" -Assignee backend
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
    python virtual-office\ceo_interface.py
) else if "%choice%"=="2" (
    echo.
    echo Opening System Monitor in new window...
    start "System Monitor" cmd /c "python virtual-office\monitor.py"
) else if "%choice%"=="0" (
    echo.
    echo Shutting down Virtual Office...
    taskkill /F /FI "WINDOWTITLE eq *Virtual Office*" >nul 2>&1
    taskkill /F /FI "WINDOWTITLE eq *Agent*" >nul 2>&1
    taskkill /F /FI "WINDOWTITLE eq *Monitor*" >nul 2>&1
    exit
) else (
    echo.
    echo Virtual Office is running in background.
    echo You can close this window safely.
    echo.
    pause
)