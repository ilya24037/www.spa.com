@echo off
title AI Team Launcher - SPA Platform
color 0A
chcp 65001 >nul

echo.
echo ========================================
echo     AI TEAM LAUNCHER - SPA PLATFORM
echo ========================================
echo.

:: Check and create folders
if not exist ".ai-team" mkdir ".ai-team"
if not exist ".ai-team\backend" mkdir ".ai-team\backend"
if not exist ".ai-team\frontend" mkdir ".ai-team\frontend"
if not exist ".ai-team\devops" mkdir ".ai-team\devops"

:: Add startup message to chat
echo [%TIME:~0,5%] [SYSTEM]: AI Team starting up... >> .ai-team\chat.md

:: Start Backend Developer
echo Starting Backend Developer...
start "AI Backend" cmd /k "cd /d .ai-team\backend && claude --dangerously-skip-permissions"

timeout /t 2 >nul

:: Start Frontend Developer
echo Starting Frontend Developer...
start "AI Frontend" cmd /k "cd /d .ai-team\frontend && claude --dangerously-skip-permissions"

timeout /t 2 >nul

:: Start DevOps Engineer
echo Starting DevOps Engineer...
start "AI DevOps" cmd /k "cd /d .ai-team\devops && claude --dangerously-skip-permissions"

timeout /t 2 >nul

:: Start Chat Monitor
echo Starting Chat Monitor...
start "Chat Monitor" powershell -NoExit -Command "cd 'C:\www.spa.com'; Write-Host 'CHAT MONITOR ACTIVE' -ForegroundColor Magenta; Write-Host '===================='; Write-Host ''; Get-Content '.ai-team\chat.md' -Wait -Tail 20 -Encoding UTF8"

timeout /t 2 >nul

:: Start Control Center
echo Starting Control Center...
start "Control Center" powershell -NoExit -File "C:\www.spa.com\control-center.ps1"

echo.
echo ========================================
echo    AI TEAM STARTED SUCCESSFULLY!
echo ========================================
echo.
echo 5 windows opened:
echo   1. Backend Developer - Give it role manually
echo   2. Frontend Developer - Give it role manually
echo   3. DevOps Engineer - Give it role manually
echo   4. Chat Monitor - Shows chat
echo   5. Control Center - Send commands
echo.
echo ========================================
echo INSTRUCTIONS FOR EACH CLAUDE WINDOW:
echo ========================================
echo.
echo BACKEND window - paste this:
echo You are Backend Developer for SPA Platform. Tech: Laravel, PHP, MySQL.
echo Read C:\www.spa.com\.ai-team\chat.md every 30 seconds.
echo Execute tasks with @backend or @all.
echo Write to chat: [HH:MM] [BACKEND]: message
echo.
echo FRONTEND window - paste this:
echo You are Frontend Developer for SPA Platform. Tech: Vue 3, TypeScript.
echo Read C:\www.spa.com\.ai-team\chat.md every 30 seconds.
echo Execute tasks with @frontend or @all.
echo Write to chat: [HH:MM] [FRONTEND]: message
echo.
echo DEVOPS window - paste this:
echo You are DevOps Engineer for SPA Platform. Tech: Docker, CI/CD.
echo Read C:\www.spa.com\.ai-team\chat.md every 30 seconds.
echo Execute tasks with @devops or @all.
echo Write to chat: [HH:MM] [DEVOPS]: message
echo.
pause