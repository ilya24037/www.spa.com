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
        Instruction = "You are Backend developer. Monitor .ai-team/chat.md file every 10 seconds. When you see @backend or @all, respond with your expertise in Laravel and DDD. Format your messages as [HH:MM] [BACKEND]: your response. Focus on backend implementation."
    }
    "Frontend" = @{
        Color = "0B"
        CheckInterval = 10
        Instruction = "You are Frontend developer. Monitor .ai-team/chat.md file every 10 seconds. When you see @frontend or @all, respond with Vue 3 and TypeScript expertise. Format your messages as [HH:MM] [FRONTEND]: your response. Focus on UI implementation."
    }
    "DevOps" = @{
        Color = "0E"
        CheckInterval = 10
        Instruction = "You are DevOps engineer. Monitor .ai-team/chat.md file every 10 seconds. When you see @devops or @all, respond with Docker and CI/CD expertise. Format your messages as [HH:MM] [DEVOPS]: your response. Focus on infrastructure."
    }
    "TeamLead" = @{
        Color = "0D"
        CheckInterval = 5
        Instruction = "You are Team Lead coordinator. Monitor .ai-team/chat.md file every 5 seconds. When you see @teamlead or @all, analyze the request and coordinate the team. Use @backend, @frontend, @devops to delegate tasks. Format messages as [HH:MM] [TEAMLEAD]: your response. Never write code yourself, only coordinate!"
    }
}

if (-not $roles.ContainsKey($Role)) {
    Write-Host "Invalid role. Use: Backend, Frontend, DevOps, or TeamLead" -ForegroundColor Red
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