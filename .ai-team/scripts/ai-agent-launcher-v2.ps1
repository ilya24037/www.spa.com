# AI Agent Launcher V2 - Auto-reads prompts from files
param(
    [string]$Role,
    [string]$Mode = "virtual-office"  # virtual-office or classic
)

$basePath = "C:\www.spa.com\.ai-team"
$chatPath = "$basePath\chat.md"

# Agent configurations
$agents = @{
    "Backend" = @{
        PromptFile = if ($Mode -eq "virtual-office") {
            "$basePath\backend\CLAUDE-VIRTUAL-OFFICE.md"
        } else {
            "$basePath\backend\CLAUDE.md"
        }
        Color = "0A"
        CheckInterval = 10
    }
    "Frontend" = @{
        PromptFile = if ($Mode -eq "virtual-office") {
            "$basePath\frontend\CLAUDE-VIRTUAL-OFFICE.md"
        } else {
            "$basePath\frontend\CLAUDE.md"
        }
        Color = "0B"
        CheckInterval = 10
    }
    "QA" = @{
        PromptFile = "$basePath\qa\CLAUDE.md"
        Color = "0C"
        CheckInterval = 10
    }
    "DevOps" = @{
        PromptFile = if ($Mode -eq "virtual-office") {
            "$basePath\devops\CLAUDE-VIRTUAL-OFFICE.md"
        } else {
            "$basePath\devops\CLAUDE.md"
        }
        Color = "0E"
        CheckInterval = 10
    }
    "TeamLead" = @{
        PromptFile = if ($Mode -eq "virtual-office") {
            "$basePath\teamlead\CLAUDE-VIRTUAL-OFFICE.md"
        } else {
            "$basePath\teamlead\CLAUDE.md"
        }
        Color = "0D"
        CheckInterval = 5
    }
}

if (-not $agents.ContainsKey($Role)) {
    Write-Host "Invalid role. Use: Backend, Frontend, QA, DevOps, or TeamLead" -ForegroundColor Red
    exit 1
}

$config = $agents[$Role]

# Read prompt from file
$promptContent = ""
if (Test-Path $config.PromptFile) {
    $promptContent = Get-Content $config.PromptFile -Raw -Encoding UTF8
    Write-Host "✅ Loaded prompt from: $($config.PromptFile)" -ForegroundColor Green
} else {
    Write-Host "⚠️ Prompt file not found: $($config.PromptFile)" -ForegroundColor Yellow
    Write-Host "Using default prompt..." -ForegroundColor Yellow

    # Fallback prompt
    $promptContent = @"
You are $Role agent. Monitor .ai-team/chat.md every $($config.CheckInterval) seconds.
When you see @$($Role.ToLower()) or @all, respond with your expertise.
Format: [HH:MM] [$($Role.ToUpper())]: your response
Check virtual-office/inbox/$($Role.ToLower())/ for personal tasks.
Update metrics after completing tasks.
"@
}

# Add auto-metrics integration
$promptContent += @"

IMPORTANT: Auto-update metrics after actions:
- After completing a task: Run command: powershell scripts\metrics-updater.ps1 -Agent $($Role.ToLower()) -Action task_completed
- After processing message: Run command: powershell scripts\metrics-updater.ps1 -Agent $($Role.ToLower()) -Action message_processed
- For QA: After finding bug: powershell scripts\metrics-updater.ps1 -Agent qa -Action bug_found
- For DevOps: After deployment: powershell scripts\metrics-updater.ps1 -Agent devops -Action deployment
"@

# Add channel integration
$promptContent += @"

CHANNEL INTEGRATION:
- Post daily standup at 9:00 to virtual-office/channels/standup/
- Report blockers to virtual-office/channels/help/
- General updates to virtual-office/channels/general/
"@

# Create launcher script
$launcherPath = "$env:TEMP\launch_$($Role.ToLower())_v2_$(Get-Random).ps1"

@"
# AI $Role Agent V2
Write-Host 'Starting $Role Agent (Virtual Office Mode)...' -ForegroundColor Green
Write-Host 'Mode: $Mode' -ForegroundColor Cyan
Write-Host 'Chat path: $chatPath' -ForegroundColor Cyan
Write-Host 'Check interval: $($config.CheckInterval) seconds' -ForegroundColor Cyan
Write-Host ''
Write-Host 'Features enabled:' -ForegroundColor Yellow
Write-Host '✅ Auto-reading prompts from files' -ForegroundColor Green
Write-Host '✅ Auto-updating metrics' -ForegroundColor Green
Write-Host '✅ Channel integration' -ForegroundColor Green
Write-Host '✅ Inbox/Outbox system' -ForegroundColor Green
Write-Host ''
Write-Host 'Launching Claude with Virtual Office capabilities...' -ForegroundColor Green

# Start Claude with full prompt
& claude --dangerously-skip-permissions @'
$promptContent
'@
"@ | Out-File -FilePath $launcherPath -Encoding UTF8

# Start the agent
Write-Host "Launching $Role agent V2..." -ForegroundColor Green
Start-Process powershell -ArgumentList "-ExecutionPolicy", "Bypass", "-NoExit", "-File", $launcherPath -WindowStyle Normal

# Log to chat
$time = Get-Date -Format 'HH:mm'
Add-Content -Path $chatPath -Value "[$time] [SYSTEM]: $Role agent V2 started (Virtual Office mode)" -Encoding UTF8

# Auto-update startup metric
& "$basePath\scripts\metrics-updater.ps1" -Agent $Role.ToLower() -Action message_processed

Write-Host "$Role agent V2 launched successfully!" -ForegroundColor Green