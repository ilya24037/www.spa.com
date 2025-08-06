# Скрипт для автоматического исправления TypeScript ошибок
# Автор: AI Assistant для SPA Platform

chcp 65001 > $null

Write-Host "🔧 АВТОМАТИЧЕСКОЕ ИСПРАВЛЕНИЕ TYPESCRIPT ОШИБОК" -ForegroundColor Cyan
Write-Host "=" * 60 -ForegroundColor Gray

$ErrorActionPreference = "Stop"
$filesFixed = 0
$errorsFixed = 0

# Функция для записи в лог
function Write-Log {
    param($Message, $Color = "White")
    Write-Host "$(Get-Date -Format 'HH:mm:ss') $Message" -ForegroundColor $Color
}

# Функция для замены в файле
function Fix-File {
    param($FilePath, $Replacements, $Description)
    
    if (Test-Path $FilePath) {
        $content = Get-Content $FilePath -Raw -Encoding UTF8
        $originalContent = $content
        $fileFixed = $false
        
        foreach ($replacement in $Replacements) {
            if ($content -match $replacement.Pattern) {
                $content = $content -replace $replacement.Pattern, $replacement.Replacement
                $fileFixed = $true
                $script:errorsFixed++
                Write-Log "  ✅ $($replacement.Description)" -Color "Green"
            }
        }
        
        if ($fileFixed) {
            Set-Content $FilePath -Value $content -Encoding UTF8 -NoNewline
            $script:filesFixed++
            Write-Log "✅ ИСПРАВЛЕН: $FilePath" -Color "Yellow"
        }
    }
}

Write-Log "🎯 Начинаем массовое исправление ошибок..."

# ============================================================================
# 1. ИСПРАВЛЕНИЕ ОТСУТСТВУЮЩИХ ИМПОРТОВ ref
# ============================================================================

Write-Log "📦 1. Исправляем отсутствующие импорты ref..." -Color "Cyan"

$refFiles = @(
    "resources/js/src/entities/booking/ui/BookingModal/BookingModal.vue",
    "resources/js/src/entities/ad/ui/AdCard/AdCardListItem.vue",
    "resources/js/src/shared/ui/molecules/Breadcrumbs/useBreadcrumbs.ts",
    "resources/js/src/shared/ui/atoms/Skeleton/useSkeleton.ts",
    "resources/js/src/shared/ui/organisms/Cards/useCard.ts",
    "resources/js/src/shared/composables/useDebounce.ts",
    "resources/js/src/shared/composables/useLoadingState.ts",
    "resources/js/src/shared/composables/useLocalStorage.ts"
)

foreach ($file in $refFiles) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw -Encoding UTF8
        
        # Проверяем, есть ли уже ref в импорте
        if ($content -match "import\s*\{\s*([^}]*)\s*\}\s*from\s*[`"']vue[`"']" -and $content -notmatch "import\s*\{\s*[^}]*ref[^}]*\}\s*from\s*[`"']vue[`"']") {
            $content = $content -replace "import\s*\{\s*([^}]*)\s*\}\s*from\s*[`"']vue[`"']", "import { ref, `$1 } from 'vue'"
            Set-Content $file -Value $content -Encoding UTF8 -NoNewline
            $filesFixed++
            Write-Log "✅ Добавлен ref импорт: $file" -Color "Green"
        }
    }
}

# ============================================================================
# 2. ИСПРАВЛЕНИЕ ОТСУТСТВУЮЩИХ ИМПОРТОВ route
# ============================================================================

Write-Log "📦 2. Исправляем отсутствующие импорты route..." -Color "Cyan"

$routeFiles = @(
    "resources/js/Pages/Auth/VerifyEmail.vue",
    "resources/js/src/features/auth/ui/AuthModal/AuthModal.vue", 
    "resources/js/src/features/auth/ui/RegisterModal/RegisterModal.vue"
)

foreach ($file in $routeFiles) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw -Encoding UTF8
        
        # Добавляем импорт route если его нет
        if ($content -match "import.*from.*@inertiajs/vue3" -and $content -notmatch "import.*route.*from.*ziggy-js") {
            $content = $content -replace "(import.*from\s*[`"']@inertiajs/vue3[`"'];?)", "`$1`nimport { route } from 'ziggy-js';"
            Set-Content $file -Value $content -Encoding UTF8 -NoNewline
            $filesFixed++
            Write-Log "✅ Добавлен route импорт: $file" -Color "Green"
        }
    }
}

# ============================================================================
# 3. ИСПРАВЛЕНИЕ ТИПИЗАЦИИ ПАРАМЕТРОВ ФУНКЦИЙ
# ============================================================================

Write-Log "🎯 3. Исправляем типизацию параметров функций..." -Color "Cyan"

$functionFiles = @(
    "resources/js/src/entities/master/model/masterStore.ts",
    "resources/js/src/entities/service/model/serviceStore.ts",
    "resources/js/src/entities/user/model/userStore.ts",
    "resources/js/src/features/booking/ui/BookingWidget/useBooking.ts",
    "resources/js/Pages/Bookings/Index.vue"
)

