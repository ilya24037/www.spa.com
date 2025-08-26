# Team Dashboard - Real-time monitoring
chcp 65001 | Out-Null
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

Clear-Host

while ($true) {
    Clear-Host
    
    Write-Host "╔══════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
    Write-Host "║           AI TEAM DASHBOARD - SPA PLATFORM              ║" -ForegroundColor Cyan
    Write-Host "╚══════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
    Write-Host ""
    
    # Время
    $currentTime = Get-Date -Format "HH:mm:ss"
    Write-Host "⏰ Time: $currentTime" -ForegroundColor Yellow
    Write-Host ""
    
    # Последние статусы
    Write-Host "📊 LAST STATUS FROM EACH ROLE:" -ForegroundColor Green
    Write-Host "================================" -ForegroundColor Green
    
    $chatContent = Get-Content '.ai-team\chat.md' -Encoding UTF8 -Tail 100
    
    # Backend status
    $backendLast = $chatContent | Where-Object { $_ -match '\[BACKEND\]' } | Select-Object -Last 1
    if ($backendLast) {
        Write-Host "BACKEND: " -NoNewline -ForegroundColor Blue
        Write-Host $backendLast.Substring($backendLast.IndexOf('[BACKEND]:') + 10)
    }
    
    # Frontend status  
    $frontendLast = $chatContent | Where-Object { $_ -match '\[FRONTEND\]' } | Select-Object -Last 1
    if ($frontendLast) {
        Write-Host "FRONTEND: " -NoNewline -ForegroundColor Cyan
        Write-Host $frontendLast.Substring($frontendLast.IndexOf('[FRONTEND]:') + 11)
    }
    
    # DevOps status
    $devopsLast = $chatContent | Where-Object { $_ -match '\[DEVOPS\]' } | Select-Object -Last 1
    if ($devopsLast) {
        Write-Host "DEVOPS: " -NoNewline -ForegroundColor Yellow
        Write-Host $devopsLast.Substring($devopsLast.IndexOf('[DEVOPS]:') + 9)
    }
    
    Write-Host ""
    Write-Host "📝 RECENT ACTIVITY (Last 5):" -ForegroundColor Magenta
    Write-Host "================================" -ForegroundColor Magenta
    
    $recentMessages = $chatContent | Select-Object -Last 5
    foreach ($msg in $recentMessages) {
        if ($msg -match '\[BACKEND\]') { 
            Write-Host $msg -ForegroundColor Blue 
        }
        elseif ($msg -match '\[FRONTEND\]') { 
            Write-Host $msg -ForegroundColor Cyan 
        }
        elseif ($msg -match '\[DEVOPS\]') { 
            Write-Host $msg -ForegroundColor Yellow 
        }
        elseif ($msg -match '\[PM\]') { 
            Write-Host $msg -ForegroundColor White 
        }
        else { 
            Write-Host $msg -ForegroundColor Gray 
        }
    }
    
    Write-Host ""
    Write-Host "Press Ctrl+C to exit | Auto-refresh every 5 seconds" -ForegroundColor DarkGray
    
    Start-Sleep -Seconds 5
}