# TeamLead Agent with Enhanced Knowledge
$ErrorActionPreference = 'SilentlyContinue'

Write-Host "Starting TeamLead Agent with enhanced knowledge..." -ForegroundColor Cyan

while ($true) {
    # Check inbox
    $inbox = Get-ChildItem "virtual-office\inbox\teamlead" -Filter "*.json" 2>$null

    if ($inbox) {
        foreach ($msg in $inbox) {
            try {
                $content = Get-Content $msg.FullName -Raw | ConvertFrom-Json
                Write-Host "Processing task: $($content.message)" -ForegroundColor Yellow

                # Process with enhanced instructions
                $prompt = @"
You are TeamLead with knowledge of SPA Platform project.
Use the principles from CLAUDE_ENHANCED.md.

Task: $($content.message)

Apply:
- KISS principle
- Business logic first
- Check docs/LESSONS/ for similar solutions
- Assign to appropriate team member
"@

                # Call Claude with enhanced context
                if (Test-Path "teamlead\CLAUDE_ENHANCED.md") {
                    claude chat --file "teamlead\CLAUDE_ENHANCED.md" "$prompt" > "virtual-office\outbox\response_$(Get-Date -Format 'yyyyMMdd_HHmmss').txt"
                }

                # Move processed message
                Move-Item $msg.FullName "virtual-office\outbox\" -Force
                Write-Host "Task processed and moved to outbox" -ForegroundColor Green
            }
            catch {
                Write-Host "Error processing message: $_" -ForegroundColor Red
            }
        }
    }

    Start-Sleep -Seconds 5
}