@echo off
title Virtual Office - Fixed V2
color 0A
chcp 65001 >nul 2>&1

echo.
echo ========================================
echo      VIRTUAL OFFICE 3.2 - FIXED V2
echo ========================================
echo.

:: Set base path
set BASE_PATH=C:\www.spa.com\.ai-team
cd /d %BASE_PATH%

:: Step 1: Clean old processes
echo [1/7] Cleaning old processes...
taskkill /F /FI "WINDOWTITLE eq *Agent*" >nul 2>&1
taskkill /F /FI "WINDOWTITLE eq *Chat*Server*" >nul 2>&1
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8082') do (
    taskkill /F /PID %%a >nul 2>&1
)
timeout /t 2 >nul

:: Step 2: Initialize Virtual Office structure
echo [2/7] Initializing Virtual Office structure...
if not exist "virtual-office\inbox" mkdir "virtual-office\inbox"
if not exist "virtual-office\outbox" mkdir "virtual-office\outbox"
if not exist "virtual-office\tasks" mkdir "virtual-office\tasks"
if not exist "virtual-office\metrics" mkdir "virtual-office\metrics"

:: Create agent directories
for %%A in (teamlead backend frontend qa devops) do (
    if not exist "virtual-office\inbox\%%A" mkdir "virtual-office\inbox\%%A"
    if not exist "virtual-office\outbox\%%A" mkdir "virtual-office\outbox\%%A"
)

:: Step 3: Clear chat
echo [3/7] Preparing chat system...
echo [%time:~0,5%] [SYSTEM]: Virtual Office V2 startup initiated > chat.md
echo.

:: Step 4: Start chat server
echo [4/7] Starting chat server on port 8082...
start "Chat Server" /min cmd /c "node ai-team-server.cjs"
timeout /t 3 >nul

:: Step 5: Open dashboard
echo [5/7] Opening dashboard in browser...
start "" "http://localhost:8082/ai-team-dashboard-channels.html"
timeout /t 2 >nul

:: Step 6: Start AI agents with EXPLICIT roles
echo [6/7] Starting AI agents with correct roles...
echo.

cd scripts

:: TeamLead with explicit prompt
echo   Starting TeamLead (Coordinator)...
echo You are TeamLead. Monitor chat.md for @teamlead and @all messages. > "%TEMP%\teamlead_prompt.txt"
echo Coordinate the team and distribute tasks. >> "%TEMP%\teamlead_prompt.txt"
type "..\teamlead\CLAUDE-VIRTUAL-OFFICE.md" >> "%TEMP%\teamlead_prompt.txt" 2>nul
start "TeamLead Agent" cmd /c "claude chat --dangerously-skip-permissions < "%TEMP%\teamlead_prompt.txt""
timeout /t 3 >nul

:: Backend with explicit prompt
echo   Starting Backend (Laravel Developer)...
echo You are Backend Developer. Monitor chat.md for @backend and @all messages. > "%TEMP%\backend_prompt.txt"
echo Handle Laravel API and database tasks. >> "%TEMP%\backend_prompt.txt"
type "..\backend\CLAUDE-VIRTUAL-OFFICE.md" >> "%TEMP%\backend_prompt.txt" 2>nul
start "Backend Agent" cmd /c "claude chat --dangerously-skip-permissions < "%TEMP%\backend_prompt.txt""
timeout /t 2 >nul

:: Frontend with explicit prompt
echo   Starting Frontend (Vue.js Developer)...
echo You are Frontend Developer. Monitor chat.md for @frontend and @all messages. > "%TEMP%\frontend_prompt.txt"
echo Handle Vue.js components and UI tasks. >> "%TEMP%\frontend_prompt.txt"
type "..\frontend\CLAUDE-VIRTUAL-OFFICE.md" >> "%TEMP%\frontend_prompt.txt" 2>nul
start "Frontend Agent" cmd /c "claude chat --dangerously-skip-permissions < "%TEMP%\frontend_prompt.txt""
timeout /t 2 >nul

:: QA with explicit prompt
echo   Starting QA (Test Engineer)...
echo You are QA Engineer. Monitor chat.md for @qa and @all messages. > "%TEMP%\qa_prompt.txt"
echo Test features and find bugs. >> "%TEMP%\qa_prompt.txt"
type "..\qa\CLAUDE.md" >> "%TEMP%\qa_prompt.txt" 2>nul
start "QA Agent" cmd /c "claude chat --dangerously-skip-permissions < "%TEMP%\qa_prompt.txt""
timeout /t 2 >nul

:: DevOps with explicit prompt
echo   Starting DevOps (Infrastructure Engineer)...
echo You are DevOps Engineer. Monitor chat.md for @devops and @all messages. > "%TEMP%\devops_prompt.txt"
echo Handle deployment and infrastructure. >> "%TEMP%\devops_prompt.txt"
type "..\devops\CLAUDE-VIRTUAL-OFFICE.md" >> "%TEMP%\devops_prompt.txt" 2>nul
start "DevOps Agent" cmd /c "claude chat --dangerously-skip-permissions < "%TEMP%\devops_prompt.txt""
timeout /t 3 >nul

cd ..

:: Step 7: Add initial task
echo [7/7] Adding initial task to chat...
echo. >> chat.md
echo [%time:~0,5%] [CEO]: @all Welcome to Virtual Office V2! >> chat.md
echo [%time:~0,5%] [CEO]: @teamlead please introduce yourself and your role >> chat.md
echo [%time:~0,5%] [CEO]: @backend please introduce yourself and your role >> chat.md
echo [%time:~0,5%] [CEO]: @frontend please introduce yourself and your role >> chat.md
echo [%time:~0,5%] [CEO]: @qa please introduce yourself and your role >> chat.md
echo [%time:~0,5%] [CEO]: @devops please introduce yourself and your role >> chat.md
echo.

echo ========================================
echo     VIRTUAL OFFICE V2 STARTED!
echo ========================================
echo.
echo Each agent should now identify themselves correctly:
echo - TeamLead: Coordinator
echo - Backend: Laravel Developer
echo - Frontend: Vue.js Developer
echo - QA: Test Engineer
echo - DevOps: Infrastructure Engineer
echo.
echo Check chat.md for their responses!
echo.
pause