@echo off
title AI Team Full Launch System
color 0A

echo ===============================================
echo        AI TEAM COMPLETE LAUNCH SYSTEM
echo ===============================================
echo.

:: Создание структуры папок
echo [1/6] Creating folder structure...
if not exist ".ai-team" mkdir ".ai-team"
if not exist ".ai-team\backend" mkdir ".ai-team\backend"
if not exist ".ai-team\frontend" mkdir ".ai-team\frontend"
if not exist ".ai-team\devops" mkdir ".ai-team\devops"
if not exist ".ai-team\logs" mkdir ".ai-team\logs"

:: Инициализация chat.md
echo [2/6] Initializing chat file...
if not exist ".ai-team\chat.md" (
    echo # AI Team Chat > ".ai-team\chat.md"
    echo [09:00] [SYSTEM]: Chat initialized. Team ready. >> ".ai-team\chat.md"
)

:: Создание PowerShell скриптов для агентов
echo [3/6] Creating agent scripts...

:: Backend Agent Script
echo $ChatFile = '.ai-team\chat.md' > .ai-team\backend-agent.ps1
echo $Role = 'BACKEND' >> .ai-team\backend-agent.ps1
echo $host.UI.RawUI.WindowTitle = 'BACKEND AGENT' >> .ai-team\backend-agent.ps1
echo Clear-Host >> .ai-team\backend-agent.ps1
echo Write-Host '=============================' -ForegroundColor Green >> .ai-team\backend-agent.ps1
echo Write-Host '     BACKEND AGENT ONLINE    ' -ForegroundColor Green >> .ai-team\backend-agent.ps1
echo Write-Host '=============================' -ForegroundColor Green >> .ai-team\backend-agent.ps1
echo Write-Host '' >> .ai-team\backend-agent.ps1
echo function Send-Msg { >> .ai-team\backend-agent.ps1
echo     param([string]$msg) >> .ai-team\backend-agent.ps1
echo     $time = Get-Date -Format 'HH:mm' >> .ai-team\backend-agent.ps1
echo     Add-Content $ChatFile "[$time] [$Role]: $msg" -Encoding UTF8 >> .ai-team\backend-agent.ps1
echo } >> .ai-team\backend-agent.ps1
echo Send-Msg 'Backend agent connected' >> .ai-team\backend-agent.ps1
echo Write-Host 'Monitoring chat...' -ForegroundColor Yellow >> .ai-team\backend-agent.ps1
echo $lastCount = 0 >> .ai-team\backend-agent.ps1
echo while ($true) { >> .ai-team\backend-agent.ps1
echo     $lines = Get-Content $ChatFile -Encoding UTF8 -ErrorAction SilentlyContinue >> .ai-team\backend-agent.ps1
echo     if ($lines.Count -gt $lastCount) { >> .ai-team\backend-agent.ps1
echo         for ($i = $lastCount; $i -lt $lines.Count; $i++) { >> .ai-team\backend-agent.ps1
echo             $line = $lines[$i] >> .ai-team\backend-agent.ps1
echo             if ($line -match '@backend' -or $line -match '@all') { >> .ai-team\backend-agent.ps1
echo                 if ($line -notmatch '\[BACKEND\]') { >> .ai-team\backend-agent.ps1
echo                     Write-Host "Message: $line" -ForegroundColor Cyan >> .ai-team\backend-agent.ps1
echo                     if ($line -match 'status') { >> .ai-team\backend-agent.ps1
echo                         Send-Msg 'Ready and monitoring' >> .ai-team\backend-agent.ps1
echo                     } >> .ai-team\backend-agent.ps1
echo                 } >> .ai-team\backend-agent.ps1
echo             } >> .ai-team\backend-agent.ps1
echo         } >> .ai-team\backend-agent.ps1
echo         $lastCount = $lines.Count >> .ai-team\backend-agent.ps1
echo     } >> .ai-team\backend-agent.ps1
echo     Start-Sleep -Seconds 1 >> .ai-team\backend-agent.ps1
echo } >> .ai-team\backend-agent.ps1

