# Backend Agent with Laravel/DDD Knowledge
$ErrorActionPreference = 'SilentlyContinue'

Write-Host "Starting Backend Agent with Laravel expertise..." -ForegroundColor Cyan

while ($true) {
    # Check inbox
    $inbox = Get-ChildItem "virtual-office\inbox\backend" -Filter "*.json" 2>$null

    if ($inbox) {
        foreach ($msg in $inbox) {
            try {
                $content = Get-Content $msg.FullName -Raw | ConvertFrom-Json
                Write-Host "Processing backend task: $($content.message)" -ForegroundColor Yellow

                # Process with enhanced instructions
                $prompt = @"
You are Backend developer for SPA Platform (Laravel 12, DDD).
Use BUSINESS_LOGIC_FIRST approach from CLAUDE_ENHANCED.md.

Task: $($content.message)

Remember:
- For errors like 'Cannot perform action' - use grep to find Action/Service
- Check $fillable in models for save issues
- Use minimal changes (KISS principle)
- Service layer for business logic, not Controllers
"@

                # Call Claude with context
                if (Test-Path "backend\CLAUDE_ENHANCED.md") {
                    claude chat --file "backend\CLAUDE_ENHANCED.md" "$prompt" > "virtual-office\outbox\backend_response_$(Get-Date -Format 'yyyyMMdd_HHmmss').txt"
                }

                # Move processed message
                Move-Item $msg.FullName "virtual-office\outbox\" -Force
                Write-Host "Backend task completed" -ForegroundColor Green
            }
            catch {
                Write-Host "Error: $_" -ForegroundColor Red
            }
        }
    }

    Start-Sleep -Seconds 10
}