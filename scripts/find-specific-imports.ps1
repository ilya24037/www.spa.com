# Find Specific Imports Script
$rootPath = "C:\www.spa.com"

Write-Host "=== SEARCHING FOR SPECIFIC IMPORTS ===" -ForegroundColor Cyan

# Search for actual import patterns in files
$searchPatterns = @(
    "@/Components/UI/Base",
    "@/Components/Layout/",
    "@/Components/Form/",
    "@/Components/Common/",
    "@/Components/Map/",
    "@/Components/Media"
)

foreach ($pattern in $searchPatterns) {
    Write-Host "Searching for: $pattern" -ForegroundColor Yellow
    
    $files = Get-ChildItem -Path "$rootPath\resources\js" -Filter "*.vue" -Recurse |
             ForEach-Object {
                 $content = Get-Content $_.FullName -Raw -ErrorAction SilentlyContinue
                 if ($content -and $content.Contains($pattern)) {
                     Write-Host "  FOUND IN: $($_.FullName.Replace($rootPath + '\', ''))" -ForegroundColor Green
                     
                     # Extract actual import lines
                     $lines = $content -split "`n"
                     for ($i = 0; $i -lt $lines.Length; $i++) {
                         if ($lines[$i].Contains($pattern)) {
                             Write-Host "    Line $($i+1): $($lines[$i].Trim())" -ForegroundColor Cyan
                         }
                     }
                     Write-Host ""
                 }
             }
}

Write-Host "=== COMPLETE ===" -ForegroundColor Green