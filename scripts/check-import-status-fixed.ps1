# Check Import Status Script
# Проверяет текущее состояние импортов и показывает прогресс

param(
    [switch]$Detailed = $false
)

# Установка кодировки для корректного отображения русского текста
chcp 65001 > $null

$rootPath = "C:\www.spa.com"

Write-Host "🔍 АНАЛИЗ ИМПОРТОВ В ПРОЕКТЕ SPA PLATFORM" -ForegroundColor Cyan
Write-Host ("=" * 50)

# Паттерны для поиска
$patterns = @{
    "Старые UI Forms" = "@/Components/UI/Forms/"
    "Старые UI Base" = "@/Components/UI/Base"
    "Старые UI общие" = "@/Components/UI/CheckboxGroup|@/Components/UI/Modal"
    "Старые Auth" = "@/Components/Auth/"
    "Старые Layout" = "@/Components/Layout/"
    "Старые Form" = "@/Components/Form/"
    "Старые Common" = "@/Components/Common/"
    "Старые Map" = "@/Components/Map/"
    "Старые Media" = "@/Components/Media"
    "Новые Shared" = "@/src/shared/"
    "Новые Features" = "@/src/features/"
    "Новые Entities" = "@/src/entities/"
    "Новые Widgets" = "@/src/widgets/"
}

$results = @{}

foreach ($pattern in $patterns.GetEnumerator()) {
    $patternName = $pattern.Key
    $patternRegex = $pattern.Value
    
    try {
        $files = Get-ChildItem -Path "$rootPath\resources\js" -Filter "*.vue" -Recurse -ErrorAction SilentlyContinue | 
                 ForEach-Object { 
                     try {
                         $content = Get-Content $_.FullName -Raw -Encoding UTF8 -ErrorAction SilentlyContinue
                         if ($content -and ($content -match $patternRegex)) {
                             $_.FullName.Replace("$rootPath\", "")
                         }
                     } catch {
                         # Игнорируем ошибки чтения отдельных файлов
                     }
                 }
        
        $results[$patternName] = @($files | Where-Object { $_ })
    } catch {
        $results[$patternName] = @()
    }
    
    $count = $results[$patternName].Count
    $color = if ($patternName -like "Новые*") { "Green" } elseif ($count -eq 0) { "Green" } else { "Yellow" }
    
    Write-Host "$patternName : $count файлов" -ForegroundColor $color
    
    if ($Detailed -and $count -gt 0) {
        $results[$patternName] | ForEach-Object { Write-Host "  - $_" -ForegroundColor Gray }
    }
}

# Подсчет прогресса
$oldImports = 0
$oldImports += $results["Старые UI Forms"].Count
$oldImports += $results["Старые UI Base"].Count  
$oldImports += $results["Старые UI общие"].Count
$oldImports += $results["Старые Auth"].Count

$newImports = 0
$newImports += $results["Новые Shared"].Count
$newImports += $results["Новые Features"].Count
$newImports += $results["Новые Entities"].Count
$newImports += $results["Новые Widgets"].Count

$totalRelevant = $oldImports + $newImports

if ($totalRelevant -gt 0) {
    $progressPercent = [math]::Round(($newImports / $totalRelevant) * 100, 1)
    $remainingPercent = [math]::Round(100 - $progressPercent, 1)
    
    Write-Host ""
    Write-Host "📊 ПРОГРЕСС МИГРАЦИИ:" -ForegroundColor Cyan
    Write-Host "   Новая структура: $newImports файлов ($progressPercent%)" -ForegroundColor Green
    Write-Host "   Старая структура: $oldImports файлов ($remainingPercent%)" -ForegroundColor Yellow
    
    $progressBarLength = 10
    $filledBars = [math]::Floor($progressPercent / 10)
    $emptyBars = $progressBarLength - $filledBars
    $progressBar = ("█" * $filledBars) + ("░" * $emptyBars)
    Write-Host "   [$progressBar] $progressPercent%" -ForegroundColor White
}

Write-Host ""
Write-Host "🎯 РЕКОМЕНДАЦИИ:" -ForegroundColor Cyan

if ($results["Старые UI Forms"].Count -gt 0) {
    Write-Host "   ⚠️  Запустите mass-import-update.ps1 для автоматической замены UI импортов" -ForegroundColor Yellow
}

if ($results["Старые Auth"].Count -gt 0) {
    Write-Host "   ⚠️  Обновите Auth импорты на @/src/features/auth" -ForegroundColor Yellow  
}

if ($oldImports -eq 0) {
    Write-Host "   ✅ Все основные импорты обновлены!" -ForegroundColor Green
}

Write-Host ""
Write-Host "💡 Для детализации запустите: .\check-import-status-fixed.ps1 -Detailed" -ForegroundColor Gray