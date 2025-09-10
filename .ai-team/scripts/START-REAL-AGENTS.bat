@echo off
title Real AI Agents Launcher
color 0A

echo ========================================
echo     REAL AI AGENTS WITH CLAUDE
echo ========================================
echo.
echo This will launch 3 REAL Claude agents that can:
echo - Actually analyze code
echo - Read and edit files
echo - Execute real tasks
echo.
echo Make sure Claude CLI is installed!
echo.
pause

echo.
echo Starting Backend Agent...
start "Real Backend Agent" powershell -NoExit -File "C:\www.spa.com\.ai-team\scripts\launch-real-backend.ps1"
timeout /t 3 /nobreak >nul

echo Starting Frontend Agent...
start "Real Frontend Agent" powershell -NoExit -File "C:\www.spa.com\.ai-team\scripts\launch-real-frontend.ps1"
timeout /t 3 /nobreak >nul

echo Starting DevOps Agent...
start "Real DevOps Agent" powershell -NoExit -File "C:\www.spa.com\.ai-team\scripts\launch-real-devops.ps1"

echo.
echo ========================================
echo     ALL AGENTS LAUNCHED!
echo ========================================
echo.
echo Agents will:
echo 1. Monitor chat.md every 10 seconds
echo 2. Respond to @mentions
echo 3. ACTUALLY PERFORM tasks (not just simulate)
echo.
echo Test with: @all analyze project
echo.
pause