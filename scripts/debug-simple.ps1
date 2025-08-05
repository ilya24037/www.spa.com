# Simple Debug Script
Write-Host "=== CHECKING FILES ===" -ForegroundColor Cyan

$files = @(
    "resources\js\src\widgets\masters-catalog\MastersCatalog.vue",
    "resources\js\src\features\masters-filter\ui\FilterPanel\FilterPanel.vue", 
    "resources\js\src\features\masters-filter\model\filter.store.ts"
)

Write-Host "Checking key files..." -ForegroundColor Yellow

foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "OK: $file" -ForegroundColor Green
    } else {
        Write-Host "MISSING: $file" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "=== DONE ===" -ForegroundColor Green