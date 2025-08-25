@echo off
title AI Team Launcher
color 0A
cls

echo ========================================
echo     AI TEAM LAUNCHER - SPA PLATFORM
echo ========================================
echo.

REM Chat Monitor Window
echo Starting Chat Monitor...
start "Chat Monitor" cmd /k "powershell -NoExit -Command ""chcp 65001; cd C:\www.spa.com; Write-Host 'CHAT MONITOR' -ForegroundColor Magenta; Get-Content .ai-team\chat.md -Wait -Tail 20"""

timeout /t 2 >nul

REM Control Center Window  
echo Starting Control Center...
start "Control Center" cmd /k "powershell -NoExit -File C:\www.spa.com\control-center.ps1"

echo.
echo AI Team windows opened!
echo Use Control Center to send commands.
echo.
pause