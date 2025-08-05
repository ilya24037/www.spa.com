# Import Status Check Script - English Version
param([switch]$Detailed = $false)

$rootPath = "C:\www.spa.com"

Write-Host "=== IMPORT ANALYSIS ===" -ForegroundColor Cyan

# Old import patterns
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

# New import patterns
$newPatterns = @(
    "@/src/shared/",
    "@/src/features/",
    "@/src/entities/",
    "@/src/widgets/"
)

$oldCount = 0
$newCount = 0

Write-Host "Checking old imports..." -ForegroundColor Yellow

foreach ($pattern in $oldPatterns) {
    try {
        $files = Get-ChildItem -Path "$rootPath\resources\js" -Filter "*.vue" -Recurse -ErrorAction SilentlyContinue |
                 Where-Object {
                     $content = Get-Content $_.FullName -Raw -ErrorAction SilentlyContinue
                     $content -and $content.Contains($pattern)
                 }
        
        $count = $files.Count
        $oldCount += $count
        
        if ($count -gt 0) {
            Write-Host "  $pattern : $count files" -ForegroundColor Red
            if ($Detailed) {
                $files | ForEach-Object { Write-Host "    - $($_.FullName)" -ForegroundColor Gray }
            }
        }
    } catch {
        Write-Host "  Error checking: $pattern" -ForegroundColor Red
    }
}

Write-Host "Checking new imports..." -ForegroundColor Green

foreach ($pattern in $newPatterns) {
    try {
        $files = Get-ChildItem -Path "$rootPath\resources\js" -Filter "*.vue" -Recurse -ErrorAction SilentlyContinue |
                 Where-Object {
                     $content = Get-Content $_.FullName -Raw -ErrorAction SilentlyContinue
                     $content -and $content.Contains($pattern)
                 }
        
        $count = $files.Count
        $newCount += $count
        
        if ($count -gt 0) {
            Write-Host "  $pattern : $count files" -ForegroundColor Green
            if ($Detailed) {
                $files | ForEach-Object { Write-Host "    - $($_.FullName)" -ForegroundColor Gray }
            }
        }
    } catch {
        Write-Host "  Error checking: $pattern" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "=== RESULTS ===" -ForegroundColor Cyan
Write-Host "Old imports: $oldCount files" -ForegroundColor Yellow
Write-Host "New imports: $newCount files" -ForegroundColor Green

$total = $oldCount + $newCount
if ($total -gt 0) {
    $percent = [math]::Round(($newCount * 100.0) / $total, 1)
    Write-Host "Migration progress: $percent%" -ForegroundColor White
} else {
    Write-Host "No imports found" -ForegroundColor Gray
}

Write-Host ""
if ($oldCount -gt 0) {
    Write-Host "RECOMMENDATION: Update old imports to new structure" -ForegroundColor Yellow
} else {
    Write-Host "ALL IMPORTS UPDATED!" -ForegroundColor Green
}