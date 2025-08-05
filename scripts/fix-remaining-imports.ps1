# Fix Remaining Import Issues
$rootPath = "C:\www.spa.com"

Write-Host "=== FIXING REMAINING IMPORT ISSUES ===" -ForegroundColor Yellow

# Correct import paths based on actual file locations
$corrections = @{
    "@/src/shared/ui/layout/ErrorBoundary" = "@/src/shared/ui/molecules/ErrorBoundary"
    "@/src/shared/ui/layout/ToastNotifications" = "@/src/shared/ui/molecules/ToastNotifications"
}

# Fix AppLayout.vue specifically
$appLayoutPath = "$rootPath\resources\js\Layouts\AppLayout.vue"

if (Test-Path $appLayoutPath) {
    Write-Host "Fixing AppLayout.vue imports..." -ForegroundColor Cyan
    
    $content = Get-Content $appLayoutPath -Raw -Encoding UTF8
    $originalContent = $content
    
    foreach ($wrongPath in $corrections.Keys) {
        $correctPath = $corrections[$wrongPath]
        if ($content.Contains($wrongPath)) {
            $content = $content.Replace($wrongPath, $correctPath)
            Write-Host "  $wrongPath -> $correctPath" -ForegroundColor Green
        }
    }
    
    if ($content -ne $originalContent) {
        # Create backup
        $backupPath = "$appLayoutPath.backup_fix_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
        Copy-Item $appLayoutPath $backupPath
        Write-Host "  Backup created: $backupPath" -ForegroundColor Gray
        
        # Save corrected content
        Set-Content $appLayoutPath $content -Encoding UTF8
        Write-Host "  AppLayout.vue FIXED!" -ForegroundColor Green
    }
} else {
    Write-Host "AppLayout.vue not found!" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== CHECKING FOR OTHER SIMILAR ISSUES ===" -ForegroundColor Cyan

# Check all files for similar wrong paths
$jsPath = "$rootPath\resources\js"
$filesToCheck = Get-ChildItem -Path $jsPath -Filter "*.vue" -Recurse

foreach ($file in $filesToCheck) {
    $content = Get-Content $file.FullName -Raw -ErrorAction SilentlyContinue
    
    if ($content) {
        $needsFix = $false
        $newContent = $content
        
        foreach ($wrongPath in $corrections.Keys) {
            if ($content.Contains($wrongPath)) {
                $correctPath = $corrections[$wrongPath]
                $newContent = $newContent.Replace($wrongPath, $correctPath)
                $needsFix = $true
                
                $relativePath = $file.FullName.Replace("$rootPath\", "")
                Write-Host "FOUND ISSUE: $relativePath" -ForegroundColor Yellow
                Write-Host "  $wrongPath -> $correctPath" -ForegroundColor Green
            }
        }
        
        if ($needsFix) {
            # Create backup and fix
            $backupPath = "$($file.FullName).backup_fix_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
            Copy-Item $file.FullName $backupPath
            
            Set-Content $file.FullName $newContent -Encoding UTF8
            Write-Host "  FIXED!" -ForegroundColor Green
            Write-Host ""
        }
    }
}

Write-Host "=== COMPLETE ===" -ForegroundColor Green