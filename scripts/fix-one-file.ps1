# Fix one specific file TypeScript errors
param(
    [string]$FilePath
)

chcp 65001 > $null

if (-not $FilePath) {
    Write-Host "Usage: scripts\fix-one-file.ps1 -FilePath 'path/to/file.vue'" -ForegroundColor Red
    exit 1
}

Write-Host "Fixing TypeScript errors in: $FilePath" -ForegroundColor Cyan

if (-not (Test-Path $FilePath)) {
    Write-Host "File not found: $FilePath" -ForegroundColor Red
    exit 1
}

$content = Get-Content $FilePath -Raw -Encoding UTF8
$originalContent = $content

# Common fixes
$content = $content -replace 'const props = ', 'const _props = '
$content = $content -replace 'const emit = ', 'const _emit = '
$content = $content -replace '\bprops\b(?=\s*=\s*withDefaults)', '_props'
$content = $content -replace 'import { ref, customRef, Ref }', 'import { customRef, type Ref }'
$content = $content -replace 'import { watch, Ref, ref }', 'import { watch, ref, type Ref }'
$content = $content -replace ': any\[\]', ': any[]'

if ($content -ne $originalContent) {
    Set-Content $FilePath -Value $content -Encoding UTF8 -NoNewline
    Write-Host "✅ Fixed: $FilePath" -ForegroundColor Green
    
    # Run quick check
    Write-Host "Running quick TypeScript check..." -ForegroundColor Yellow
    npx vue-tsc --noEmit --skipLibCheck $FilePath
} else {
    Write-Host "No changes needed for: $FilePath" -ForegroundColor Blue
}

Write-Host "Done!" -ForegroundColor Green