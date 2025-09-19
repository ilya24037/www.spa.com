@echo off
chcp 65001 >nul 2>&1
title Enhanced Virtual Office with Knowledge Base

echo ============================================================
echo     ENHANCED VIRTUAL OFFICE - SPA PLATFORM EDITION
echo              AI Team with Knowledge Base
echo ============================================================
echo.

:: Check Claude
where claude >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Claude not found! Install: npm install -g @anthropic-ai/claude-code
    pause
    exit /b 1
)

:: Knowledge sync
echo Synchronizing project knowledge base...
powershell -ExecutionPolicy Bypass -File scripts\knowledge-sync-fixed.ps1 -Mode sync
if %errorlevel% neq 0 (
    echo WARNING: Knowledge sync completed with errors
    echo Continuing startup...
)
echo.

:: Create directories
echo Checking structure...
if not exist "virtual-office\inbox" mkdir "virtual-office\inbox"
if not exist "virtual-office\inbox\teamlead" mkdir "virtual-office\inbox\teamlead"
if not exist "virtual-office\inbox\backend" mkdir "virtual-office\inbox\backend"
if not exist "virtual-office\inbox\frontend" mkdir "virtual-office\inbox\frontend"
if not exist "virtual-office\inbox\qa" mkdir "virtual-office\inbox\qa"
if not exist "virtual-office\inbox\devops" mkdir "virtual-office\inbox\devops"
if not exist "virtual-office\outbox" mkdir "virtual-office\outbox"
if not exist "virtual-office\tasks" mkdir "virtual-office\tasks"
if not exist "virtual-office\reports" mkdir "virtual-office\reports"
if not exist "virtual-office\channels\general" mkdir "virtual-office\channels\general"
if not exist "virtual-office\channels\standup" mkdir "virtual-office\channels\standup"
if not exist "virtual-office\channels\help" mkdir "virtual-office\channels\help"
if not exist "virtual-office\metrics" mkdir "virtual-office\metrics"
if not exist "system\logs" mkdir "system\logs"

:: Initialize status
echo Initializing system...
echo {} > virtual-office\system\status.json 2>nul
echo [] > virtual-office\tasks\active.json 2>nul
echo.

:: Start server
echo Starting AI Team Server...
start /min cmd /c "node ai-team-server.cjs"
timeout /t 2 /nobreak >nul

:: Start agents
echo Starting agents with enhanced knowledge...
echo.

echo    [1/5] TeamLead Agent (coordinator)...
start "TeamLead Agent" powershell -ExecutionPolicy Bypass -File teamlead\agent.ps1

echo    [2/5] Backend Agent (Laravel DDD)...
start "Backend Agent" powershell -ExecutionPolicy Bypass -File backend\agent.ps1

echo    [3/5] Frontend Agent (Vue 3 FSD)...
start "Frontend Agent" powershell -ExecutionPolicy Bypass -File frontend\agent.ps1

echo    [4/5] QA Agent (testing)...
start "QA Agent" powershell -ExecutionPolicy Bypass -File qa\agent.ps1

echo    [5/5] DevOps Agent (infrastructure)...
start "DevOps Agent" powershell -ExecutionPolicy Bypass -File devops\agent.ps1

echo.
echo All agents started with enhanced knowledge base!
echo.

:: Auto standups
echo Setting up automatic standups...
if exist "scripts\auto-standup.ps1" (
    start /min powershell -ExecutionPolicy Bypass -File scripts\auto-standup.ps1
)
echo.

:: Display info
echo ============================================================
echo                 SYSTEM READY TO WORK
echo ============================================================
echo.
echo  Web Interface:    http://localhost:8082
echo  CEO Dashboard:    http://localhost:8082/ceo-dashboard.html
echo  Team Chat:        http://localhost:8082/chat.html
echo.
echo  Knowledge base synchronized from docs/
echo  Quick commands available in virtual-office/knowledge/
echo.
echo ============================================================
echo                    USEFUL COMMANDS
echo ============================================================
echo.
echo  Create task:
echo  powershell scripts\task-manager.ps1 -Action create
echo.
echo  Quick fix search:
echo  powershell scripts\quick-fix.ps1 -Error "error text"
echo.
echo  Check status:
echo  powershell scripts\check-agents.ps1
echo.
echo  Sync knowledge:
echo  powershell scripts\knowledge-sync-fixed.ps1
echo.
echo ============================================================
echo.
echo TIP: Agents now know all lessons from docs/LESSONS/
echo      and automatically apply patterns from project experience!
echo.
echo Press any key to close this window...
pause >nul