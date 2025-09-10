# AI Team Automation Script
# Автоматизация работы AI команды через PowerShell

# Конфигурация
$chatFile = "C:\www.spa.com\.ai-team\chat.md"
$checkInterval = 2 # секунды

# Функция для добавления сообщения в чат
function Write-ToChat {
    param(
        [string]$role,
        [string]$message
    )
    $timestamp = Get-Date -Format "HH:mm"
    $entry = "[$timestamp] [$role]: $message"
    Add-Content -Path $chatFile -Value $entry
}

# Функция для чтения последних сообщений
function Read-ChatMessages {
    param(
        [int]$lastLines = 10
    )
    if (Test-Path $chatFile) {
        Get-Content -Path $chatFile -Tail $lastLines
    }
}

# Функция для проверки упоминаний
function Check-Mentions {
    param(
        [string[]]$messages,
        [string]$role
    )
    $mentions = @()
    $roleLower = $role.ToLower()
    
    foreach ($msg in $messages) {
        if ($msg -match "@$roleLower" -or $msg -match "@all") {
            $mentions += $msg
        }
    }
    return $mentions
}

# Симулятор Backend агента
function Start-BackendAgent {
    Write-Host "Starting Backend Agent..." -ForegroundColor Green
    Write-ToChat "BACKEND" "Backend developer ready. Monitoring for tasks. Expertise: Laravel 12, DDD architecture."
    
    while ($true) {
        $messages = Read-ChatMessages -lastLines 20
        $mentions = Check-Mentions -messages $messages -role "backend"
        
        if ($mentions.Count -gt 0) {
            $lastMention = $mentions[-1]
            
            # Проверяем разные типы команд
            if ($lastMention -match "готов к работе") {
                Write-ToChat "BACKEND" "✅ Готов к работе. Мониторю чат каждые 2 секунды."
            }
            elseif ($lastMention -match "создай модель (\w+)") {
                $modelName = $matches[1]
                Write-ToChat "BACKEND" "🔄 working - Создаю модель $modelName"
                Start-Sleep -Seconds 3
                Write-ToChat "BACKEND" "✅ done - Модель $modelName создана в Domain/$modelName/Models/"
            }
        }
        
        Start-Sleep -Seconds $checkInterval
    }
}

# Симулятор Frontend агента
function Start-FrontendAgent {
    Write-Host "Starting Frontend Agent..." -ForegroundColor Blue
    Write-ToChat "FRONTEND" "Frontend developer online. Ready for Vue 3, TypeScript, and FSD architecture tasks."
    
    while ($true) {
        $messages = Read-ChatMessages -lastLines 20
        $mentions = Check-Mentions -messages $messages -role "frontend"
        
        if ($mentions.Count -gt 0) {
            $lastMention = $mentions[-1]
            
            if ($lastMention -match "готов к работе") {
                Write-ToChat "FRONTEND" "✅ Готов к работе. Мониторю чат каждые 2 секунды."
            }
            elseif ($lastMention -match "создай компонент (\w+)") {
                $componentName = $matches[1]
                Write-ToChat "FRONTEND" "🔄 working - Создаю компонент $componentName"
                Start-Sleep -Seconds 3
                Write-ToChat "FRONTEND" "✅ done - Компонент $componentName создан в entities/"
            }
        }
        
        Start-Sleep -Seconds $checkInterval
    }
}

# Симулятор DevOps агента
function Start-DevOpsAgent {
    Write-Host "Starting DevOps Agent..." -ForegroundColor Yellow
    Write-ToChat "DEVOPS" "DevOps engineer online. Ready for Docker, CI/CD, and infrastructure tasks."
    
    while ($true) {
        $messages = Read-ChatMessages -lastLines 20
        $mentions = Check-Mentions -messages $messages -role "devops"
        
        if ($mentions.Count -gt 0) {
            $lastMention = $mentions[-1]
            
            if ($lastMention -match "готов к работе") {
                Write-ToChat "DEVOPS" "✅ Готов к работе. Мониторю чат каждые 2 секунды."
            }
            elseif ($lastMention -match "проверь") {
                Write-ToChat "DEVOPS" "🔄 working - Проверяю инфраструктуру"
                Start-Sleep -Seconds 2
                Write-ToChat "DEVOPS" "✅ done - Все сервисы работают нормально"
            }
        }
        
        Start-Sleep -Seconds $checkInterval
    }
}

# Главное меню
Write-Host @"
╔══════════════════════════════════════╗
║     AI Team Automation Script        ║
╚══════════════════════════════════════╝
"@ -ForegroundColor Cyan

Write-Host "`nВыберите агента для запуска:" -ForegroundColor White
Write-Host "1. Backend Agent" -ForegroundColor Green
Write-Host "2. Frontend Agent" -ForegroundColor Blue
Write-Host "3. DevOps Agent" -ForegroundColor Yellow
Write-Host "4. Запустить всех (в отдельных окнах)" -ForegroundColor Magenta

$choice = Read-Host "`nВаш выбор (1-4)"

switch ($choice) {
    "1" { Start-BackendAgent }
    "2" { Start-FrontendAgent }
    "3" { Start-DevOpsAgent }
    "4" {
        # Запуск в отдельных окнах PowerShell
        Start-Process powershell -ArgumentList "-NoExit", "-Command", "& '$PSCommandPath' -Agent Backend" -WindowStyle Normal
        Start-Process powershell -ArgumentList "-NoExit", "-Command", "& '$PSCommandPath' -Agent Frontend" -WindowStyle Normal
        Start-Process powershell -ArgumentList "-NoExit", "-Command", "& '$PSCommandPath' -Agent DevOps" -WindowStyle Normal
        Write-Host "Все агенты запущены в отдельных окнах!" -ForegroundColor Green
    }
    default {
        Write-Host "Неверный выбор!" -ForegroundColor Red
    }
}

# Поддержка запуска с параметрами
if ($args.Count -eq 2 -and $args[0] -eq "-Agent") {
    switch ($args[1]) {
        "Backend" { Start-BackendAgent }
        "Frontend" { Start-FrontendAgent }
        "DevOps" { Start-DevOpsAgent }
    }
}