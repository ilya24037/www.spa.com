@echo off
chcp 65001 >nul

if "%~1"=="" (
    echo Usage: scripts\simple-fix-one.bat filepath
    echo Example: scripts\simple-fix-one.bat "resources/js/Pages/Dashboard.vue"
    pause
    exit /b 1
)

set FILE=%~1

echo Fixing file: %FILE%

if not exist "%FILE%" (
    echo File not found: %FILE%
    pause
    exit /b 1
)

echo Before fix:
powershell -Command "Get-Content '%FILE%' | Select-String -Pattern 'const props|const emit|: any\[\]' | ForEach-Object { Write-Host $_.LineNumber': '$_.Line }"

echo.
echo Applying fixes...

REM Fix unused props/emit variables
powershell -Command "$content = Get-Content '%FILE%' -Raw -Encoding UTF8; $content = $content -replace 'const props = (withDefaults\(defineProps)', 'const _props = $1'; $content = $content -replace 'const emit = defineEmits', 'const _emit = defineEmits'; Set-Content '%FILE%' -Value $content -Encoding UTF8 -NoNewline"

echo.
echo After fix:
powershell -Command "Get-Content '%FILE%' | Select-String -Pattern 'const _props|const _emit' | ForEach-Object { Write-Host $_.LineNumber': '$_.Line }"

echo.
echo File fixed! Now run: npm run build
pause