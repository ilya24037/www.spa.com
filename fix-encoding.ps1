# PowerShell script to fix UTF-8 encoding issues in Vue files

$rootPath = "C:\www.spa.com\resources\js"
$encoding = [System.Text.UTF8Encoding]::new($false) # UTF-8 without BOM

Write-Host "Fixing UTF-8 encoding in Vue files..." -ForegroundColor Green

# Find all Vue files
$files = Get-ChildItem -Path $rootPath -Filter "*.vue" -Recurse
$files += Get-ChildItem -Path $rootPath -Filter "*.ts" -Recurse
$files += Get-ChildItem -Path $rootPath -Filter "*.js" -Recurse

$fixedCount = 0

foreach ($file in $files) {
    # Read file content
    $content = Get-Content -Path $file.FullName -Raw -Encoding UTF8
    
    # Check if file has BOM or encoding issues
    $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
    $hasBOM = $false
    
    if ($bytes.Length -ge 3) {
        if ($bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
            $hasBOM = $true
        }
    }
    
    # Check for corrupted Cyrillic text patterns
    $hasCorruptedText = $false
    if ($content -match 'Р[а-яА-Я]') {
        $hasCorruptedText = $true
    }
    
    if ($hasBOM -or $hasCorruptedText) {
        Write-Host "Fixing: $($file.Name)" -ForegroundColor Yellow
        
        # Remove BOM if present
        if ($hasBOM) {
            $content = [System.Text.Encoding]::UTF8.GetString($bytes, 3, $bytes.Length - 3)
        }
        
        # Write back with proper UTF-8 encoding (no BOM)
        [System.IO.File]::WriteAllText($file.FullName, $content, $encoding)
        $fixedCount++
    }
}

Write-Host "`nFixed $fixedCount files with encoding issues." -ForegroundColor Green
Write-Host "All files are now UTF-8 without BOM." -ForegroundColor Cyan