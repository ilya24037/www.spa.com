# QA Agent with Testing Knowledge
$ErrorActionPreference = 'SilentlyContinue'

Write-Host "Starting QA Agent with testing expertise..." -ForegroundColor Cyan

while ($true) {
    # Check inbox
    $inbox = Get-ChildItem "virtual-office\inbox\qa" -Filter "*.json" 2>$null

    if ($inbox) {
        foreach ($msg in $inbox) {
            try {
                $content = Get-Content $msg.FullName -Raw | ConvertFrom-Json
                Write-Host "Processing QA task: $($content.message)" -ForegroundColor Yellow

                # Process with enhanced instructions
                $prompt = @"
You are QA engineer for SPA Platform.
Use testing checklist from CLAUDE_ENHANCED.md.

Task: $($content.message)

Check for known issues:
- Missing watchers (data loss on navigation)
- Missing fields in $fillable (save failures)
- Status validation too strict
- Double form submission
- Undefined errors in components
"@

                # Call Claude
                if (Test-Path "qa\CLAUDE_ENHANCED.md") {
                    claude chat --file "qa\CLAUDE_ENHANCED.md" "$prompt" > "virtual-office\outbox\qa_report_$(Get-Date -Format 'yyyyMMdd_HHmmss').txt"
                }

                # Move processed
                Move-Item $msg.FullName "virtual-office\outbox\" -Force
                Write-Host "QA testing completed" -ForegroundColor Green
            }
            catch {
                Write-Host "Error: $_" -ForegroundColor Red
            }
        }
    }

    Start-Sleep -Seconds 10
}