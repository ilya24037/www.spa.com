@echo off
chcp 65001 >nul
title Full AI Team Launcher
color 0D

echo ========================================
echo     FULL AI TEAM WITH TEAMLEAD
echo ========================================
echo.
echo Starting complete AI team:
echo - TeamLead/Director (coordinator)
echo - Backend Developer
echo - Frontend Developer
echo - DevOps Engineer
echo.
pause

echo.
echo [1/4] Starting TeamLead/Director...
start "TeamLead" powershell -NoExit -File "C:\www.spa.com\.ai-team\scripts\launch-teamlead.ps1"
timeout /t 3 /nobreak >nul

echo [2/4] Starting Backend Developer...
start "Backend" powershell -NoExit -File "C:\www.spa.com\.ai-team\scripts\launch-real-backend.ps1"
timeout /t 3 /nobreak >nul

echo [3/4] Starting Frontend Developer...
start "Frontend" powershell -NoExit -File "C:\www.spa.com\.ai-team\scripts\launch-real-frontend.ps1"
timeout /t 3 /nobreak >nul

echo [4/4] Starting DevOps Engineer...
start "DevOps" powershell -NoExit -File "C:\www.spa.com\.ai-team\scripts\launch-real-devops.ps1"

echo.
echo ========================================
echo     FULL TEAM LAUNCHED!
echo ========================================
echo.
echo Team hierarchy:
echo   TeamLead - coordinates all tasks
echo   - Backend - API and server logic
echo   - Frontend - UI and components
echo   - DevOps - infrastructure
echo.
echo Test with: @teamlead assign task to analyze favorites page
echo.
pause