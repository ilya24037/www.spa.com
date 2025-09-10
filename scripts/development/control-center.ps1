# Control Center for AI Team
chcp 65001 | Out-Null
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8

cd C:\www.spa.com
Clear-Host

Write-Host ""
Write-Host "  ====================================" -ForegroundColor Cyan
Write-Host "     AI TEAM CONTROL CENTER" -ForegroundColor Green  
Write-Host "  ====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "  Available Commands:" -ForegroundColor Yellow
Write-Host "  ------------------" -ForegroundColor Yellow
Write-Host "  msg-all 'message'     - Send to all team" -ForegroundColor White
Write-Host "  msg-back 'message'    - Send to Backend" -ForegroundColor White
Write-Host "  msg-front 'message'   - Send to Frontend" -ForegroundColor White
Write-Host "  msg-dev 'message'     - Send to DevOps" -ForegroundColor White
Write-Host "  status                - Show task statuses" -ForegroundColor White
Write-Host "  chat                  - Show recent messages" -ForegroundColor White
Write-Host "  clear-chat            - Clear chat history" -ForegroundColor White
Write-Host "  help                  - Show this help" -ForegroundColor White
Write-Host ""
Write-Host "  Examples:" -ForegroundColor Gray
Write-Host "  msg-all 'create review system'" -ForegroundColor DarkGray
Write-Host "  msg-back 'create User model'" -ForegroundColor DarkGray
Write-Host ""

# Functions
function msg-all { 
    param([string]$msg) 
    $time = Get-Date -Format 'HH:mm'
    $message = "[$time] [PM]: @all $msg"
    Add-Content -Path '.ai-team\chat.md' -Value $message -Encoding UTF8
    Write-Host "Message sent to ALL: $msg" -ForegroundColor Green 
}

function msg-back { 
    param([string]$msg) 
    $time = Get-Date -Format 'HH:mm'
    $message = "[$time] [PM]: @backend $msg"
    Add-Content -Path '.ai-team\chat.md' -Value $message -Encoding UTF8
    Write-Host "Message sent to BACKEND: $msg" -ForegroundColor Blue 
}

function msg-front { 
    param([string]$msg) 
    $time = Get-Date -Format 'HH:mm'
    $message = "[$time] [PM]: @frontend $msg"
    Add-Content -Path '.ai-team\chat.md' -Value $message -Encoding UTF8
    Write-Host "Message sent to FRONTEND: $msg" -ForegroundColor Cyan 
}

function msg-dev { 
    param([string]$msg) 
    $time = Get-Date -Format 'HH:mm'
    $message = "[$time] [PM]: @devops $msg"
    Add-Content -Path '.ai-team\chat.md' -Value $message -Encoding UTF8
    Write-Host "Message sent to DEVOPS: $msg" -ForegroundColor Yellow 
}

function status { 
    Write-Host 'Task Statuses:' -ForegroundColor Yellow
    Get-Content '.ai-team\chat.md' -Encoding UTF8 | Select-String 'done|working|blocked' | Select-Object -Last 10 | ForEach-Object { 
        if ($_ -match 'done') { Write-Host $_ -ForegroundColor Green } 
        elseif ($_ -match 'working') { Write-Host $_ -ForegroundColor Yellow } 
        elseif ($_ -match 'blocked') { Write-Host $_ -ForegroundColor Red } 
        else { Write-Host $_ } 
    } 
}

function sync {
    $time = Get-Date -Format 'HH:mm'
    # Используем английский текст чтобы избежать проблем с кодировкой
    $syncMsg = "[$time] [PM]: @all SYNC - Please update your current work status"
    Add-Content -Path '.ai-team\chat.md' -Value $syncMsg -Encoding UTF8
    Write-Host "Sync request sent!" -ForegroundColor Green
    Write-Host "Agents should report their status now" -ForegroundColor Yellow
}

function report {
    Write-Host "`n=== TEAM WORK REPORT ===" -ForegroundColor Cyan
    Write-Host "Last 30 messages:" -ForegroundColor Yellow
    Get-Content '.ai-team\chat.md' -Encoding UTF8 -Tail 30
    Write-Host "`n=== END OF REPORT ===" -ForegroundColor Cyan
}

function chat { 
    Write-Host 'Recent Chat:' -ForegroundColor Cyan
    Get-Content '.ai-team\chat.md' -Encoding UTF8 -Tail 15 | ForEach-Object { 
        if ($_ -match '\[PM\]') { Write-Host $_ -ForegroundColor White } 
        elseif ($_ -match '\[BACKEND\]') { Write-Host $_ -ForegroundColor Blue } 
        elseif ($_ -match '\[FRONTEND\]') { Write-Host $_ -ForegroundColor Cyan } 
        elseif ($_ -match '\[DEVOPS\]') { Write-Host $_ -ForegroundColor Yellow } 
        else { Write-Host $_ -ForegroundColor Gray } 
    } 
}

function clear-chat { 
    Clear-Content '.ai-team\chat.md'
    $time = Get-Date -Format 'HH:mm'
    "[$time] [SYSTEM]: Chat cleared. AI Team ready." | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8
    Write-Host 'Chat cleared!' -ForegroundColor Green 
}

# Russian aliases
function vsem { param([string]$msg) msg-all $msg }
function back { param([string]$msg) msg-back $msg }
function front { param([string]$msg) msg-front $msg }
function devops { param([string]$msg) msg-dev $msg }
function ochistit { clear-chat }
function pomosh { help }

function help { 
    Write-Host ''
    Write-Host 'AI Team Commands:' -ForegroundColor Yellow
    Write-Host '  msg-all "text"  - Send to all' -ForegroundColor White
    Write-Host '  msg-back "text" - Send to Backend' -ForegroundColor White
    Write-Host '  msg-front "text" - Send to Frontend' -ForegroundColor White
    Write-Host '  msg-dev "text" - Send to DevOps' -ForegroundColor White
    Write-Host '  vsem "text" - Same as msg-all (rus)' -ForegroundColor White
    Write-Host '  back "text" - Same as msg-back (rus)' -ForegroundColor White
    Write-Host '  front "text" - Same as msg-front (rus)' -ForegroundColor White
    Write-Host '  devops "text" - Same as msg-dev (rus)' -ForegroundColor White
    Write-Host '  status - Show statuses' -ForegroundColor White
    Write-Host '  chat - Show chat' -ForegroundColor White
    Write-Host '  clear-chat / ochistit - Clear chat' -ForegroundColor White
    Write-Host '  help / pomosh - Show help' -ForegroundColor White
    Write-Host '' 
}