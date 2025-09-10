@echo off
title AI Team Quick Start
color 0A

echo.
echo ========================================
echo    AI TEAM - QUICK START
echo ========================================
echo.

:: Just start the chat server and open browser
echo Starting Chat Server...
start "Chat Server" /min powershell -NoExit -ExecutionPolicy Bypass -File "chat-server-clean.ps1" -Port 8082

timeout /t 2 >nul

:: Open dashboard
echo Opening Dashboard...
start "" "http://localhost:8082/ai-team-dashboard-channels.html"

echo.
echo ========================================
echo    CHAT SERVER STARTED!
echo ========================================
echo.
echo Dashboard: http://localhost:8082
echo.
echo TO START AI TEAM:
echo   Click the green "Start AI Team" button in the browser
echo   OR write "/start-all" in chat
echo.
echo The AI agents will work in BACKGROUND mode.
echo You won't see their windows, but they will respond in chat!
echo.
pause