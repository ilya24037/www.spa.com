# Fix Import Extensions Script
$rootPath = "C:\www.spa.com"

Write-Host "=== FIXING IMPORT EXTENSIONS ===" -ForegroundColor Yellow

# Patterns to fix - imports without .vue extension
$fixes = @{
    '@/src/shared/ui/atoms/TextInput' = '@/src/shared/ui/atoms/TextInput/TextInput.vue'
    '@/src/shared/ui/atoms/Select' = '@/src/shared/ui/atoms/Select/Select.vue'
    '@/src/shared/ui/atoms/Textarea' = '@/src/shared/ui/atoms/Textarea/Textarea.vue'
    '@/src/shared/ui/atoms/Checkbox' = '@/src/shared/ui/atoms/Checkbox/Checkbox.vue'
    '@/src/shared/ui/atoms/Radio' = '@/src/shared/ui/atoms/Radio/Radio.vue'
    '@/src/shared/ui/layout/PageSection' = '@/src/shared/ui/layout/PageSection/PageSection.vue'
    '@/src/shared/ui/layout/AppLayout' = '@/src/shared/ui/layout/AppLayout/AppLayout.vue'
    '@/src/features/media/ui/PhotoUploader' = '@/src/features/media/ui/PhotoUploader/PhotoUploader.vue'
    '@/src/features/media/ui/VideoUploader' = '@/src/features/media/ui/VideoUploader/VideoUploader.vue'
    '@/src/features/geo/ui/GeoSuggest' = '@/src/features/geo/ui/GeoSuggest/GeoSuggest.vue'
    '@/src/shared/ui/atoms/PriceInput' = '@/src/shared/ui/atoms/PriceInput/PriceInput.vue'
    '@/src/shared/ui/atoms/StarRating' = '@/src/shared/ui/atoms/StarRating/StarRating.vue'
    '@/src/features/map/ui/UniversalMap' = '@/src/features/map/ui/UniversalMap/UniversalMap.vue'
    '@/src/features/map/ui/RealMap' = '@/src/features/map/ui/RealMap/RealMap.vue'
    '@/src/features/media/ui/MediaUploader' = '@/src/features/media/ui/MediaUploader/MediaUploader.vue'
}

# Find all Vue files
$vueFiles = Get-ChildItem -Path "$rootPath\resources\js" -Filter "*.vue" -Recurse

$fixedCount = 0

foreach ($file in $vueFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    $fileFixed = $false
    
    foreach ($oldPath in $fixes.Keys) {
        $newPath = $fixes[$oldPath]
        
        # Check for import patterns
        $patterns = @(
            "import\s+.*\s+from\s+[`"']$([regex]::Escape($oldPath))[`"']",
            "from\s+[`"']$([regex]::Escape($oldPath))[`"']"
        )
        
        foreach ($pattern in $patterns) {
            if ($content -match $pattern) {
                $content = $content -replace [regex]::Escape($oldPath), $newPath
                $fileFixed = $true
            }
        }
    }
    
    if ($fileFixed) {
        $relativePath = $file.FullName.Replace("$rootPath\", "")
        Write-Host "Fixed: $relativePath" -ForegroundColor Green
        
        # Create backup
        $backupPath = "$($file.FullName).backup_extensions_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
        Copy-Item $file.FullName $backupPath
        
        # Save fixed content
        Set-Content $file.FullName $content -Encoding UTF8
        $fixedCount++
    }
}

Write-Host ""
Write-Host "=== SUMMARY ===" -ForegroundColor Cyan
Write-Host "Files fixed: $fixedCount" -ForegroundColor Green

if ($fixedCount -eq 0) {
    Write-Host "No import extension issues found!" -ForegroundColor Green
}