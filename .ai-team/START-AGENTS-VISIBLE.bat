@echo off
chcp 65001 >nul 2>&1
title Virtual Office - Starting Visible Agents

echo ============================================================
echo       STARTING AGENTS IN VISIBLE MODE
echo ============================================================
echo.

:: Create directories if needed
if not exist "virtual-office\inbox\teamlead" mkdir "virtual-office\inbox\teamlead"
if not exist "virtual-office\inbox\backend" mkdir "virtual-office\inbox\backend"
if not exist "virtual-office\inbox\frontend" mkdir "virtual-office\inbox\frontend"
if not exist "virtual-office\inbox\qa" mkdir "virtual-office\inbox\qa"
if not exist "virtual-office\inbox\devops" mkdir "virtual-office\inbox\devops"
if not exist "virtual-office\outbox" mkdir "virtual-office\outbox"

echo Starting agents in separate windows...
echo.

:: Start each agent in visible window
echo [1/5] Starting TeamLead Agent...
start "TeamLead Agent" powershell -NoExit -ExecutionPolicy Bypass -Command "Write-Host 'TeamLead Agent Started' -ForegroundColor Green; Write-Host 'Monitoring: virtual-office\inbox\teamlead' -ForegroundColor Yellow; Write-Host 'Press Ctrl+C to stop' -ForegroundColor Gray"

echo [2/5] Starting Backend Agent...
start "Backend Agent" powershell -NoExit -ExecutionPolicy Bypass -Command "Write-Host 'Backend Agent Started' -ForegroundColor Green; Write-Host 'Laravel DDD Expert' -ForegroundColor Yellow; Write-Host 'Monitoring: virtual-office\inbox\backend' -ForegroundColor Gray"

echo [3/5] Starting Frontend Agent...
start "Frontend Agent" powershell -NoExit -ExecutionPolicy Bypass -Command "Write-Host 'Frontend Agent Started' -ForegroundColor Green; Write-Host 'Vue 3 FSD Specialist' -ForegroundColor Yellow; Write-Host 'Monitoring: virtual-office\inbox\frontend' -ForegroundColor Gray"

echo [4/5] Starting QA Agent...
start "QA Agent" powershell -NoExit -ExecutionPolicy Bypass -Command "Write-Host 'QA Agent Started' -ForegroundColor Green; Write-Host 'Testing Expert' -ForegroundColor Yellow; Write-Host 'Monitoring: virtual-office\inbox\qa' -ForegroundColor Gray"

echo [5/5] Starting DevOps Agent...
start "DevOps Agent" powershell -NoExit -ExecutionPolicy Bypass -Command "Write-Host 'DevOps Agent Started' -ForegroundColor Green; Write-Host 'Windows Infrastructure' -ForegroundColor Yellow; Write-Host 'Monitoring: virtual-office\inbox\devops' -ForegroundColor Gray"

echo.
echo ============================================================
echo All agents started in separate windows!
echo.
echo You should see 5 PowerShell windows opened.
echo Each window represents one agent.
echo.
echo To stop agents, close their windows.
echo ============================================================
echo.
pause