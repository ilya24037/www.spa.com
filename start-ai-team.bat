@echo off
title AI Team Launcher - SPA Platform
color 0A

echo ========================================
echo     AI TEAM LAUNCHER - SPA PLATFORM
echo ========================================
echo.
echo Starting AI team with proper monitoring instructions...
echo.

REM Start Backend Developer
start "AI Backend Developer" powershell -NoExit -Command ^
"chcp 65001 | Out-Null; ^
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8; ^
cd 'C:\www.spa.com\.ai-team\backend'; ^
Write-Host 'BACKEND DEVELOPER STARTING...' -ForegroundColor Green; ^
claude --dangerously-skip-permissions --mode normal 'You are Backend Developer for SPA Platform. Read CLAUDE.md in current folder for your instructions. Monitor ../chat.md file every 2 seconds. Execute tasks with @backend or @all mentions. Write results back to chat using Edit command. Start with greeting in chat.'"

timeout /t 2 /nobreak >nul

REM Start Frontend Developer
start "AI Frontend Developer" powershell -NoExit -Command ^
"chcp 65001 | Out-Null; ^
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8; ^
cd 'C:\www.spa.com\.ai-team\frontend'; ^
Write-Host 'FRONTEND DEVELOPER STARTING...' -ForegroundColor Cyan; ^
claude --dangerously-skip-permissions --mode normal 'You are Frontend Developer for SPA Platform. Read CLAUDE.md in current folder for your instructions. Monitor ../chat.md file every 2 seconds. Execute tasks with @frontend or @all mentions. Write results back to chat using Edit command. Start with greeting in chat.'"

timeout /t 2 /nobreak >nul

REM Start DevOps Engineer
start "AI DevOps Engineer" powershell -NoExit -Command ^
"chcp 65001 | Out-Null; ^
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8; ^
cd 'C:\www.spa.com\.ai-team\devops'; ^
Write-Host 'DEVOPS ENGINEER STARTING...' -ForegroundColor Yellow; ^
claude --dangerously-skip-permissions --mode normal 'You are DevOps Engineer for SPA Platform. Read CLAUDE.md in current folder for your instructions. Monitor ../chat.md file every 2 seconds. Execute tasks with @devops or @all mentions. Write results back to chat using Edit command. Start with greeting in chat.'"

timeout /t 2 /nobreak >nul

REM Start Chat Monitor
start "AI Team Chat Monitor" powershell -NoExit -Command ^
"chcp 65001 | Out-Null; ^
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8; ^
cd 'C:\www.spa.com'; ^
Write-Host 'CHAT MONITOR ACTIVE' -ForegroundColor Magenta; ^
Write-Host '===================='; ^
Write-Host ''; ^
Get-Content '.ai-team\chat.md' -Wait -Tail 20 -Encoding UTF8"

timeout /t 2 /nobreak >nul

