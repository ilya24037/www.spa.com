# AI Agent Launcher with proper instruction handling
param(
    [string]$Role,
    [string]$Color = "0A"
)

$chatPath = "C:\www.spa.com\.ai-team\chat.md"

# Role configurations
$roles = @{
    "Backend" = @{
        Color = "0A"
        CheckInterval = 10
        Instruction = "You are Backend developer. Monitor .ai-team/chat.md and virtual-office/inbox/backend/ every 10 seconds. When you see @backend or @all, respond with Laravel expertise. Check virtual-office/tasks/ for assigned tasks. Update metrics with scripts\metrics-updater.ps1. Work with @qa for testing. Format: [HH:MM] [BACKEND]: response. Read backend/CLAUDE-VIRTUAL-OFFICE.md for full instructions."
    }
    "Frontend" = @{
        Color = "0B"
        CheckInterval = 10
        Instruction = "You are Frontend developer. Monitor .ai-team/chat.md and virtual-office/inbox/frontend/ every 10 seconds. When you see @frontend or @all, respond with Vue 3 expertise. Check virtual-office/tasks/ for UI tasks. Work with @qa on UI testing. Update metrics with scripts\metrics-updater.ps1. Format: [HH:MM] [FRONTEND]: response. Read frontend/CLAUDE-VIRTUAL-OFFICE.md for details."
    }
    "QA" = @{
        Color = "0C"
        CheckInterval = 10
        Instruction = "You are QA Engineer. Monitor .ai-team/chat.md and virtual-office/inbox/qa/ every 10 seconds. Test features from @backend and @frontend. Report bugs, run tests, validate quality. Update metrics: scripts\metrics-updater.ps1 -Agent qa -Action bug_found/test_run. Post to channels/standup daily. Format: [HH:MM] [QA]: response. Read qa/CLAUDE.md for full instructions."
    }
    "DevOps" = @{
        Color = "0E"
        CheckInterval = 10
        Instruction = "You are DevOps engineer. Monitor .ai-team/chat.md and virtual-office/inbox/devops/ every 10 seconds. Handle infrastructure, CI/CD, deployments. Work with @qa for test automation. Update metrics: scripts\metrics-updater.ps1 -Agent devops -Action deployment. Monitor system/status.json. Format: [HH:MM] [DEVOPS]: response. Read devops/CLAUDE-VIRTUAL-OFFICE.md for details."
    }
    "TeamLead" = @{
        Color = "0D"
        CheckInterval = 5
        Instruction = "You are Team Lead coordinator of 5 agents. Monitor .ai-team/chat.md, virtual-office/inbox/teamlead/, and all channels every 5 seconds. Create tasks in virtual-office/tasks/, distribute via inbox, track progress. Coordinate @backend @frontend @qa @devops. Run daily standup in channels/standup. NEVER write code! Format: [HH:MM] [TEAMLEAD]: response. Read teamlead/CLAUDE-VIRTUAL-OFFICE.md for Virtual Office instructions."
    }
}

if (-not $roles.ContainsKey($Role)) {
    Write-Host "Invalid role. Use: Backend, Frontend, QA, DevOps, or TeamLead" -ForegroundColor Red
    exit 1
}

$config = $roles[$Role]

# Create launcher script
$launcherPath = "$env:TEMP\launch_$($Role.ToLower())_$(Get-Random).ps1"

@"
# AI $Role Agent
Write-Host 'Starting $Role Agent...' -ForegroundColor Green
Write-Host 'Chat path: $chatPath' -ForegroundColor Cyan
Write-Host 'Check interval: $($config.CheckInterval) seconds' -ForegroundColor Cyan
Write-Host ''
Write-Host 'Instruction:' -ForegroundColor Yellow
Write-Host '$($config.Instruction)' -ForegroundColor White
Write-Host ''
Write-Host 'Launching Claude...' -ForegroundColor Green

# Start Claude with instruction
& claude --dangerously-skip-permissions '$($config.Instruction)'
"@ | Out-File -FilePath $launcherPath -Encoding UTF8

# Start the agent
Write-Host "Launching $Role agent..." -ForegroundColor Green
Start-Process powershell -ArgumentList "-ExecutionPolicy", "Bypass", "-NoExit", "-File", $launcherPath -WindowStyle Normal

# Log to chat
$time = Get-Date -Format 'HH:mm'
Add-Content -Path $chatPath -Value "[$time] [SYSTEM]: $Role agent started" -Encoding UTF8

Write-Host "$Role agent launched successfully!" -ForegroundColor Green