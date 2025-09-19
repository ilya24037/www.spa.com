# DevOps Agent for Windows Environment
$ErrorActionPreference = 'SilentlyContinue'

Write-Host "Starting DevOps Agent..." -ForegroundColor Cyan

while ($true) {
    # Check inbox
    $inbox = Get-ChildItem "virtual-office\inbox\devops" -Filter "*.json" 2>$null

    if ($inbox) {
        foreach ($msg in $inbox) {
            try {
                $content = Get-Content $msg.FullName -Raw | ConvertFrom-Json
                Write-Host "Processing DevOps task: $($content.message)" -ForegroundColor Yellow

                # Process task
                $prompt = @"
You are DevOps engineer for SPA Platform (Windows environment).

Task: $($content.message)

Consider:
- Windows/PowerShell environment
- Docker Desktop for Windows
- Laravel deployment
- Vue.js build process
"@

                # Call Claude
                claude chat "$prompt" > "virtual-office\outbox\devops_response_$(Get-Date -Format 'yyyyMMdd_HHmmss').txt"

                # Move processed
                Move-Item $msg.FullName "virtual-office\outbox\" -Force
                Write-Host "DevOps task completed" -ForegroundColor Green
            }
            catch {
                Write-Host "Error: $_" -ForegroundColor Red
            }
        }
    }

    Start-Sleep -Seconds 10
}