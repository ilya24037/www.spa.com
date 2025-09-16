# QA Agent Launcher
param(
    [string]$Mode = "normal"
)

$chatPath = "C:\www.spa.com\.ai-team\chat.md"
$inboxPath = "C:\www.spa.com\.ai-team\virtual-office\inbox\qa"
$promptPath = "C:\www.spa.com\.ai-team\qa\CLAUDE.md"

Write-Host "Starting QA Agent..." -ForegroundColor Cyan
Write-Host "Mode: $Mode" -ForegroundColor Yellow
Write-Host "Chat: $chatPath" -ForegroundColor Gray
Write-Host "Inbox: $inboxPath" -ForegroundColor Gray

# Read QA prompt
if (Test-Path $promptPath) {
    $prompt = Get-Content $promptPath -Raw -Encoding UTF8
} else {
    $prompt = @"
You are QA Engineer. Monitor .ai-team/chat.md and virtual-office/inbox/qa/ for testing tasks.
When you see @qa or @all, respond with testing expertise.
Format: [HH:MM] [QA]: your response
Focus on: testing, bug reports, validation, quality assurance.
"@
}

# Log startup
$time = Get-Date -Format 'HH:mm'
Add-Content -Path $chatPath -Value "[$time] [SYSTEM]: QA agent started" -Encoding UTF8

# Launch Claude with QA instructions
Write-Host "`nLaunching Claude as QA Engineer..." -ForegroundColor Green
& claude --dangerously-skip-permissions $prompt

Write-Host "`nQA Agent stopped" -ForegroundColor Red