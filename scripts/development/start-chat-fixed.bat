@echo off
title AI Team Chat UTF8
color 0A
chcp 65001 >nul

echo.
echo ========================================
echo    AI TEAM CHAT - UTF8 FIXED
echo ========================================
echo.

:: Stop old server if running
echo Stopping old processes...
taskkill /F /FI "WINDOWTITLE eq Chat Server*" >nul 2>&1
timeout /t 2 >nul

:: Start new server with UTF-8
echo Starting server with UTF-8 support...
start "Chat Server UTF8" powershell -NoExit -ExecutionPolicy Bypass -File "chat-server-utf8.ps1" -Port 8080

timeout /t 3 >nul

:: Open in browser
echo Opening chat in browser...
start "" "http://localhost:8080"

echo.
echo ========================================
echo    SERVER STARTED SUCCESSFULLY!
echo ========================================
echo.
echo UTF-8 encoding is active!
echo Russian text should display correctly now.
echo.
echo Chat available at: http://localhost:8080
echo.
pause