# SPA Platform Claude Code Launcher
# Запускает Claude Code в корне проекта

Set-Location "C:\www.spa.com"
Write-Host "Starting Claude Code for SPA Platform..." -ForegroundColor Cyan
Write-Host "Working directory: $(Get-Location)" -ForegroundColor Green
Write-Host ""
claude --dangerously-skip-permissions