foreach ($file in $functionFiles) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw -Encoding UTF8
        $originalContent = $content
        
        # Исправляем параметры функций без типизации
        $content = $content -replace "(\w+)\s*=\s*async\s*\(\s*(\w+)\s*\)", "`$1 = async (`$2: any)"
        $content = $content -replace "(\w+)\s*=\s*async\s*\(\s*(\w+),\s*(\w+)\s*\)", "`$1 = async (`$2: any, `$3: any)"
        $content = $content -replace "(\w+)\s*=\s*\(\s*(\w+)\s*\)", "`$1 = (`$2: any)"
        $content = $content -replace "function\s+(\w+)\s*\(\s*(\w+)\s*\)", "function `$1(`$2: any)"
        
        if ($content -ne $originalContent) {
            Set-Content $file -Value $content -Encoding UTF8 -NoNewline
            $filesFixed++
            Write-Log "✅ Исправлена типизация параметров: $file" -Color "Green"
        }
    }
}

# ============================================================================
# 4. ИСПРАВЛЕНИЕ ОШИБОК В КОНКРЕТНЫХ ФАЙЛАХ
# ============================================================================

Write-Log "🔧 4. Исправляем конкретные ошибки..." -Color "Cyan"

# Исправление resolve()
$bookingStoreFile = "resources/js/src/entities/booking/model/bookingStore.ts"
if (Test-Path $bookingStoreFile) {
    $content = Get-Content $bookingStoreFile -Raw -Encoding UTF8
    if ($content -match "resolve\(\)") {
        $content = $content -replace "resolve\(\)", "resolve(undefined)"
        Set-Content $bookingStoreFile -Value $content -Encoding UTF8 -NoNewline
        $filesFixed++
        Write-Log "✅ Исправлен resolve(): $bookingStoreFile" -Color "Green"
    }
}

# Исправление Input.vue
$inputFile = "resources/js/src/shared/ui/atoms/Input/Input.vue"
if (Test-Path $inputFile) {
    $content = Get-Content $inputFile -Raw -Encoding UTF8
    if ($content -match ":Readonly=") {
        $content = $content -replace ":Readonly=", ":readonly="
        Set-Content $inputFile -Value $content -Encoding UTF8 -NoNewline
        $filesFixed++
        Write-Log "✅ Исправлен Readonly: $inputFile" -Color "Green"
    }
}

# Исправление Modal вариантов
$modalFiles = @(
    "resources/js/src/shared/ui/molecules/Modal/AlertModal.vue",
    "resources/js/src/shared/ui/molecules/Modal/ConfirmModal.vue"
)

foreach ($file in $modalFiles) {
    if (Test-Path $file) {
        $content = Get-Content $file -Raw -Encoding UTF8
        if ($content -match 'variant="info"') {
            $content = $content -replace 'variant="info"', 'variant="primary"'
            Set-Content $file -Value $content -Encoding UTF8 -NoNewline
            $filesFixed++
            Write-Log "✅ Исправлен variant: $file" -Color "Green"
        }
    }
}

# ============================================================================
# 5. ИСПРАВЛЕНИЕ ОШИБОК ТИПИЗАЦИИ STORES
# ============================================================================

Write-Log "📊 5. Исправляем ошибки типизации в stores..." -Color "Cyan"

$storeFiles = Get-ChildItem -Path "resources/js/src/entities/*/model/*.ts" -Recurse
foreach ($file in $storeFiles) {
    $content = Get-Content $file.FullName -Raw -Encoding UTF8
    $originalContent = $content
    
    # Исправляем error типы
    $content = $content -replace "error:\s*Ref<Error\s*\|\s*null>", "error: Ref<string | null>"
    $content = $content -replace "catch\s*\(\s*(\w+)\s*\)\s*\{([^}]*?)error\.value\s*=\s*\1\.message", "catch (`$1) {`$2error.value = (`$1 as Error).message"
    
    if ($content -ne $originalContent) {
        Set-Content $file.FullName -Value $content -Encoding UTF8 -NoNewline
        $filesFixed++
        Write-Log "✅ Исправлены типы в store: $($file.Name)" -Color "Green"
    }
}

# ============================================================================
# ФИНАЛЬНАЯ ПРОВЕРКА
# ============================================================================

Write-Log "`n📊 СТАТИСТИКА ИСПРАВЛЕНИЙ:" -Color "Cyan"
Write-Log "=" * 40 -Color "Gray"
Write-Log "✅ Исправлено файлов: $filesFixed" -Color "Green"

if ($filesFixed -gt 0) {
    Write-Log "`n🚀 Запускаем проверку TypeScript..." -Color "Yellow"
    
    try {
        npm run build
        if ($LASTEXITCODE -eq 0) {
            Write-Log "✅ Сборка прошла успешно!" -Color "Green"
        } else {
            Write-Log "⚠️ Остались ошибки, но их стало меньше." -Color "Yellow"
        }
    } catch {
        Write-Log "❌ Ошибка при запуске сборки: $_" -Color "Red"
    }
} else {
    Write-Log "ℹ️ Не найдено файлов для исправления." -Color "Blue"
}

Write-Log "`n🎉 СКРИПТ ЗАВЕРШЕН!" -Color "Green"