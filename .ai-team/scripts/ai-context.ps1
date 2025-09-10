param(
    [string]$Mode = "quick"
)

# Переходим в папку проекта
Set-Location "C:\www.spa.com"

Write-Host ""
Write-Host "AI Context Generator" -ForegroundColor Green
Write-Host "===================" -ForegroundColor Green
Write-Host ""

Write-Host "Current directory: $(Get-Location)" -ForegroundColor Yellow
Write-Host ""

# Выполняем команду
Write-Host "Generating context..." -ForegroundColor Cyan
try {
    & php artisan ai:context --$Mode
    Write-Host ""
    Write-Host "Command completed successfully!" -ForegroundColor Green
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
    Write-Host ""
}

# Проверяем файл
if (Test-Path "AI_CONTEXT.md") {
    Write-Host "AI_CONTEXT.md file created!" -ForegroundColor Green
    Write-Host "Opening file..." -ForegroundColor Yellow
    Start-Process notepad "AI_CONTEXT.md"
    Write-Host ""
    Write-Host "COPY ALL TEXT (Ctrl+A, Ctrl+C) AND PASTE TO AI CHAT" -ForegroundColor Yellow
} else {
    Write-Host "ERROR: AI_CONTEXT.md not found" -ForegroundColor Red
}

Write-Host ""
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")