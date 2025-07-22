# SPA Platform Restart Script
# Перезапуск всех сервисов проекта

param(
    [Parameter(Mandatory=$false)]
    [switch]$Force,
    
    [Parameter(Mandatory=$false)]
    [switch]$SkipBackup
)

# Цвета для вывода
$Colors = @{
    Success = "Green"
    Warning = "Yellow"
    Error = "Red"
    Info = "Cyan"
}

function Write-ColorOutput {
    param(
        [string]$Message,
        [string]$Color = "White"
    )
    Write-Host $Message -ForegroundColor $Colors[$Color]
}

function Stop-Services {
    Write-ColorOutput "🛑 Остановка сервисов..." "Info"
    
    # Остановка Laravel сервера
    $laravelProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object { 
        $_.CommandLine -like "*artisan serve*" 
    }
    
    if ($laravelProcesses) {
        foreach ($process in $laravelProcesses) {
            Write-ColorOutput "Остановка Laravel процесса (PID: $($process.Id))" "Info"
            Stop-Process -Id $process.Id -Force
        }
    }
    
    # Остановка Vite сервера
    $viteProcesses = Get-Process -Name "node" -ErrorAction SilentlyContinue | Where-Object { 
        $_.CommandLine -like "*vite*" 
    }
    
    if ($viteProcesses) {
        foreach ($process in $viteProcesses) {
            Write-ColorOutput "Остановка Vite процесса (PID: $($process.Id))" "Info"
            Stop-Process -Id $process.Id -Force
        }
    }
    
    Write-ColorOutput "✅ Все сервисы остановлены" "Success"
}

function Start-Services {
    Write-ColorOutput "🚀 Запуск сервисов..." "Info"
    
    # Запуск Laravel сервера
    Write-ColorOutput "Запуск Laravel сервера..." "Info"
    Start-Process -FilePath "php" -ArgumentList "artisan", "serve" -WindowStyle Minimized
    
    # Небольшая пауза
    Start-Sleep -Seconds 2
    
    # Запуск Vite сервера
    Write-ColorOutput "Запуск Vite dev сервера..." "Info"
    Start-Process -FilePath "npm" -ArgumentList "run", "dev" -WindowStyle Minimized
    
    Write-ColorOutput "✅ Все сервисы запущены" "Success"
}

function Clear-Cache {
    Write-ColorOutput "🧹 Очистка кеша..." "Info"
    
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
    
    # Очистка кеша Vite
    if (Test-Path "node_modules/.vite") {
        Remove-Item "node_modules/.vite" -Recurse -Force
    }
    
    Write-ColorOutput "✅ Кеш очищен" "Success"
}

function Backup-Database {
    if ($SkipBackup) {
        Write-ColorOutput "⏭️ Пропуск резервной копии" "Warning"
        return
    }
    
    Write-ColorOutput "💾 Создание резервной копии БД..." "Info"
    
    $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
    
    # Создаем папку если не существует
    New-Item -ItemType Directory -Force -Path "storage/backups" | Out-Null
    
    # Экспорт SQLite БД (если используется)
    if (Test-Path "database/database.sqlite") {
        Copy-Item "database/database.sqlite" "storage/backups/database_$timestamp.sqlite"
        Write-ColorOutput "✅ Резервная копия создана: storage/backups/database_$timestamp.sqlite" "Success"
    }
}

function Test-Services {
    Write-ColorOutput "🔍 Проверка сервисов..." "Info"
    
    # Проверка Laravel
    try {
        $response = Invoke-WebRequest -Uri "http://127.0.0.1:8000" -TimeoutSec 10 -ErrorAction Stop
        if ($response.StatusCode -eq 200) {
            Write-ColorOutput "✅ Laravel сервер работает" "Success"
        }
    } catch {
        Write-ColorOutput "❌ Laravel сервер не отвечает" "Error"
    }
    
    # Проверка Vite
    try {
        $response = Invoke-WebRequest -Uri "http://127.0.0.1:5173" -TimeoutSec 10 -ErrorAction Stop
        if ($response.StatusCode -eq 200) {
            Write-ColorOutput "✅ Vite сервер работает" "Success"
        }
    } catch {
        Write-ColorOutput "❌ Vite сервер не отвечает" "Error"
    }
}

# Основная логика
Write-ColorOutput "🔄 Перезапуск SPA Platform" "Info"

try {
    Backup-Database
    Stop-Services
    Clear-Cache
    Start-Sleep -Seconds 3
    Start-Services
    Start-Sleep -Seconds 5
    Test-Services
    
    Write-ColorOutput "`n🎉 Перезапуск завершен успешно!" "Success"
    Write-ColorOutput "`n🌐 Доступные URL:" "Info"
    Write-ColorOutput "   • Laravel: http://127.0.0.1:8000" "Info"
    Write-ColorOutput "   • Vite: http://127.0.0.1:5173" "Info"
    
} catch {
    Write-ColorOutput "❌ Ошибка перезапуска: $($_.Exception.Message)" "Error"
    exit 1
} 