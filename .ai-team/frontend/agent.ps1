# Frontend Agent with Vue 3/FSD Knowledge
$ErrorActionPreference = 'SilentlyContinue'

Write-Host "Starting Frontend Agent with Vue 3 expertise..." -ForegroundColor Cyan

while ($true) {
    # Check inbox
    $inbox = Get-ChildItem "virtual-office\inbox\frontend" -Filter "*.json" 2>$null

    if ($inbox) {
        foreach ($msg in $inbox) {
            try {
                $content = Get-Content $msg.FullName -Raw | ConvertFrom-Json
                Write-Host "Processing frontend task: $($content.message)" -ForegroundColor Yellow

                # Process with enhanced instructions
                $prompt = @"
You are Frontend developer for SPA Platform (Vue 3, TypeScript, FSD).
Use instructions from CLAUDE_ENHANCED.md.

Task: $($content.message)

Critical rules:
- ALWAYS add watchers for reactive data (or data will be lost!)
- Use computed for protection against undefined
- Follow FSD architecture (shared/entities/features/widgets)
- Apply KISS principle - reuse existing components
"@

                # Call Claude
                if (Test-Path "frontend\CLAUDE_ENHANCED.md") {
                    claude chat --file "frontend\CLAUDE_ENHANCED.md" "$prompt" > "virtual-office\outbox\frontend_response_$(Get-Date -Format 'yyyyMMdd_HHmmss').txt"
                }

                # Move processed
                Move-Item $msg.FullName "virtual-office\outbox\" -Force
                Write-Host "Frontend task completed" -ForegroundColor Green
            }
            catch {
                Write-Host "Error: $_" -ForegroundColor Red
            }
        }
    }

    Start-Sleep -Seconds 10
}