# PowerShell скрипт для финальной проверки после рефакторинга
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "ФИНАЛЬНАЯ ПРОВЕРКА ПОСЛЕ РЕФАКТОРИНГА" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Проверка структуры папок
Write-Host "1. Проверка структуры папок..." -ForegroundColor Yellow
$oldFolders = @(
    "C:\www.spa.com\app\Models",
    "C:\www.spa.com\app\Services", 
    "C:\www.spa.com\app\Http"
)

$allDeleted = $true
foreach ($folder in $oldFolders) {
    if (Test-Path $folder) {
        Write-Host "  ❌ Папка всё ещё существует: $folder" -ForegroundColor Red
        $allDeleted = $false
    } else {
        Write-Host "  ✅ Удалена: $folder" -ForegroundColor Green
    }
}

if ($allDeleted) {
    Write-Host "  ✅ Все старые папки удалены!" -ForegroundColor Green
} else {
    Write-Host "  ❌ Некоторые старые папки не удалены!" -ForegroundColor Red
}

Write-Host ""

# 2. Проверка импортов
Write-Host "2. Проверка старых импортов..." -ForegroundColor Yellow
$oldImports = @(
    "use App\\Models\\",
    "use App\\Services\\",
    "App\\Services\\",
    "App\\Models\\"
)

$hasOldImports = $false
foreach ($pattern in $oldImports) {
    Write-Host "  Поиск: $pattern" -ForegroundColor Cyan
    $results = Get-ChildItem -Path "C:\www.spa.com\app" -Filter "*.php" -Recurse | 
               Select-String -Pattern $pattern -List
    
    if ($results.Count -gt 0) {
        Write-Host "  ❌ Найдено $($results.Count) файлов с устаревшими импортами" -ForegroundColor Red
        $hasOldImports = $true
        $results | Select-Object -First 3 | ForEach-Object {
            Write-Host "     - $($_.Path)" -ForegroundColor Yellow
        }
    } else {
        Write-Host "  ✅ Не найдено" -ForegroundColor Green
    }
}

Write-Host ""

# 3. Проверка Domain структуры
Write-Host "3. Проверка Domain структуры..." -ForegroundColor Yellow
$domains = @(
    "User", "Master", "Ad", "Booking", "Payment", 
    "Media", "Review", "Service", "Search", "Notification"
)

foreach ($domain in $domains) {
    $domainPath = "C:\www.spa.com\app\Domain\$domain"
    if (Test-Path $domainPath) {
        $models = Get-ChildItem -Path "$domainPath\Models" -Filter "*.php" -ErrorAction SilentlyContinue | Measure-Object
        $services = Get-ChildItem -Path "$domainPath\Services" -Filter "*.php" -ErrorAction SilentlyContinue | Measure-Object
        Write-Host "  ✅ $domain - Models: $($models.Count), Services: $($services.Count)" -ForegroundColor Green
    } else {
        Write-Host "  ❌ Домен не найден: $domain" -ForegroundColor Red
    }
}

Write-Host ""

# 4. Проверка работоспособности
Write-Host "4. Проверка работоспособности приложения..." -ForegroundColor Yellow
Write-Host "  Выполните в терминале:" -ForegroundColor Cyan
Write-Host "    composer dump-autoload" -ForegroundColor White
Write-Host "    php artisan optimize:clear" -ForegroundColor White
Write-Host "    php artisan route:list" -ForegroundColor White
Write-Host "    php artisan test:create-ad" -ForegroundColor White

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "ИТОГОВАЯ СТАТИСТИКА" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Подсчет файлов
$domainFiles = (Get-ChildItem -Path "C:\www.spa.com\app\Domain" -Filter "*.php" -Recurse | Measure-Object).Count
$appFiles = (Get-ChildItem -Path "C:\www.spa.com\app\Application" -Filter "*.php" -Recurse | Measure-Object).Count
$infraFiles = (Get-ChildItem -Path "C:\www.spa.com\app\Infrastructure" -Filter "*.php" -Recurse | Measure-Object).Count

Write-Host "Domain слой: $domainFiles файлов" -ForegroundColor Green
Write-Host "Application слой: $appFiles файлов" -ForegroundColor Green
Write-Host "Infrastructure слой: $infraFiles файлов" -ForegroundColor Green
Write-Host ""

if (-not $hasOldImports -and $allDeleted) {
    Write-Host "✅ РЕФАКТОРИНГ ЗАВЕРШЁН УСПЕШНО!" -ForegroundColor Green
} else {
    Write-Host "⚠️  ТРЕБУЕТСЯ ДОРАБОТКА!" -ForegroundColor Yellow
}