REM Start Control Terminal with functions
start "AI Team Control" powershell -NoExit -Command ^
"chcp 65001 | Out-Null; ^
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8; ^
cd 'C:\www.spa.com'; ^
Clear-Host; ^
Write-Host ''; ^
Write-Host '  ====================================' -ForegroundColor Cyan; ^
Write-Host '     AI TEAM CONTROL CENTER' -ForegroundColor Green; ^
Write-Host '  ====================================' -ForegroundColor Cyan; ^
Write-Host ''; ^
Write-Host '  Available commands:' -ForegroundColor Yellow; ^
Write-Host '  ------------------' -ForegroundColor Yellow; ^
Write-Host '  msg-all ""message""      - Send to all team' -ForegroundColor White; ^
Write-Host '  msg-back ""message""     - Send to Backend' -ForegroundColor White; ^
Write-Host '  msg-front ""message""    - Send to Frontend' -ForegroundColor White; ^
Write-Host '  msg-dev ""message""      - Send to DevOps' -ForegroundColor White; ^
Write-Host '  msg-team ""b,f"" ""text""  - Send to group' -ForegroundColor White; ^
Write-Host '  status                  - Show task statuses' -ForegroundColor White; ^
Write-Host '  chat                    - Show recent messages' -ForegroundColor White; ^
Write-Host '  clear-chat              - Clear chat' -ForegroundColor White; ^
Write-Host '  help                    - Show help' -ForegroundColor White; ^
Write-Host ''; ^
Write-Host '  Examples:' -ForegroundColor Gray; ^
Write-Host '  msg-all ""create review system""' -ForegroundColor DarkGray; ^
Write-Host '  msg-back ""create User model""' -ForegroundColor DarkGray; ^
Write-Host '  msg-team ""f,d"" ""prepare deploy""' -ForegroundColor DarkGray; ^
Write-Host ''; ^
Write-Host '  Team is starting...' -ForegroundColor Green; ^
Write-Host ''; ^
function msg-all { param([string]$msg) $time = Get-Date -Format 'HH:mm'; ""[${time}] [PM]: @all $msg"" | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8 -Append; Write-Host ""Message sent to ALL: $msg"" -ForegroundColor Green }; ^
function msg-back { param([string]$msg) $time = Get-Date -Format 'HH:mm'; ""[${time}] [PM]: @backend $msg"" | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8 -Append; Write-Host ""Message sent to BACKEND: $msg"" -ForegroundColor Blue }; ^
function msg-front { param([string]$msg) $time = Get-Date -Format 'HH:mm'; ""[${time}] [PM]: @frontend $msg"" | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8 -Append; Write-Host ""Message sent to FRONTEND: $msg"" -ForegroundColor Cyan }; ^
function msg-dev { param([string]$msg) $time = Get-Date -Format 'HH:mm'; ""[${time}] [PM]: @devops $msg"" | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8 -Append; Write-Host ""Message sent to DEVOPS: $msg"" -ForegroundColor Yellow }; ^
function msg-team { param([string]$teams, [string]$msg) $time = Get-Date -Format 'HH:mm'; $mentions = ''; if ($teams -match 'b') {$mentions += '@backend '}; if ($teams -match 'f') {$mentions += '@frontend '}; if ($teams -match 'd') {$mentions += '@devops '}; ""[${time}] [PM]: $mentions$msg"" | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8 -Append; Write-Host ""Message sent to [$teams]: $msg"" -ForegroundColor Magenta }; ^
function status { Write-Host 'Task statuses:' -ForegroundColor Yellow; Get-Content '.ai-team\chat.md' -Encoding UTF8 | Select-String 'done|working|blocked' | Select-Object -Last 10 | ForEach-Object { if ($_ -match 'done') { Write-Host $_ -ForegroundColor Green } elseif ($_ -match 'working') { Write-Host $_ -ForegroundColor Yellow } elseif ($_ -match 'blocked') { Write-Host $_ -ForegroundColor Red } else { Write-Host $_ } } }; ^
function chat { Write-Host 'Recent messages:' -ForegroundColor Cyan; Get-Content '.ai-team\chat.md' -Encoding UTF8 -Tail 15 | ForEach-Object { if ($_ -match '\[PM\]') { Write-Host $_ -ForegroundColor White } elseif ($_ -match '\[BACKEND\]') { Write-Host $_ -ForegroundColor Blue } elseif ($_ -match '\[FRONTEND\]') { Write-Host $_ -ForegroundColor Cyan } elseif ($_ -match '\[DEVOPS\]') { Write-Host $_ -ForegroundColor Yellow } else { Write-Host $_ -ForegroundColor Gray } } }; ^
function clear-chat { Clear-Content '.ai-team\chat.md'; $time = Get-Date -Format 'HH:mm'; ""[${time}] [SYSTEM]: Chat cleared. AI team ready."" | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8; Write-Host 'Chat cleared!' -ForegroundColor Green }; ^
function help { Write-Host ''; Write-Host 'AI Team Commands:' -ForegroundColor Yellow; Write-Host '  msg-all ""text""  - Send to all' -ForegroundColor White; Write-Host '  msg-back ""text"" - Send to Backend' -ForegroundColor White; Write-Host '  msg-front ""text"" - Send to Frontend' -ForegroundColor White; Write-Host '  msg-dev ""text"" - Send to DevOps' -ForegroundColor White; Write-Host '  msg-team ""b,f,d"" ""text"" - Send to group' -ForegroundColor White; Write-Host '  status - Show task statuses' -ForegroundColor White; Write-Host '  chat - Show recent messages' -ForegroundColor White; Write-Host '  clear-chat - Clear chat' -ForegroundColor White; Write-Host '  help - Show this help' -ForegroundColor White; Write-Host '' }"

echo.
echo AI Team is starting...
echo 5 windows should open:
echo   1. Backend Developer
echo   2. Frontend Developer
echo   3. DevOps Engineer
echo   4. Chat Monitor
echo   5. Control Center (your terminal)
echo.
echo Use Control Center to manage the team!
echo.
pause