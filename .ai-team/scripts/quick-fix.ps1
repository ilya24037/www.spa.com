# quick-fix.ps1 - Quick problem search
param([string]$Error)

Write-Host "Searching for solution: $Error" -ForegroundColor Cyan

# Search in lessons
$lessons = Get-ChildItem "virtual-office\knowledge\lessons" -Filter "*.md" -ErrorAction SilentlyContinue
foreach ($lesson in $lessons) {
    $content = Get-Content $lesson.FullName -Raw -ErrorAction SilentlyContinue
    if ($content -match $Error) {
        Write-Host "Found solution in: $($lesson.Name)" -ForegroundColor Green
        Write-Host $content
        break
    }
}

# Search in problems
if (Test-Path "virtual-office\knowledge\problems\INDEX.md") {
    $problems = Get-Content "virtual-office\knowledge\problems\INDEX.md" -Raw -ErrorAction SilentlyContinue
    if ($problems -match $Error) {
        Write-Host "Similar problems found in index" -ForegroundColor Yellow
    }
}
