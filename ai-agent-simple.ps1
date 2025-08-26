# Simple AI Agent Launcher
param(
    [string]$Role = "Backend"
)

$chatPath = "C:\www.spa.com\.ai-team\chat.md"

# Simple instructions for each role
$instructions = @{
    "Backend" = "You are Backend dev. Read C:\www.spa.com\.ai-team\chat.md every 10 seconds. When you see @backend or @all, respond. Laravel expert. Write: [HH:MM] [BACKEND]: message"
    "Frontend" = "You are Frontend dev. Read C:\www.spa.com\.ai-team\chat.md every 10 seconds. When you see @frontend or @all, respond. Vue3 expert. Write: [HH:MM] [FRONTEND]: message"
    "DevOps" = "You are DevOps. Read C:\www.spa.com\.ai-team\chat.md every 10 seconds. When you see @devops or @all, respond. Docker expert. Write: [HH:MM] [DEVOPS]: message"
    "TeamLead" = "You are TeamLead. Read C:\www.spa.com\.ai-team\chat.md every 5 seconds. When you see @teamlead or @all, coordinate team. Write: [HH:MM] [TEAMLEAD]: message"
}

$instruction = $instructions[$Role]

Write-Host "Starting $Role Agent..." -ForegroundColor Green
Write-Host "Instruction: $instruction" -ForegroundColor Yellow

# Create a temporary instruction file
$tempFile = "$env:TEMP\ai_${Role}_instruction.txt"
$instruction | Out-File -FilePath $tempFile -Encoding UTF8

# Try to pass instruction via echo pipe
$process = Start-Process -FilePath "cmd" -ArgumentList "/c", "echo $instruction | claude --dangerously-skip-permissions" -PassThru -WindowStyle Normal

# Log to chat
$time = Get-Date -Format 'HH:mm'
Add-Content -Path $chatPath -Value "[$time] [SYSTEM]: $Role agent started (PID: $($process.Id))" -Encoding UTF8

Write-Host "$Role agent launched!" -ForegroundColor Green