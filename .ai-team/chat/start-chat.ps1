# AI Team Chat Launcher
# Запуск чата с выбором режима

Clear-Host
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "     🚀 AI TEAM CHAT LAUNCHER" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Select chat mode:" -ForegroundColor Yellow
Write-Host ""
Write-Host "  1. " -NoNewline
Write-Host "Simple Chat" -ForegroundColor Green
Write-Host "     Basic chat between You, Claude, and Cursor"
Write-Host ""
Write-Host "  2. " -NoNewline
Write-Host "Integrated Chat" -ForegroundColor Magenta
Write-Host "     Full integration with Virtual Office (.ai-team)"
Write-Host "     Includes all AI agents from Virtual Office"
Write-Host ""
Write-Host "  3. " -NoNewline
Write-Host "Web Interface" -ForegroundColor Cyan
Write-Host "     Open HTML chat in browser"
Write-Host ""
Write-Host "  4. " -NoNewline
Write-Host "Monitor Mode" -ForegroundColor Yellow
Write-Host "     Open Virtual Office Monitor"
Write-Host ""

$choice = Read-Host "Enter your choice (1-4)"

switch ($choice) {
    "1" {
        Write-Host "`nStarting Simple Chat..." -ForegroundColor Green
        & "$PSScriptRoot\chat.ps1"
    }
    "2" {
        Write-Host "`nStarting Integrated Chat..." -ForegroundColor Magenta
        Write-Host "Connecting to Virtual Office..." -ForegroundColor DarkGray

        # Check if Python is available
        try {
            $pythonVersion = python --version 2>&1
            Write-Host "Python found: $pythonVersion" -ForegroundColor DarkGray
            python "$PSScriptRoot\team-chat-integrated.py"
        } catch {
            Write-Host "Python not found. Falling back to PowerShell chat." -ForegroundColor Yellow
            & "$PSScriptRoot\chat.ps1"
        }
    }
    "3" {
        Write-Host "`nOpening Web Interface..." -ForegroundColor Cyan
        Start-Process "$PSScriptRoot\team-chat.html"
    }
    "4" {
        Write-Host "`nStarting Virtual Office Monitor..." -ForegroundColor Yellow
        $monitorPath = "C:\www.spa.com\.ai-team\virtual-office\monitor.py"
        if (Test-Path $monitorPath) {
            python $monitorPath
        } else {
            Write-Host "Monitor not found at: $monitorPath" -ForegroundColor Red
        }
    }
    default {
        Write-Host "`nInvalid choice. Exiting." -ForegroundColor Red
    }
}

Write-Host "`nPress any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")