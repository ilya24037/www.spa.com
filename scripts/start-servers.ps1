# SPA Platform - Запуск серверов
# Простой скрипт для запуска Laravel и Vite серверов

param(
    [Parameter(Mandatory=$false)]
    [switch]$LaravelOnly,
    
    [Parameter(Mandatory=$false)]
    [switch]$ViteOnly
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

function Start-LaravelServer {
    Write-ColorOutput "🚀 Запуск Laravel сервера..." "Info"
    
    # Проверяем, не запущен ли уже сервер
    $laravelProcess = Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object { 
        $_.CommandLine -like "*artisan serve*" 
    }
    
    if ($laravelProcess) {
        Write-ColorOutput "⚠️ Laravel сервер уже запущен (PID: $($laravelProcess.Id))" "Warning"
        return
    }
    
    try {
        # Запускаем Laravel сервер с полным путем к PHP
        $phpPath = "C:\php\php.exe"
        if (Test-Path $phpPath) {
            Start-Process -FilePath $phpPath -ArgumentList "artisan", "serve" -WindowStyle Minimized
            Write-ColorOutput "✅ Laravel сервер запущен на http://127.0.0.1:8000" "Success"
        } else {
            Write-ColorOutput "❌ PHP не найден по пути: $phpPath" "Error"
        }
    } catch {
        Write-ColorOutput "❌ Ошибка запуска Laravel сервера: $($_.Exception.Message)" "Error"
    }
}

function Start-ViteServer {
    Write-ColorOutput "⚡ Запуск Vite dev сервера..." "Info"
    
    # Проверяем, не запущен ли уже сервер
    $viteProcess = Get-Process -Name "node" -ErrorAction SilentlyContinue | Where-Object { 
        $_.CommandLine -like "*vite*" 
    }
    
    if ($viteProcess) {
        Write-ColorOutput "⚠️ Vite сервер уже запущен (PID: $($viteProcess.Id))" "Warning"
        return
    }
    
    try {
        # Запускаем Vite сервер
        Start-Process -FilePath "npm" -ArgumentList "run", "dev" -WindowStyle Minimized
        Write-ColorOutput "✅ Vite сервер запущен на http://127.0.0.1:5173" "Success"
    } catch {
        Write-ColorOutput "❌ Ошибка запуска Vite сервера: $($_.Exception.Message)" "Error"
    }
}

function Show-Status {
    Write-ColorOutput "`n📊 Статус серверов:" "Info"
    
    # Проверяем Laravel
    $laravelProcess = Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object { 
        $_.CommandLine -like "*artisan serve*" 
    }
    
    if ($laravelProcess) {
        Write-ColorOutput "✅ Laravel: Запущен (PID: $($laravelProcess.Id))" "Success"
    } else {
        Write-ColorOutput "❌ Laravel: Остановлен" "Error"
    }
    
    # Проверяем Vite
    $viteProcess = Get-Process -Name "node" -ErrorAction SilentlyContinue | Where-Object { 
        $_.CommandLine -like "*vite*" 
    }
    
    if ($viteProcess) {
        Write-ColorOutput "✅ Vite: Запущен (PID: $($viteProcess.Id))" "Success"
    } else {
        Write-ColorOutput "❌ Vite: Остановлен" "Error"
    }
    
    Write-ColorOutput "`n🌐 Доступные URL:" "Info"
    Write-ColorOutput "   • Laravel: http://127.0.0.1:8000" "Info"
    Write-ColorOutput "   • Vite: http://127.0.0.1:5173" "Info"
}

# Основная логика
Write-ColorOutput "🚀 Запуск серверов SPA Platform" "Info"

if ($LaravelOnly) {
    Start-LaravelServer
} elseif ($ViteOnly) {
    Start-ViteServer
} else {
    Start-LaravelServer
    Start-Sleep -Seconds 2
    Start-ViteServer
}

Show-Status

Write-ColorOutput "`n💡 Полезные команды:" "Info"
Write-ColorOutput "   • Остановка: .\scripts\stop-servers.ps1" "Info"
Write-ColorOutput "   • Перезапуск: .\scripts\restart.ps1" "Info"
Write-ColorOutput "   • Логи Laravel: Get-Content storage/logs/laravel.log -Tail 50 -Wait" "Info" 