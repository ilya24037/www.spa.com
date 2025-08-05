# delete-legacy.ps1 - Удаление legacy компонентов

$files = @(
    'resources\js\Components\Cards\Card.vue',
    'resources\js\Components\Features\MasterShow\components\BookingWidget.vue',
    'resources\js\Components\Features\MasterShow\components\MasterInfo.vue',
    'resources\js\Components\Features\MasterShow\index.vue',
    'resources\js\Components\Features\PhotoUploader\index.vue',
    'resources\js\Components\Features\Services\index.vue',
    'resources\js\Components\Masters\MasterDescription\index.vue',
    'resources\js\Components\Masters\MasterDetails\index.vue',
    'resources\js\Components\Masters\MasterHeader\index.vue',
    'resources\js\Components\Media\MediaGallery\index.vue',
    'resources\js\Components\Media\MediaUploader\index.vue',
    'resources\js\Components\Modals\BookingModal.vue',
    'resources\js\Components\UI\Forms\InputError.vue',
    'resources\js\Components\UI\Forms\InputLabel.vue',
    'resources\js\Components\UI\Forms\PrimaryButton.vue',
    'resources\js\Components\UI\Forms\SecondaryButton.vue',
    'resources\js\Components\UI\Forms\TextInput.vue'
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   УДАЛЕНИЕ LEGACY КОМПОНЕНТОВ" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$deleted = 0
$failed = 0
$notFound = 0

foreach ($file in $files) {
    if (Test-Path $file) {
        try {
            Remove-Item -Path $file -Force -ErrorAction Stop
            if (-not (Test-Path $file)) {
                $deleted++
                Write-Host "[✓] Deleted: $file" -ForegroundColor Green
            } else {
                $failed++
                Write-Host "[✗] Failed: $file" -ForegroundColor Red
            }
        } catch {
            $failed++
            Write-Host "[✗] Error deleting: $file - $_" -ForegroundColor Red
        }
    } else {
        $notFound++
        Write-Host "[•] Not found: $file" -ForegroundColor Gray
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "РЕЗУЛЬТАТЫ:" -ForegroundColor Yellow
Write-Host "  Удалено: $deleted" -ForegroundColor Green
Write-Host "  Ошибок: $failed" -ForegroundColor Red
Write-Host "  Не найдено: $notFound" -ForegroundColor Gray
Write-Host "========================================" -ForegroundColor Cyan

# Удаление пустых директорий
Write-Host ""
Write-Host "Удаление пустых директорий..." -ForegroundColor Yellow
$emptyDirs = Get-ChildItem -Path "resources\js\Components" -Recurse -Directory | 
    Where-Object { (Get-ChildItem $_.FullName -Recurse | Measure-Object).Count -eq 0 } |
    Sort-Object -Property FullName -Descending

foreach ($dir in $emptyDirs) {
    try {
        Remove-Item -Path $dir.FullName -Force
        Write-Host "[✓] Removed empty dir: $($dir.FullName)" -ForegroundColor Green
    } catch {
        Write-Host "[✗] Failed to remove: $($dir.FullName)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "✅ Завершено!" -ForegroundColor Green