:: Frontend Agent Script
echo $ChatFile = '.ai-team\chat.md' > .ai-team\frontend-agent.ps1
echo $Role = 'FRONTEND' >> .ai-team\frontend-agent.ps1
echo $host.UI.RawUI.WindowTitle = 'FRONTEND AGENT' >> .ai-team\frontend-agent.ps1
echo Clear-Host >> .ai-team\frontend-agent.ps1
echo Write-Host '=============================' -ForegroundColor Cyan >> .ai-team\frontend-agent.ps1
echo Write-Host '    FRONTEND AGENT ONLINE    ' -ForegroundColor Cyan >> .ai-team\frontend-agent.ps1
echo Write-Host '=============================' -ForegroundColor Cyan >> .ai-team\frontend-agent.ps1
echo Write-Host '' >> .ai-team\frontend-agent.ps1
echo function Send-Msg { >> .ai-team\frontend-agent.ps1
echo     param([string]$msg) >> .ai-team\frontend-agent.ps1
echo     $time = Get-Date -Format 'HH:mm' >> .ai-team\frontend-agent.ps1
echo     Add-Content $ChatFile "[$time] [$Role]: $msg" -Encoding UTF8 >> .ai-team\frontend-agent.ps1
echo } >> .ai-team\frontend-agent.ps1
echo Send-Msg 'Frontend agent connected' >> .ai-team\frontend-agent.ps1
echo Write-Host 'Monitoring chat...' -ForegroundColor Yellow >> .ai-team\frontend-agent.ps1
echo $lastCount = 0 >> .ai-team\frontend-agent.ps1
echo while ($true) { >> .ai-team\frontend-agent.ps1
echo     $lines = Get-Content $ChatFile -Encoding UTF8 -ErrorAction SilentlyContinue >> .ai-team\frontend-agent.ps1
echo     if ($lines.Count -gt $lastCount) { >> .ai-team\frontend-agent.ps1
echo         for ($i = $lastCount; $i -lt $lines.Count; $i++) { >> .ai-team\frontend-agent.ps1
echo             $line = $lines[$i] >> .ai-team\frontend-agent.ps1
echo             if ($line -match '@frontend' -or $line -match '@all') { >> .ai-team\frontend-agent.ps1
echo                 if ($line -notmatch '\[FRONTEND\]') { >> .ai-team\frontend-agent.ps1
echo                     Write-Host "Message: $line" -ForegroundColor Yellow >> .ai-team\frontend-agent.ps1
echo                     if ($line -match 'status') { >> .ai-team\frontend-agent.ps1
echo                         Send-Msg 'Ready and monitoring' >> .ai-team\frontend-agent.ps1
echo                     } >> .ai-team\frontend-agent.ps1
echo                 } >> .ai-team\frontend-agent.ps1
echo             } >> .ai-team\frontend-agent.ps1
echo         } >> .ai-team\frontend-agent.ps1
echo         $lastCount = $lines.Count >> .ai-team\frontend-agent.ps1
echo     } >> .ai-team\frontend-agent.ps1
echo     Start-Sleep -Seconds 1 >> .ai-team\frontend-agent.ps1
echo } >> .ai-team\frontend-agent.ps1

