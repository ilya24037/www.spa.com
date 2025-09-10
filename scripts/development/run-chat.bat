@echo off
title AI Team Chat
color 0A

echo.
echo ========================================
echo    AI TEAM CHAT SERVER
echo ========================================
echo.

:: Kill old processes
taskkill /F /FI "WINDOWTITLE eq *Chat*" >nul 2>&1
timeout /t 1 >nul

:: Start clean server
echo Starting chat server...
start "Chat Server" powershell -NoExit -ExecutionPolicy Bypass -File "chat-server-clean.ps1" -Port 8080

timeout /t 3 >nul

:: Open browser
start "" "http://localhost:8080"

echo.
echo Server is running at: http://localhost:8080
echo.
pause