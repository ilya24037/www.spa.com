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
    "[$time] [PM]: @all $msg" | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8 -Append
    Write-Host "Message sent to ALL: $msg" -ForegroundColor Green 
}

function msg-back { 
    param([string]$msg) 
    $time = Get-Date -Format 'HH:mm'
    "[$time] [PM]: @backend $msg" | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8 -Append
    Write-Host "Message sent to BACKEND: $msg" -ForegroundColor Blue 
}

function msg-front { 
    param([string]$msg) 
    $time = Get-Date -Format 'HH:mm'
    "[$time] [PM]: @frontend $msg" | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8 -Append
    Write-Host "Message sent to FRONTEND: $msg" -ForegroundColor Cyan 
}

function msg-dev { 
    param([string]$msg) 
    $time = Get-Date -Format 'HH:mm'
    "[$time] [PM]: @devops $msg" | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8 -Append
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

function help { 
    Write-Host ''
    Write-Host 'AI Team Commands:' -ForegroundColor Yellow
    Write-Host '  msg-all "text"  - Send to all' -ForegroundColor White
    Write-Host '  msg-back "text" - Send to Backend' -ForegroundColor White
    Write-Host '  msg-front "text" - Send to Frontend' -ForegroundColor White
    Write-Host '  msg-dev "text" - Send to DevOps' -ForegroundColor White
    Write-Host '  status - Show statuses' -ForegroundColor White
    Write-Host '  chat - Show chat' -ForegroundColor White
    Write-Host '  clear-chat - Clear chat' -ForegroundColor White
    Write-Host '' 
}