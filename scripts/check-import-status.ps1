# Check Import Status Script
# Проверяет текущее состояние импортов и показывает прогресс

param(
    [switch]$Detailed = $false
)

$rootPath = "C:\www.spa.com"

Write-Host "🔍 АНАЛИЗ ИМПОРТОВ В ПРОЕКТЕ SPA PLATFORM" -ForegroundColor Cyan
Write-Host "=" * 50

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
    
    $files = Get-ChildItem -Path "$rootPath\resources\js" -Filter "*.vue" -Recurse | 
             ForEach-Object { 
                 $content = Get-Content $_.FullName -Raw
                 if ($content -match $patternRegex) {
                     $_.FullName.Replace("$rootPath\", "")
                 }
             }
    
    $results[$patternName] = @($files)
    
    $count = $files.Count
    $color = if ($patternName -like "Новые*") { "Green" } elseif ($count -eq 0) { "Green" } else { "Yellow" }
    
    Write-Host "$patternName : $count файлов" -ForegroundColor $color
    
    if ($Detailed -and $count -gt 0) {
        $files | ForEach-Object { Write-Host "  - $_" -ForegroundColor Gray }
    }
}

# Подсчет прогресса
$oldImports = ($results["Старые UI Forms"].Count + 
               $results["Старые UI Base"].Count + 
               $results["Старые UI общие"].Count +
               $results["Старые Auth"].Count)

$newImports = ($results["Новые Shared"].Count + 
               $results["Новые Features"].Count + 
               $results["Новые Entities"].Count + 
               $results["Новые Widgets"].Count)

$totalRelevant = $oldImports + $newImports

if ($totalRelevant -gt 0) {
    $progressPercent = [math]::Round(($newImports / $totalRelevant) * 100, 1)
    Write-Host ""
    Write-Host "📊 ПРОГРЕСС МИГРАЦИИ:" -ForegroundColor Cyan
    Write-Host "   Новая структура: $newImports файлов ($progressPercent%)" -ForegroundColor Green
    Write-Host "   Старая структура: $oldImports файлов ($([math]::Round(100-$progressPercent, 1))%)" -ForegroundColor Yellow
    
    $progressBar = "█" * [math]::Floor($progressPercent / 10) + "░" * [math]::Floor((100 - $progressPercent) / 10)
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
Write-Host "💡 Для детализации запустите: .\check-import-status.ps1 -Detailed" -ForegroundColor Gray