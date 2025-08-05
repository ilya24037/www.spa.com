# Mass Import Update Script - English Version
# Updates old import paths to new architecture

param(
    [switch]$Preview = $false,
    [switch]$Backup = $true,
    [string]$TargetFile = ""
)

$rootPath = "C:\www.spa.com"
$jsPath = "$rootPath\resources\js"

Write-Host "=== MASS IMPORT UPDATE ===" -ForegroundColor Cyan

# Import replacement rules based on REAL imports found in codebase
$replacementRules = @{
    # UI Base Components (EXACT paths from grep results)
    "@/Components/UI/BaseInput.vue" = "@/src/shared/ui/atoms/TextInput"
    "@/Components/UI/BaseSelect.vue" = "@/src/shared/ui/atoms/Select"
    "@/Components/UI/BaseTextarea.vue" = "@/src/shared/ui/atoms/Textarea"
    "@/Components/UI/BaseCheckbox.vue" = "@/src/shared/ui/atoms/Checkbox"
    "@/Components/UI/BaseRadio.vue" = "@/src/shared/ui/atoms/Radio"
    
    # Layout Components
    "@/Components/Layout/PageSection.vue" = "@/src/shared/ui/layout/PageSection"
    
    # Form Components
    "@/Components/Form/Upload/PhotoUploader.vue" = "@/src/features/media/ui/PhotoUploader"
    "@/Components/Form/Upload/VideoUploader.vue" = "@/src/features/media/ui/VideoUploader"
    "@/Components/Form/Geo/GeoSuggest.vue" = "@/src/features/geo/ui/GeoSuggest"
    "@/Components/Form/Controls/PriceInput.vue" = "@/src/shared/ui/atoms/PriceInput"
    
    # Common Components
    "@/Components/Common/StarRating.vue" = "@/src/shared/ui/atoms/StarRating"
    "@/Components/Common/ErrorBoundary.vue" = "@/src/shared/ui/layout/ErrorBoundary"
    "@/Components/Common/ToastNotifications.vue" = "@/src/shared/ui/layout/ToastNotifications"
    
    # Map Components
    "@/Components/Map/UniversalMap.vue" = "@/src/features/map/ui/UniversalMap"
    "@/Components/Map/RealMap.vue" = "@/src/features/map/ui/RealMap"
    
    # Media Components
    "@/Components/MediaUpload/MediaUploader.vue" = "@/src/features/media/ui/MediaUploader"
    
    # Layout main (from Auth pages)
    "@/Layouts/AppLayout.vue" = "@/src/shared/ui/layout/AppLayout"
}

function Create-Backup {
    param([string]$filePath)
    
    if ($Backup) {
        $backupPath = "$filePath.backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
        Copy-Item $filePath $backupPath
        Write-Host "  Backup created: $backupPath" -ForegroundColor Gray
    }
}

function Update-FileImports {
    param(
        [string]$filePath,
        [hashtable]$rules,
        [bool]$previewMode
    )
    
    $relativePath = $filePath.Replace("$rootPath\", "")
    $content = Get-Content $filePath -Raw -Encoding UTF8
    $originalContent = $content
    $changesMade = $false
    $changesLog = @()
    
    foreach ($oldImport in $rules.Keys) {
        $newImport = $rules[$oldImport]
        
        # Check different import patterns
        $patterns = @(
            "import\s+.*\s+from\s+[`"']$([regex]::Escape($oldImport))[`"']",
            "import\s*\(\s*[`"']$([regex]::Escape($oldImport))[`"']\s*\)",
            "from\s+[`"']$([regex]::Escape($oldImport))[`"']"
        )
        
        foreach ($pattern in $patterns) {
            if ($content -match $pattern) {
                $content = $content -replace [regex]::Escape($oldImport), $newImport
                $changesMade = $true
                $changesLog += "  $oldImport -> $newImport"
            }
        }
    }
    
    if ($changesMade) {
        Write-Host "FILE: $relativePath" -ForegroundColor Yellow
        $changesLog | ForEach-Object { Write-Host $_ -ForegroundColor Green }
        
        if (-not $previewMode) {
            Create-Backup $filePath
            Set-Content $filePath $content -Encoding UTF8
            Write-Host "  UPDATED!" -ForegroundColor Green
        } else {
            Write-Host "  (PREVIEW MODE - no changes made)" -ForegroundColor Cyan
        }
        Write-Host ""
        return $true
    }
    
    return $false
}

# Main execution
$filesToProcess = @()

if ($TargetFile) {
    if (Test-Path "$jsPath\$TargetFile") {
        $filesToProcess += "$jsPath\$TargetFile"
    } else {
        Write-Host "ERROR: File not found: $TargetFile" -ForegroundColor Red
        exit 1
    }
} else {
    $filesToProcess = Get-ChildItem -Path $jsPath -Filter "*.vue" -Recurse | 
                     Where-Object {
                         $content = Get-Content $_.FullName -Raw -ErrorAction SilentlyContinue
                         $content -and ($replacementRules.Keys | Where-Object { $content.Contains($_) }).Count -gt 0
                     } | 
                     Select-Object -ExpandProperty FullName
}

$totalFiles = $filesToProcess.Count
$updatedFiles = 0

if ($totalFiles -eq 0) {
    Write-Host "No files found with old imports!" -ForegroundColor Green
    exit 0
}

Write-Host "Found $totalFiles files with old imports" -ForegroundColor Cyan

if ($Preview) {
    Write-Host "PREVIEW MODE - No files will be modified" -ForegroundColor Yellow
}

Write-Host "Processing files..." -ForegroundColor Cyan
Write-Host ""

foreach ($file in $filesToProcess) {
    $updated = Update-FileImports $file $replacementRules $Preview
    if ($updated) {
        $updatedFiles++
    }
}

Write-Host "=== SUMMARY ===" -ForegroundColor Cyan
Write-Host "Total files processed: $totalFiles" -ForegroundColor White
Write-Host "Files updated: $updatedFiles" -ForegroundColor Green

if ($Preview) {
    Write-Host ""
    Write-Host "To apply changes, run without -Preview flag:" -ForegroundColor Yellow
    Write-Host "powershell -ExecutionPolicy Bypass -File `".\mass-import-update.ps1`"" -ForegroundColor Gray
}