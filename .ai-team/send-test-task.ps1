# Script to send test task to agents
param(
    [string]$Agent = "frontend",
    [string]$Task = "Проверить компонент MasterCard на наличие watchers"
)

$timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

$taskJson = @{
    from = "user"
    to = $Agent
    title = "Test Task"
    message = $Task
    priority = "normal"
    timestamp = $timestamp
} | ConvertTo-Json

$fileName = "task_$(Get-Date -Format 'yyyyMMdd_HHmmss').json"
$filePath = "virtual-office\inbox\$Agent\$fileName"

# Save task
$taskJson | Out-File -FilePath $filePath -Encoding UTF8

Write-Host "Task sent to $Agent agent!" -ForegroundColor Green
Write-Host "Task: $Task" -ForegroundColor Yellow
Write-Host "File: $filePath" -ForegroundColor Gray
Write-Host ""
Write-Host "Check response in: virtual-office\outbox\" -ForegroundColor Cyan