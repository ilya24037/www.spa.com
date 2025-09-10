@echo off
title AI Team Chat UI Launcher
color 0A
chcp 65001 >nul

echo.
echo ========================================
echo    AI TEAM CHAT UI - SPA PLATFORM
echo ========================================
echo.

:: Check if .ai-team folder exists
if not exist ".ai-team" (
    mkdir ".ai-team"
    echo Created .ai-team folder
)

:: Initialize chat.md if not exists
if not exist ".ai-team\chat.md" (
    echo # AI Team Chat - SPA Platform > ".ai-team\chat.md"
    echo. >> ".ai-team\chat.md"
    echo [%TIME:~0,5%] [SYSTEM]: Chat UI initialized >> ".ai-team\chat.md"
    echo Created chat.md file
)

echo Starting services...
echo.

:: Start Chat Server
echo 1. Starting Chat Server on port 8080...
start "Chat Server" powershell -NoExit -File "chat-server-v2.ps1" -Port 8080

timeout /t 3 >nul

:: Open Chat UI in browser
echo 2. Opening Chat UI in browser...
start "" "http://localhost:8080"

echo.
echo 3. Starting AI Team agents...
echo.

:: Start AI agents if needed
choice /C YN /T 5 /D N /M "Start AI agents too"
if errorlevel 2 goto SkipAgents
if errorlevel 1 goto StartAgents

:StartAgents
echo Starting AI Team...
call ai-team-auto.bat
goto Done

:SkipAgents
echo Skipping AI agents start (you can start them manually)
goto Done

:Done
echo.
echo ========================================
echo    CHAT UI STARTED SUCCESSFULLY!
echo ========================================
echo.
echo Services running:
echo   - Chat Server: http://localhost:8080
echo   - Chat UI: Open in your browser
echo.
echo How to use:
echo   1. Type messages in the browser chat
echo   2. Use @backend, @frontend, @devops to mention roles
echo   3. Use /sync, /status commands
echo   4. AI agents will respond in real-time
echo.
echo Press any key to view chat monitor...
pause >nul

:: Start Chat Monitor
powershell -NoExit -Command "Write-Host 'CHAT MONITOR' -ForegroundColor Cyan; Write-Host '============' -ForegroundColor Cyan; Get-Content '.ai-team\chat.md' -Wait -Tail 20 -Encoding UTF8"