# Простая проверка импортов
param([switch]$Detailed = $false)

chcp 65001 > $null

$rootPath = "C:\www.spa.com"

Write-Host "=== АНАЛИЗ ИМПОРТОВ ===" -ForegroundColor Cyan

# Простые паттерны поиска
$oldPatterns = @(
    "@/Components/UI/Forms/",
    "@/Components/UI/Base",
    "@/Components/Auth/",
    "@/Components/Layout/",
    "@/Components/Form/",
    "@/Components/Common/",
    "@/Components/Map/",
    "@/Components/Media"
)

$newPatterns = @(
    "@/src/shared/",
    "@/src/features/",
    "@/src/entities/",
    "@/src/widgets/"
)

$oldCount = 0
$newCount = 0

Write-Host "Проверяем старые импорты..." -ForegroundColor Yellow

foreach ($pattern in $oldPatterns) {
    try {
        $files = Get-ChildItem -Path "$rootPath\resources\js" -Filter "*.vue" -Recurse -ErrorAction SilentlyContinue |
                 ForEach-Object {
                     $content = Get-Content $_.FullName -Raw -ErrorAction SilentlyContinue
                     if ($content -and $content.Contains($pattern)) {
                         $_.FullName
                     }
                 }
        
        $count = ($files | Measure-Object).Count
        $oldCount += $count
        
        if ($count -gt 0) {
            Write-Host "  $pattern : $count файлов" -ForegroundColor Red
            if ($Detailed) {
                $files | ForEach-Object { Write-Host "    - $_" -ForegroundColor Gray }
            }
        }
    } catch {
        Write-Host "  Ошибка проверки: $pattern" -ForegroundColor Red
    }
}

Write-Host "Проверяем новые импорты..." -ForegroundColor Green

foreach ($pattern in $newPatterns) {
    try {
        $files = Get-ChildItem -Path "$rootPath\resources\js" -Filter "*.vue" -Recurse -ErrorAction SilentlyContinue |
                 ForEach-Object {
                     $content = Get-Content $_.FullName -Raw -ErrorAction SilentlyContinue
                     if ($content -and $content.Contains($pattern)) {
                         $_.FullName
                     }
                 }
        
        $count = ($files | Measure-Object).Count
        $newCount += $count
        
        if ($count -gt 0) {
            Write-Host "  $pattern : $count файлов" -ForegroundColor Green
            if ($Detailed) {
                $files | ForEach-Object { Write-Host "    - $_" -ForegroundColor Gray }
            }
        }
    } catch {
        Write-Host "  Ошибка проверки: $pattern" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "=== РЕЗУЛЬТАТ ===" -ForegroundColor Cyan
Write-Host "Старые импорты: $oldCount файлов" -ForegroundColor Yellow
Write-Host "Новые импорты: $newCount файлов" -ForegroundColor Green

$total = $oldCount + $newCount
if ($total -gt 0) {
    $percent = [math]::Round(($newCount * 100) / $total, 1)
    Write-Host "Прогресс миграции: $percent%" -ForegroundColor White
} else {
    Write-Host "Импорты не найдены" -ForegroundColor Gray
}

Write-Host ""
if ($oldCount -gt 0) {
    Write-Host "РЕКОМЕНДАЦИЯ: Обновите старые импорты на новую структуру" -ForegroundColor Yellow
} else {
    Write-Host "ВСЕ ИМПОРТЫ ОБНОВЛЕНЫ!" -ForegroundColor Green
}