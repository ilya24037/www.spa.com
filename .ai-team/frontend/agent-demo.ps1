# Frontend Agent Demo - Works without Claude CLI
$ErrorActionPreference = 'SilentlyContinue'

Write-Host @"
============================================================
           FRONTEND AGENT - DEMO VERSION
============================================================
Vue 3 + TypeScript + Feature-Sliced Design Expert
Knowledge Base: 107+ documents loaded
============================================================
"@ -ForegroundColor Cyan

Write-Host "`nAgent capabilities:" -ForegroundColor Yellow
Write-Host "- Analyzes Vue components for missing watchers"
Write-Host "- Checks FSD architecture compliance"
Write-Host "- Reviews TypeScript types"
Write-Host "- Validates data chain (component->emit->backend)"
Write-Host ""

Write-Host "Monitoring inbox: virtual-office\inbox\frontend\" -ForegroundColor Green
Write-Host ""

# Create demo response function
function Process-Task {
    param($task)

    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Processing task: $($task.title)" -ForegroundColor Yellow

    # Simulate analysis based on task
    $response = @"
# Analysis Report for: $($task.title)

## Task Analysis
Received task from: $($task.from)
Task: $($task.message)

## Applied Knowledge
Using knowledge from docs/LESSONS:
- BUSINESS_LOGIC_FIRST.md
- Watcher chain validation pattern
- FSD architecture rules

## Recommendations
1. Check all reactive data has watchers
2. Verify emit chain to backend
3. Ensure $fillable includes all fields
4. Follow KISS principle

## Code Review Points
- Missing watchers can cause data loss
- Always use TypeScript strict mode
- Protect against undefined with computed

Generated at: $(Get-Date)
By: Frontend Agent (Demo Mode)
"@

    # Save response
    $outputFile = "virtual-office\outbox\frontend_demo_$(Get-Date -Format 'yyyyMMdd_HHmmss').txt"
    $response | Out-File -FilePath $outputFile -Encoding UTF8

    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Response saved to: $outputFile" -ForegroundColor Green
    return $outputFile
}

# Main monitoring loop
Write-Host "Starting monitoring loop..." -ForegroundColor Cyan
Write-Host "Place .json files in virtual-office\inbox\frontend\ to process" -ForegroundColor Yellow
Write-Host "Press Ctrl+C to stop`n" -ForegroundColor Gray

while ($true) {
    # Check inbox
    $inbox = Get-ChildItem "virtual-office\inbox\frontend" -Filter "*.json" -ErrorAction SilentlyContinue

    if ($inbox) {
        foreach ($msg in $inbox) {
            try {
                Write-Host "`n[$(Get-Date -Format 'HH:mm:ss')] New task found!" -ForegroundColor Green

                # Read task
                $content = Get-Content $msg.FullName -Raw | ConvertFrom-Json

                # Process task
                $result = Process-Task -task $content

                # Move processed file to outbox
                Move-Item $msg.FullName "virtual-office\outbox\" -Force

                Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Task completed!" -ForegroundColor Green
                Write-Host "Original task moved to outbox" -ForegroundColor Gray

            } catch {
                Write-Host "[ERROR] Failed to process: $_" -ForegroundColor Red
            }
        }
    }

    # Show heartbeat
    Write-Host "." -NoNewline -ForegroundColor DarkGray

    # Wait before next check
    Start-Sleep -Seconds 5
}