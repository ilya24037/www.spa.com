# Переходим в папку проекта
Set-Location "C:\www.spa.com"

Write-Host ""
Write-Host "AI Context Generator" -ForegroundColor Green
Write-Host "===================" -ForegroundColor Green
Write-Host ""

do {
    Write-Host "Choose option:" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "[1] Quick context + open file" -ForegroundColor Yellow
    Write-Host "[2] Normal analysis" -ForegroundColor Yellow
    Write-Host "[3] Full analysis" -ForegroundColor Yellow
    Write-Host "[4] Auto mode" -ForegroundColor Yellow
    Write-Host "[5] Open existing file" -ForegroundColor Yellow
    Write-Host "[0] Exit" -ForegroundColor Red
    Write-Host ""
    
    $choice = Read-Host "Enter choice (0-5)"
    
    switch ($choice) {
        "1" {
            Clear-Host
            Write-Host "Creating quick report..." -ForegroundColor Cyan
            Write-Host ""
            try {
                & php artisan ai:context --quick
                Write-Host ""
                if (Test-Path "AI_CONTEXT.md") {
                    Write-Host "Done! Opening file..." -ForegroundColor Green
                    Start-Process notepad "AI_CONTEXT.md"
                    Write-Host ""
                    Write-Host "COPY ALL TEXT (Ctrl+A, Ctrl+C) AND PASTE TO AI CHAT" -ForegroundColor Yellow
                } else {
                    Write-Host "ERROR: AI_CONTEXT.md not created" -ForegroundColor Red
                }
            } catch {
                Write-Host "Error: $_" -ForegroundColor Red
            }
            Write-Host ""
            Write-Host "Press any key to return to menu..."
            $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
            Clear-Host
        }
        "2" {
            Clear-Host
            Write-Host "Running normal analysis..." -ForegroundColor Cyan
            Write-Host ""
            try {
                & php artisan ai:context
                Write-Host ""
                if (Test-Path "AI_CONTEXT.md") {
                    Write-Host "Analysis complete! Opening file..." -ForegroundColor Green
                    Start-Process notepad "AI_CONTEXT.md"
                } else {
                    Write-Host "ERROR: File not created" -ForegroundColor Red
                }
            } catch {
                Write-Host "Error: $_" -ForegroundColor Red
            }
            Write-Host ""
            Write-Host "Press any key to return to menu..."
            $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
            Clear-Host
        }
        "3" {
            Clear-Host
            Write-Host "Full project analysis..." -ForegroundColor Cyan
            Write-Host "This will take some time..." -ForegroundColor Yellow
            Write-Host ""
            try {
                & php artisan ai:context --full
                Write-Host ""
                if (Test-Path "AI_CONTEXT.md") {
                    Write-Host "Full analysis ready! Opening file..." -ForegroundColor Green
                    Start-Process notepad "AI_CONTEXT.md"
                    Write-Host ""
                    Write-Host "File contains:" -ForegroundColor Yellow
                    Write-Host "- Detailed project structure"
                    Write-Host "- Analysis of all components"
                    Write-Host "- Code quality metrics"
                    Write-Host "- Full recommendations"
                } else {
                    Write-Host "ERROR: File not created" -ForegroundColor Red
                }
            } catch {
                Write-Host "Error: $_" -ForegroundColor Red
            }
            Write-Host ""
            Write-Host "Press any key to return to menu..."
            $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
            Clear-Host
        }
        "4" {
            Clear-Host
            Write-Host "Auto generation..." -ForegroundColor Cyan
            try {
                & php artisan ai:context --auto
                Write-Host ""
                Write-Host "Context updated in AI_CONTEXT.md" -ForegroundColor Green
            } catch {
                Write-Host "Error: $_" -ForegroundColor Red
            }
            Write-Host ""
            Write-Host "Press any key to return to menu..."
            $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
            Clear-Host
        }
        "5" {
            Clear-Host
            Write-Host "Opening AI_CONTEXT.md..." -ForegroundColor Cyan
            if (Test-Path "AI_CONTEXT.md") {
                Start-Process notepad "AI_CONTEXT.md"
                Write-Host "File opened!" -ForegroundColor Green
                Write-Host ""
                Write-Host "Copy all text and paste to AI chat" -ForegroundColor Yellow
            } else {
                Write-Host "File not found. Create context first (option 1-3)" -ForegroundColor Red
            }
            Write-Host ""
            Write-Host "Press any key to return to menu..."
            $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
            Clear-Host
        }
        "0" {
            Write-Host "Goodbye!" -ForegroundColor Green
            break
        }
        default {
            Write-Host "Invalid choice. Please enter 0-5" -ForegroundColor Red
            Start-Sleep -Seconds 1
            Clear-Host
        }
    }
} while ($choice -ne "0")