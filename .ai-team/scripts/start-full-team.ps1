# Full AI Team Launcher PowerShell Script
Clear-Host

Write-Host "========================================" -ForegroundColor Magenta
Write-Host "    FULL AI TEAM WITH TEAMLEAD" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Magenta
Write-Host ""

Write-Host "Starting complete AI team:" -ForegroundColor Cyan
Write-Host "  - TeamLead/Director (coordinator)" -ForegroundColor White
Write-Host "  - Backend Developer" -ForegroundColor Green
Write-Host "  - Frontend Developer" -ForegroundColor Cyan
Write-Host "  - DevOps Engineer" -ForegroundColor Yellow
Write-Host ""

Write-Host "Press any key to start..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

Write-Host ""
Write-Host "[1/4] Starting TeamLead/Director..." -ForegroundColor Magenta
Start-Process powershell -ArgumentList "-NoExit", "-File", "C:\www.spa.com\.ai-team\scripts\launch-teamlead.ps1" -WindowStyle Normal
Start-Sleep -Seconds 3

Write-Host "[2/4] Starting Backend Developer..." -ForegroundColor Green
Start-Process powershell -ArgumentList "-NoExit", "-File", "C:\www.spa.com\.ai-team\scripts\launch-real-backend.ps1" -WindowStyle Normal
Start-Sleep -Seconds 3

Write-Host "[3/4] Starting Frontend Developer..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-File", "C:\www.spa.com\.ai-team\scripts\launch-real-frontend.ps1" -WindowStyle Normal
Start-Sleep -Seconds 3

Write-Host "[4/4] Starting DevOps Engineer..." -ForegroundColor Yellow
Start-Process powershell -ArgumentList "-NoExit", "-File", "C:\www.spa.com\.ai-team\scripts\launch-real-devops.ps1" -WindowStyle Normal

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "     FULL TEAM LAUNCHED!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

Write-Host "Team hierarchy:" -ForegroundColor Cyan
Write-Host "  TeamLead - coordinates all tasks" -ForegroundColor Magenta
Write-Host "    - Backend - API and server logic" -ForegroundColor Green
Write-Host "    - Frontend - UI and components" -ForegroundColor Cyan
Write-Host "    - DevOps - infrastructure" -ForegroundColor Yellow
Write-Host ""

Write-Host "Test with: @teamlead assign task to analyze favorites page" -ForegroundColor Yellow
Write-Host ""

Write-Host "Press any key to exit..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")