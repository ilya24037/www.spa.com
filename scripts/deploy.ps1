# SPA Platform Deployment Script
# Поддерживает разные методы установки PowerShell

param(
    [Parameter(Mandatory=$false)]
    [ValidateSet("development", "staging", "production")]
    [string]$Environment = "development",
    
    [Parameter(Mandatory=$false)]
    [ValidateSet("winget", "msi", "zip")]
    [string]$PowerShellMethod = "winget",
    
    [Parameter(Mandatory=$false)]
    [switch]$SkipTests,
    
    [Parameter(Mandatory=$false)]
    [switch]$Force
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

function Test-PowerShellInstallation {
    param([string]$Method)
    
    Write-ColorOutput "🔍 Проверка установки PowerShell..." "Info"
    
    switch ($Method) {
        "winget" {
            try {
                $version = pwsh --version 2>$null
                if ($version) {
                    Write-ColorOutput "✅ PowerShell установлен через WinGet: $version" "Success"
                    return $true
                }
            } catch {
                Write-ColorOutput "❌ PowerShell не найден. Установите через: winget install Microsoft.PowerShell" "Error"
                return $false
            }
        }
        "msi" {
            $psPath = "${env:ProgramFiles}\PowerShell\7\pwsh.exe"
            if (Test-Path $psPath) {
                $version = & $psPath --version
                Write-ColorOutput "✅ PowerShell установлен через MSI: $version" "Success"
                return $true
            } else {
                Write-ColorOutput "❌ PowerShell MSI не найден. Скачайте с Microsoft" "Error"
                return $false
            }
        }
        "zip" {
            $psPath = "C:\PowerShell\7.5\pwsh.exe"
            if (Test-Path $psPath) {
                $version = & $psPath --version
                Write-ColorOutput "✅ PowerShell установлен через ZIP: $version" "Success"
                return $true
            } else {
                Write-ColorOutput "❌ PowerShell ZIP не найден в C:\PowerShell\7.5\" "Error"
                return $false
            }
        }
    }
    return $false
}

function Install-PowerShell {
    param([string]$Method)
    
    Write-ColorOutput "📦 Установка PowerShell методом: $Method" "Info"
    
    switch ($Method) {
        "winget" {
            Write-ColorOutput "Установка через WinGet..." "Info"
            winget install --id Microsoft.PowerShell --source winget
        }
        "msi" {
            Write-ColorOutput "Скачайте MSI с https://github.com/PowerShell/PowerShell/releases" "Warning"
            Write-ColorOutput "Затем запустите: msiexec /i PowerShell-7.5.2-win-x64.msi /quiet" "Info"
        }
        "zip" {
            Write-ColorOutput "Скачайте ZIP с https://github.com/PowerShell/PowerShell/releases" "Warning"
            Write-ColorOutput "Распакуйте в C:\PowerShell\7.5\" "Info"
        }
    }
}

function Test-ProjectRequirements {
    Write-ColorOutput "🔍 Проверка требований проекта..." "Info"
    
    $requirements = @{
        "PHP" = "php --version"
        "Composer" = "composer --version"
        "Node.js" = "node --version"
        "npm" = "npm --version"
    }
    
    $missing = @()
    
    foreach ($req in $requirements.GetEnumerator()) {
        try {
            $result = Invoke-Expression $req.Value 2>$null
            if ($result) {
                Write-ColorOutput "✅ $($req.Key): $($result.Split("`n")[0])" "Success"
            } else {
                $missing += $req.Key
            }
        } catch {
            $missing += $req.Key
        }
    }
    
    if ($missing.Count -gt 0) {
        Write-ColorOutput "❌ Отсутствуют: $($missing -join ', ')" "Error"
        return $false
    }
    
    return $true
}

function Backup-Database {
    Write-ColorOutput "💾 Создание резервной копии БД..." "Info"
    
    $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
    $backupPath = "storage/backups/db_backup_$timestamp.sql"
    
    # Создаем папку если не существует
    New-Item -ItemType Directory -Force -Path "storage/backups" | Out-Null
    
    # Экспорт SQLite БД (если используется)
    if (Test-Path "database/database.sqlite") {
        Copy-Item "database/database.sqlite" "storage/backups/database_$timestamp.sqlite"
        Write-ColorOutput "✅ Резервная копия создана: storage/backups/database_$timestamp.sqlite" "Success"
    }
}

function Update-Dependencies {
    Write-ColorOutput "📦 Обновление зависимостей..." "Info"
    
    # Composer зависимости
    Write-ColorOutput "Установка PHP зависимостей..." "Info"
    composer install --no-dev --optimize-autoloader
    
    # NPM зависимости
    Write-ColorOutput "Установка Node.js зависимостей..." "Info"
    npm install
    
    # Сборка фронтенда
    Write-ColorOutput "Сборка фронтенда..." "Info"
    npm run build
}

function Run-Migrations {
    Write-ColorOutput "🗄️ Выполнение миграций..." "Info"
    
    # Проверяем статус миграций
    $pendingMigrations = php artisan migrate:status | Select-String "No"
    
    if ($pendingMigrations) {
        Write-ColorOutput "Выполнение миграций..." "Info"
        php artisan migrate --force
    } else {
        Write-ColorOutput "✅ Все миграции выполнены" "Success"
    }
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
}

function Optimize-Application {
    Write-ColorOutput "⚡ Оптимизация приложения..." "Info"
    
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    Write-ColorOutput "✅ Приложение оптимизировано" "Success"
}

function Test-Application {
    if ($SkipTests) {
        Write-ColorOutput "⏭️ Пропуск тестов" "Warning"
        return
    }
    
    Write-ColorOutput "🧪 Запуск тестов..." "Info"
    
    # PHP тесты
    php artisan test
    
    # Frontend тесты (если есть)
    if (Test-Path "package.json") {
        $scripts = Get-Content "package.json" | ConvertFrom-Json
        if ($scripts.scripts.test) {
            npm test
        }
    }
}

function Start-Services {
    Write-ColorOutput "🚀 Запуск сервисов..." "Info"
    
    # Запуск Laravel сервера (если не запущен)
    $laravelProcess = Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like "*artisan serve*" }
    
    if (-not $laravelProcess) {
        Write-ColorOutput "Запуск Laravel сервера..." "Info"
        Start-Process -FilePath "php" -ArgumentList "artisan", "serve" -WindowStyle Minimized
    }
    
    # Запуск Vite (если не запущен)
    $viteProcess = Get-Process -Name "node" -ErrorAction SilentlyContinue | Where-Object { $_.CommandLine -like "*vite*" }
    
    if (-not $viteProcess) {
        Write-ColorOutput "Запуск Vite dev сервера..." "Info"
        Start-Process -FilePath "npm" -ArgumentList "run", "dev" -WindowStyle Minimized
    }
}

function Show-DeploymentSummary {
    Write-ColorOutput "`n🎉 Деплой завершен успешно!" "Success"
    Write-ColorOutput "`n📊 Сводка:" "Info"
    Write-ColorOutput "   • Окружение: $Environment" "Info"
    Write-ColorOutput "   • PowerShell метод: $PowerShellMethod" "Info"
    Write-ColorOutput "   • Тесты: $(if($SkipTests){'Пропущены'}else{'Выполнены'})" "Info"
    
    Write-ColorOutput "`n🌐 Доступные URL:" "Info"
    Write-ColorOutput "   • Laravel: http://127.0.0.1:8000" "Info"
    Write-ColorOutput "   • Vite: http://127.0.0.1:5173" "Info"
    
    Write-ColorOutput "`n📝 Полезные команды:" "Info"
    Write-ColorOutput "   • Просмотр логов: php artisan log:tail" "Info"
    Write-ColorOutput "   • Очистка кеша: php artisan cache:clear" "Info"
    Write-ColorOutput "   • Перезапуск: .\scripts\restart.ps1" "Info"
}

# Основная логика деплоя
Write-ColorOutput "🚀 Начало деплоя SPA Platform" "Info"
Write-ColorOutput "Окружение: $Environment" "Info"
Write-ColorOutput "PowerShell метод: $PowerShellMethod" "Info"

# Проверка PowerShell
if (-not (Test-PowerShellInstallation -Method $PowerShellMethod)) {
    if ($Force) {
        Install-PowerShell -Method $PowerShellMethod
    } else {
        Write-ColorOutput "❌ PowerShell не установлен. Используйте -Force для установки" "Error"
        exit 1
    }
}

# Проверка требований
if (-not (Test-ProjectRequirements)) {
    Write-ColorOutput "❌ Не все требования выполнены" "Error"
    exit 1
}

# Выполнение деплоя
try {
    Backup-Database
    Update-Dependencies
    Run-Migrations
    Clear-Cache
    Optimize-Application
    Test-Application
    Start-Services
    Show-DeploymentSummary
} catch {
    Write-ColorOutput "❌ Ошибка деплоя: $($_.Exception.Message)" "Error"
    exit 1
} 