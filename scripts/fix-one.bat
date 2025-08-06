@echo off
chcp 65001 >nul

if "%~1"=="" (
    echo Usage: scripts\fix-one.bat filepath
    echo Example: scripts\fix-one.bat "resources/js/Pages/Dashboard.vue"
    pause
    exit /b 1
)

set FILE=%~1

echo Fixing file: %FILE%

REM Basic fixes
powershell -Command "if (Test-Path '%FILE%') { $content = Get-Content '%FILE%' -Raw -Encoding UTF8; $content = $content -replace 'const props = ', 'const _props = '; $content = $content -replace 'const emit = ', 'const _emit = '; Set-Content '%FILE%' -Value $content -Encoding UTF8 -NoNewline; Write-Host 'Fixed: %FILE%' -ForegroundColor Green } else { Write-Host 'File not found: %FILE%' -ForegroundColor Red }"

echo.
echo Testing single file...
npx vue-tsc --noEmit --skipLibCheck "%FILE%"

echo.
echo Done!
pause