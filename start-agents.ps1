# start-agents.ps1

# Запуск Backend агента
Start-Process powershell -ArgumentList @"
-NoExit -Command `"
    Set-Location 'C:\www.spa.com'
    . .\agent-monitor.ps1 -Role 'BACKEND' -Color 'Green'
`"
"@ -WindowStyle Normal

Start-Sleep -Seconds 1

# Запуск Frontend агента
Start-Process powershell -ArgumentList @"
-NoExit -Command `"
    Set-Location 'C:\www.spa.com'
    . .\agent-monitor.ps1 -Role 'FRONTEND' -Color 'Cyan'
`"
"@ -WindowStyle Normal

Start-Sleep -Seconds 1

# Запуск DevOps агента
Start-Process powershell -ArgumentList @"
-NoExit -Command `"
    Set-Location 'C:\www.spa.com'
    . .\agent-monitor.ps1 -Role 'DEVOPS' -Color 'Yellow'
`"
"@ -WindowStyle Normal

Write-Host "All agents started!" -ForegroundColor Green