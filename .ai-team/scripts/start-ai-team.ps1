# AI Team Launcher PowerShell Script
# Set UTF-8 encoding
chcp 65001 | Out-Null
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "    AI TEAM LAUNCHER - SPA PLATFORM" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Cyan

# Function to start AI assistant
function Start-AIAssistant {
    param(
        [string]$Title,
        [string]$Role,
        [string]$Folder,
        [string]$Color,
        [string]$Mention
    )
    
    # Используем cmd.exe для запуска claude, как в рабочем bat файле
    $cmdScript = @"
@echo off
chcp 65001 >nul
cd /d "C:\www.spa.com\.ai-team\$Folder"
echo $Role STARTING...
claude --dangerously-skip-permissions "You are $Role for SPA Platform. Read CLAUDE.md in current folder for your instructions. Monitor ../chat.md file every 30 seconds. Execute tasks with @$Mention or @all mentions. Write results back to chat in format [HH:MM] [$($Mention.ToUpper())]: message. Start with greeting in chat."
"@
    
    # Создаем временный bat файл
    $tempBat = [System.IO.Path]::GetTempFileName() + ".bat"
    $cmdScript | Out-File -FilePath $tempBat -Encoding ASCII
    
    # Запускаем через cmd
    Start-Process cmd -ArgumentList "/k", $tempBat -WindowStyle Normal
}

# Start Backend Developer
Write-Host "Starting Backend Developer..." -ForegroundColor Green
Start-AIAssistant -Title "AI Backend Developer" -Role "Backend Developer" -Folder "backend" -Color "Green" -Mention "backend"
Start-Sleep -Seconds 2

# Start Frontend Developer
Write-Host "Starting Frontend Developer..." -ForegroundColor Cyan
Start-AIAssistant -Title "AI Frontend Developer" -Role "Frontend Developer" -Folder "frontend" -Color "Cyan" -Mention "frontend"
Start-Sleep -Seconds 2

# Start DevOps Engineer
Write-Host "Starting DevOps Engineer..." -ForegroundColor Yellow
Start-AIAssistant -Title "AI DevOps Engineer" -Role "DevOps Engineer" -Folder "devops" -Color "Yellow" -Mention "devops"
Start-Sleep -Seconds 2

# Start Chat Monitor
Write-Host "Starting Chat Monitor..." -ForegroundColor Magenta
$chatMonitor = @"
chcp 65001 | Out-Null
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
cd 'C:\www.spa.com'
Write-Host 'CHAT MONITOR ACTIVE' -ForegroundColor Magenta
Write-Host '===================='
Write-Host ''
Get-Content '.ai-team\chat.md' -Wait -Tail 20 -Encoding UTF8
"@
Start-Process powershell -ArgumentList "-NoExit", "-Command", $chatMonitor -WindowStyle Normal
Start-Sleep -Seconds 2

# Start Control Center
Write-Host "Starting Control Center..." -ForegroundColor White
Start-Process powershell -ArgumentList "-NoExit", "-File", "C:\www.spa.com\control-center.ps1" -WindowStyle Normal

Write-Host "`n✅ AI Team started!" -ForegroundColor Green
Write-Host "5 windows opened:" -ForegroundColor Yellow
Write-Host "  1. Backend Developer" -ForegroundColor White
Write-Host "  2. Frontend Developer" -ForegroundColor White
Write-Host "  3. DevOps Engineer" -ForegroundColor White
Write-Host "  4. Chat Monitor" -ForegroundColor White
Write-Host "  5. Control Center" -ForegroundColor White
Write-Host "`nUse Control Center to manage the team!" -ForegroundColor Cyan