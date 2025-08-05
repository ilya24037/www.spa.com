# Emergency Rollback Script
$rootPath = "C:\www.spa.com"

Write-Host "=== EMERGENCY ROLLBACK ===" -ForegroundColor Red

# Find all backup files created today
$backupFiles = Get-ChildItem -Path "$rootPath\resources\js" -Filter "*.backup_*" -Recurse

Write-Host "Found $($backupFiles.Count) backup files" -ForegroundColor Cyan

foreach ($backup in $backupFiles) {
    $originalFile = $backup.FullName -replace '\.backup_\d{8}_\d{6}$', ''
    
    if (Test-Path $originalFile) {
        $relativePath = $originalFile.Replace("$rootPath\", "")
        Write-Host "Restoring: $relativePath" -ForegroundColor Yellow
        
        Copy-Item $backup.FullName $originalFile -Force
        Write-Host "  RESTORED!" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "=== ROLLBACK COMPLETE ===" -ForegroundColor Green
Write-Host "Check if site works now" -ForegroundColor Cyan