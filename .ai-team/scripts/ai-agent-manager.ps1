# AI Agent Manager - Manages AI team processes
param([string]$Action = "status")

$global:AgentProcesses = @{}
$chatPath = "C:\www.spa.com\.ai-team\chat.md"

function Start-AIAgent {
    param(
        [string]$Role,
        [string]$Color
    )
    
    # Simplified instructions without special characters
    $instruction = switch($Role) {
        "TeamLead" {
            "TeamLead here. Check chat.md every 2 sec. Reply to teamlead or all mentions. Coordinate backend frontend devops. Format HH:MM TEAMLEAD message"
        }
        "Backend" {
            "Backend dev here. Check chat.md every 2 sec. Reply to backend or all. Laravel DDD expert. Format HH:MM BACKEND message"
        }
        "Frontend" {
            "Frontend dev here. Check chat.md every 2 sec. Reply to frontend or all. Vue3 TypeScript expert. Format HH:MM FRONTEND message"
        }
        "DevOps" {
            "DevOps here. Check chat.md every 2 sec. Reply to devops or all. Docker CI expert. Format HH:MM DEVOPS message"
        }
    }
    
    $tempBat = "$env:TEMP\ai_$($Role.ToLower())_$(Get-Random).bat"
    @"
@echo off
title AI $Role Agent
color $Color
cd /d C:\www.spa.com
claude --dangerously-skip-permissions "$instruction"
pause
"@ | Out-File -FilePath $tempBat -Encoding ASCII
    
    $process = Start-Process cmd -ArgumentList "/c", $tempBat -PassThru -WindowStyle Minimized
    $global:AgentProcesses[$Role] = $process
    
    $time = Get-Date -Format 'HH:mm'
    $message = "[$time] [SYSTEM]: $Role agent started (PID: $($process.Id))"
    [System.IO.File]::AppendAllText($chatPath, "$message`n", [System.Text.Encoding]::UTF8)
    
    return $process
}

function Stop-AIAgent {
    param([string]$Role)
    
    if ($global:AgentProcesses.ContainsKey($Role)) {
        $process = $global:AgentProcesses[$Role]
        if (!$process.HasExited) {
            $process.Kill()
            $time = Get-Date -Format 'HH:mm'
            $message = "[$time] [SYSTEM]: $Role agent stopped"
            [System.IO.File]::AppendAllText($chatPath, "$message`n", [System.Text.Encoding]::UTF8)
        }
        $global:AgentProcesses.Remove($Role)
    }
}

function Get-AgentStatus {
    $status = @{}
    foreach ($role in @("Backend", "Frontend", "DevOps", "TeamLead")) {
        if ($global:AgentProcesses.ContainsKey($role)) {
            $process = $global:AgentProcesses[$role]
            $status[$role] = if (!$process.HasExited) { "running" } else { "stopped" }
        } else {
            $status[$role] = "offline"
        }
    }
    return $status
}

# Handle actions
switch ($Action) {
    "start-backend" {
        Start-AIAgent -Role "Backend" -Color "0A"
        Write-Host "Backend agent started" -ForegroundColor Green
    }
    "start-frontend" {
        Start-AIAgent -Role "Frontend" -Color "0B"
        Write-Host "Frontend agent started" -ForegroundColor Cyan
    }
    "start-devops" {
        Start-AIAgent -Role "DevOps" -Color "0E"
        Write-Host "DevOps agent started" -ForegroundColor Yellow
    }
    "start-teamlead" {
        Start-AIAgent -Role "TeamLead" -Color "0D"
        Write-Host "TeamLead agent started" -ForegroundColor Magenta
    }
    "start-all" {
        Start-AIAgent -Role "TeamLead" -Color "0D"
        Start-Process -FilePath "powershell" -ArgumentList "-Command", "Start-Sleep -Seconds 2" -Wait
        Start-AIAgent -Role "Backend" -Color "0A"
        Start-AIAgent -Role "Frontend" -Color "0B"
        Start-AIAgent -Role "DevOps" -Color "0E"
        Write-Host "All agents started" -ForegroundColor Green
    }
    "stop-all" {
        Stop-AIAgent -Role "Backend"
        Stop-AIAgent -Role "Frontend"
        Stop-AIAgent -Role "DevOps"
        Stop-AIAgent -Role "TeamLead"
        Write-Host "All agents stopped" -ForegroundColor Red
    }
    "status" {
        $status = Get-AgentStatus
        $status | ConvertTo-Json
    }
}