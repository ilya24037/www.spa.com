# Простой скрипт исправления TypeScript ошибок
chcp 65001 > $null

Write-Host "🔧 ПРОСТОЕ ИСПРАВЛЕНИЕ TYPESCRIPT ОШИБОК" -ForegroundColor Cyan

$filesFixed = 0

# Функция для замены в файле
function Fix-SimpleFile {
    param($FilePath, $Find, $Replace, $Description)
    
    if (Test-Path $FilePath) {
        $content = Get-Content $FilePath -Raw -Encoding UTF8
        if ($content -like "*$Find*") {
            $content = $content -replace [regex]::Escape($Find), $Replace
            Set-Content $FilePath -Value $content -Encoding UTF8 -NoNewline
            $script:filesFixed++
            Write-Host "✅ $Description в $FilePath" -ForegroundColor Green
        }
    }
}

Write-Host "1. Исправляем ref импорты..." -ForegroundColor Yellow

# Исправляем ref импорты
Fix-SimpleFile "resources/js/src/shared/composables/useDebounce.ts" "import { ref, customRef, Ref }" "import { customRef, Ref }" "Убран дублирующий ref"
Fix-SimpleFile "resources/js/src/shared/composables/useLoadingState.ts" "import { ref, Ref }" "import { ref, type Ref }" "Исправлен импорт Ref"
Fix-SimpleFile "resources/js/src/shared/composables/useLocalStorage.ts" "import { watch, Ref, ref }" "import { watch, ref, type Ref }" "Исправлен порядок импортов"

Write-Host "2. Исправляем route импорты..." -ForegroundColor Yellow

# Исправляем route импорты простым способом
$routeFiles = @(
    "resources/js/src/features/auth/ui/AuthModal/AuthModal.vue",
    "resources/js/src/features/auth/ui/RegisterModal/RegisterModal.vue"
)

foreach ($file in $routeFiles) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw -Encoding UTF8
        if ($content -notlike "*ziggy-js*") {
            $lines = $content -split "`n"
            for ($i = 0; $i -lt $lines.Length; $i++) {
                if ($lines[$i] -like "*@inertiajs/vue3*") {
                    $lines = $lines[0..$i] + "import { route } from 'ziggy-js'" + $lines[($i+1)..($lines.Length-1)]
                    break
                }
            }
            $newContent = $lines -join "`n"
            Set-Content $file -Value $newContent -Encoding UTF8 -NoNewline
            $filesFixed++
            Write-Host "✅ Добавлен route в $file" -ForegroundColor Green
        }
    }
}

Write-Host "3. Исправляем простые ошибки..." -ForegroundColor Yellow

# Простые замены
Fix-SimpleFile "resources/js/src/entities/booking/model/bookingStore.ts" "resolve()" "resolve(undefined)" "Исправлен resolve"
Fix-SimpleFile "resources/js/src/shared/ui/atoms/Input/Input.vue" ":Readonly=" ":readonly=" "Исправлен Readonly"
Fix-SimpleFile "resources/js/src/shared/ui/molecules/Modal/AlertModal.vue" 'variant="info"' 'variant="primary"' "Исправлен variant"
Fix-SimpleFile "resources/js/src/shared/ui/molecules/Modal/ConfirmModal.vue" 'variant="info"' 'variant="primary"' "Исправлен variant"

Write-Host "4. Исправляем error типы..." -ForegroundColor Yellow

$errorFiles = @(
    "resources/js/src/entities/service/model/serviceStore.ts",
    "resources/js/src/entities/user/model/userStore.ts"
)

foreach ($file in $errorFiles) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw -Encoding UTF8
        if ($content -like "*err.message*") {
            $content = $content -replace "err\.message", "(err as Error).message"
            Set-Content $file -Value $content -Encoding UTF8 -NoNewline
            $filesFixed++
            Write-Host "✅ Исправлен error тип в $file" -ForegroundColor Green
        }
    }
}

Write-Host "`n📊 Исправлено файлов: $filesFixed" -ForegroundColor Cyan

if ($filesFixed -gt 0) {
    Write-Host "`n🚀 Проверяем результат..." -ForegroundColor Yellow
    npm run build
} else {
    Write-Host "ℹ️ Нет файлов для исправления" -ForegroundColor Blue
}

Write-Host "`n🎉 Готово!" -ForegroundColor Green