:: DevOps Agent Script  
echo $ChatFile = '.ai-team\chat.md' > .ai-team\devops-agent.ps1
echo $Role = 'DEVOPS' >> .ai-team\devops-agent.ps1
echo $host.UI.RawUI.WindowTitle = 'DEVOPS AGENT' >> .ai-team\devops-agent.ps1
echo Clear-Host >> .ai-team\devops-agent.ps1
echo Write-Host '=============================' -ForegroundColor Yellow >> .ai-team\devops-agent.ps1
echo Write-Host '     DEVOPS AGENT ONLINE     ' -ForegroundColor Yellow >> .ai-team\devops-agent.ps1
echo Write-Host '=============================' -ForegroundColor Yellow >> .ai-team\devops-agent.ps1
echo Write-Host '' >> .ai-team\devops-agent.ps1
echo function Send-Msg { >> .ai-team\devops-agent.ps1
echo     param([string]$msg) >> .ai-team\devops-agent.ps1
echo     $time = Get-Date -Format 'HH:mm' >> .ai-team\devops-agent.ps1
echo     Add-Content $ChatFile "[$time] [$Role]: $msg" -Encoding UTF8 >> .ai-team\devops-agent.ps1
echo } >> .ai-team\devops-agent.ps1
echo Send-Msg 'DevOps agent connected' >> .ai-team\devops-agent.ps1
echo Write-Host 'Monitoring chat...' -ForegroundColor Cyan >> .ai-team\devops-agent.ps1
echo $lastCount = 0 >> .ai-team\devops-agent.ps1
echo while ($true) { >> .ai-team\devops-agent.ps1
echo     $lines = Get-Content $ChatFile -Encoding UTF8 -ErrorAction SilentlyContinue >> .ai-team\devops-agent.ps1
echo     if ($lines.Count -gt $lastCount) { >> .ai-team\devops-agent.ps1
echo         for ($i = $lastCount; $i -lt $lines.Count; $i++) { >> .ai-team\devops-agent.ps1
echo             $line = $lines[$i] >> .ai-team\devops-agent.ps1
echo             if ($line -match '@devops' -or $line -match '@all') { >> .ai-team\devops-agent.ps1
echo                 if ($line -notmatch '\[DEVOPS\]') { >> .ai-team\devops-agent.ps1
echo                     Write-Host "Message: $line" -ForegroundColor Green >> .ai-team\devops-agent.ps1
echo                     if ($line -match 'status') { >> .ai-team\devops-agent.ps1
echo                         Send-Msg 'Ready and monitoring' >> .ai-team\devops-agent.ps1
echo                     } >> .ai-team\devops-agent.ps1
echo                 } >> .ai-team\devops-agent.ps1
echo             } >> .ai-team\devops-agent.ps1
echo         } >> .ai-team\devops-agent.ps1
echo         $lastCount = $lines.Count >> .ai-team\devops-agent.ps1
echo     } >> .ai-team\devops-agent.ps1
echo     Start-Sleep -Seconds 1 >> .ai-team\devops-agent.ps1
echo } >> .ai-team\devops-agent.ps1

:: Запуск агентов
echo [4/6] Starting AI Agents...

start "Backend Agent" powershell -ExecutionPolicy Bypass -File ".ai-team\backend-agent.ps1"
timeout /t 1 /nobreak >nul

start "Frontend Agent" powershell -ExecutionPolicy Bypass -File ".ai-team\frontend-agent.ps1"
timeout /t 1 /nobreak >nul

start "DevOps Agent" powershell -ExecutionPolicy Bypass -File ".ai-team\devops-agent.ps1"
timeout /t 1 /nobreak >nul

:: Chat Monitor
echo [5/6] Starting Chat Monitor...
start "Chat Monitor" powershell -ExecutionPolicy Bypass -NoExit -Command "Clear-Host; Write-Host 'CHAT MONITOR' -ForegroundColor Magenta; Write-Host '============' -ForegroundColor Magenta; Get-Content '.ai-team\chat.md' -Wait -Encoding UTF8"

:: Запуск Node.js сервера если есть
echo [6/6] Checking for web server...
if exist "ai-team-server.js" (
    start "AI Team Server" cmd /c "node ai-team-server.js"
    timeout /t 2 /nobreak >nul
    start http://localhost:8082/ai-team-dashboard-channels.html
) else (
    echo Web server not found, skipping...
)

echo.
echo ===============================================
echo    AI TEAM SYSTEM LAUNCHED SUCCESSFULLY!
echo ===============================================
echo.
echo Active components:
echo   [OK] Backend Agent  - PowerShell
echo   [OK] Frontend Agent - PowerShell
echo   [OK] DevOps Agent   - PowerShell
echo   [OK] Chat Monitor   - PowerShell
echo.
echo Test commands:
echo   1. Open new PowerShell and run:
echo      Add-Content ".ai-team\chat.md" "[10:00] [PM]: @all status"
echo.
echo   2. Watch agents respond in their terminals
echo.
pause