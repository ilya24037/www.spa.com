@echo off
chcp 65001 >nul
echo Fixing _props to props and _emit to emit...

echo Step 1: Fix _props to props in all Vue files
powershell -Command "Get-ChildItem -Path 'resources/js' -Recurse -Include '*.vue' | ForEach-Object { $content = Get-Content $_.FullName -Raw -Encoding UTF8; if ($content -match 'const _props = ') { $content = $content -replace 'const _props = ', 'const props = '; Set-Content $_.FullName -Value $content -Encoding UTF8; Write-Host \"Fixed props in: $($_.Name)\" } }"

echo Step 2: Fix _emit to emit in all Vue files
powershell -Command "Get-ChildItem -Path 'resources/js' -Recurse -Include '*.vue' | ForEach-Object { $content = Get-Content $_.FullName -Raw -Encoding UTF8; if ($content -match 'const _emit = ') { $content = $content -replace 'const _emit = ', 'const emit = '; Set-Content $_.FullName -Value $content -Encoding UTF8; Write-Host \"Fixed emit in: $($_.Name)\" } }"

echo Step 3: Remove unused reactive and readonly imports 
powershell -Command "Get-ChildItem -Path 'resources/js' -Recurse -Include '*.ts','*.vue' | ForEach-Object { $content = Get-Content $_.FullName -Raw -Encoding UTF8; if ($content -match 'import \{ ref, computed, reactive, readonly, type Ref \}') { $content = $content -replace 'import \{ ref, computed, reactive, readonly, type Ref \}', 'import { ref, computed, type Ref }'; Set-Content $_.FullName -Value $content -Encoding UTF8; Write-Host \"Cleaned imports in: $($_.Name)\" } }"

echo Done! Run npm run build to check results.
pause