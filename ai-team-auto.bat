@echo off
title AI Team Auto Launcher - SPA Platform
color 0A
chcp 65001 >nul

echo.
echo ========================================
echo   AI TEAM AUTO LAUNCHER - SPA PLATFORM
echo ========================================
echo.

:: Check folders
if not exist ".ai-team" mkdir ".ai-team"
if not exist ".ai-team\backend" mkdir ".ai-team\backend"
if not exist ".ai-team\frontend" mkdir ".ai-team\frontend"
if not exist ".ai-team\devops" mkdir ".ai-team\devops"

:: Add startup message
echo [%TIME:~0,5%] [SYSTEM]: AI Team starting up with auto roles... >> .ai-team\chat.md

:: Start Backend Developer with role
echo Starting Backend Developer with role...
start "AI Backend" cmd /k claude --dangerously-skip-permissions "You are Backend Developer. Read CLAUDE.md in current folder C:\www.spa.com\.ai-team\backend\CLAUDE.md for instructions. Monitor C:\www.spa.com\.ai-team\chat.md every 30 seconds. Execute tasks with @backend or @all. Write to chat [HH:MM] [BACKEND]: message"

timeout /t 3 >nul

:: Start Frontend Developer with role  
echo Starting Frontend Developer with role...
start "AI Frontend" cmd /k claude --dangerously-skip-permissions "You are Frontend Developer. Read C:\www.spa.com\.ai-team\frontend\CLAUDE.md for instructions. Monitor C:\www.spa.com\.ai-team\chat.md every 30 seconds. Execute tasks with @frontend or @all. Write to chat [HH:MM] [FRONTEND]: message"

timeout /t 3 >nul

:: Start DevOps Engineer with role
echo Starting DevOps Engineer with role...
start "AI DevOps" cmd /k claude --dangerously-skip-permissions "You are DevOps Engineer. Read C:\www.spa.com\.ai-team\devops\CLAUDE.md for instructions. Monitor C:\www.spa.com\.ai-team\chat.md every 30 seconds. Execute tasks with @devops or @all. Write to chat [HH:MM] [DEVOPS]: message"

timeout /t 3 >nul

:: Start Chat Monitor
echo Starting Chat Monitor...
start "Chat Monitor" powershell -NoExit -Command "cd 'C:\www.spa.com'; Write-Host 'CHAT MONITOR ACTIVE' -ForegroundColor Magenta; Write-Host '===================='; Write-Host ''; Get-Content '.ai-team\chat.md' -Wait -Tail 20 -Encoding UTF8"

timeout /t 2 >nul

:: Start Control Center
echo Starting Control Center...
start "Control Center" powershell -NoExit -File "C:\www.spa.com\control-center.ps1"

echo.
echo ========================================
echo    AI TEAM STARTED WITH AUTO ROLES!
echo ========================================
echo.
echo 5 windows opened:
echo   1. Backend Developer - Auto role applied
echo   2. Frontend Developer - Auto role applied  
echo   3. DevOps Engineer - Auto role applied
echo   4. Chat Monitor - Shows chat
echo   5. Control Center - Send commands
echo.
echo Each Claude has:
echo - Its own CLAUDE.md with instructions
echo - Auto monitoring of chat.md
echo - Ready to work!
echo.
echo Use Control Center commands:
echo   msg-all "your task"
echo   msg-back "backend task"
echo   msg-front "frontend task"
echo   msg-dev "devops task"
echo.
pause