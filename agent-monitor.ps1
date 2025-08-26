# agent-monitor.ps1
param(
    [string]$Role = "BACKEND",
    [string]$Color = "Green"
)

$ChatFile = "C:\www.spa.com\.ai-team\chat.md"
$LastProcessedLine = 0

function Write-ColorMessage {
    param([string]$Message, [string]$Color = "White")
    Write-Host $Message -ForegroundColor $Color
}

function Send-Message {
    param([string]$Message)
    $Time = Get-Date -Format "HH:mm"
    $Entry = "[$Time] [$Role]: $Message"
    Add-Content -Path $ChatFile -Value $Entry -Encoding UTF8
    Write-ColorMessage "→ Sent: $Message" "Yellow"
}

function Process-Mention {
    param([string]$Line)
    
    $roleLower = $Role.ToLower()
    
    # Проверяем упоминания
    if ($Line -match "@$roleLower" -or $Line -match "@all") {
        Write-ColorMessage "📨 Mentioned in: $Line" "Cyan"
        
        # Автоответы на команды
        if ($Line -match "status") {
            Send-Message "✅ Ready and monitoring"
        }
        elseif ($Line -match "create (.+)") {
            $task = $matches[1]
            Send-Message "🔄 working - Creating $task"
            Start-Sleep -Seconds 2
            Send-Message "✅ done - $task created successfully"
        }
        elseif ($Line -match "готов") {
            Send-Message "✅ На связи и готов к работе"
        }
    }
}

# Инициализация
Clear-Host
Write-ColorMessage "╔══════════════════════════════════════╗" $Color
Write-ColorMessage "║    $Role AGENT ACTIVE    ║" $Color
Write-ColorMessage "╚══════════════════════════════════════╝" $Color
Write-ColorMessage ""

Send-Message "🤖 $Role agent connected and monitoring"

# Главный цикл мониторинга
while ($true) {
    if (Test-Path $ChatFile) {
        $lines = Get-Content $ChatFile -Encoding UTF8
        
        # Обрабатываем новые строки
        for ($i = $LastProcessedLine; $i -lt $lines.Count; $i++) {
            $line = $lines[$i]
            
            # Пропускаем собственные сообщения
            if ($line -notmatch "\[$Role\]:") {
                Process-Mention $line
            }
        }
        
        $LastProcessedLine = $lines.Count
    }
    
    Start-Sleep -Seconds 1
}