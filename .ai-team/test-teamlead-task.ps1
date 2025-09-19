# Send test task to TeamLead agent
Write-Host "Sending test task to TeamLead agent..." -ForegroundColor Cyan

$task = @{
    from = "ceo"
    to = "teamlead"
    title = "План рефакторинга MasterCard"
    message = "Нужно спланировать рефакторинг компонента MasterCard с учетом FSD архитектуры. Использовать знания из docs/LESSONS и применить принцип KISS."
    priority = "high"
    timestamp = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
} | ConvertTo-Json

# Save task to TeamLead inbox
$taskFile = "virtual-office\inbox\teamlead\refactoring_task_$(Get-Date -Format 'yyyyMMdd_HHmmss').json"
$task | Out-File -FilePath $taskFile -Encoding UTF8

Write-Host "✅ Task sent successfully!" -ForegroundColor Green
Write-Host "📁 Task file: $taskFile" -ForegroundColor Yellow
Write-Host ""
Write-Host "TeamLead should process this task within 10 seconds..." -ForegroundColor Gray
Write-Host "Check response in: virtual-office\outbox\" -ForegroundColor Cyan

# Wait and check for response
Write-Host ""
Write-Host "Waiting for response..." -ForegroundColor Gray
Start-Sleep -Seconds 15

# Check if response exists
$responses = Get-ChildItem "virtual-office\outbox" -Filter "*response*.txt" -ErrorAction SilentlyContinue |
    Sort-Object LastWriteTime -Descending |
    Select-Object -First 1

if ($responses) {
    Write-Host ""
    Write-Host "✅ Response found: $($responses.Name)" -ForegroundColor Green
    Write-Host "Content preview:" -ForegroundColor Yellow
    Get-Content $responses.FullName -First 10 | ForEach-Object { Write-Host $_ }
} else {
    Write-Host "⚠️ No response yet. Check virtual-office\outbox\ manually" -ForegroundColor Yellow
}