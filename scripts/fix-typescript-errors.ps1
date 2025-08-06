# Скрипт для автоматического исправления TypeScript ошибок
# Автор: AI Assistant для SPA Platform
# Дата: $(Get-Date)

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
# 1. ИСПРАВЛЕНИЕ ОТСУТСТВУЮЩИХ ИМПОРТОВ
# ============================================================================

Write-Log "📦 1. Исправляем отсутствующие импорты..." -Color "Cyan"

# Исправление отсутствующих ref импортов
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
    $replacements = @(
        @{
            Pattern = "import\s*\{\s*([^}]*)\s*\}\s*from\s*['""]vue['""]"
            Replacement = "import { ref, `$1 } from 'vue'"
            Description = "Добавлен импорт ref"
        }
    )
    Fix-File $file $replacements "Добавление ref импорта"
}

# Исправление route импортов
$routeFiles = @(
    "resources/js/Pages/Auth/VerifyEmail.vue",
    "resources/js/src/features/auth/ui/AuthModal/AuthModal.vue",
    "resources/js/src/features/auth/ui/RegisterModal/RegisterModal.vue"
)

foreach ($file in $routeFiles) {
    $replacements = @(
        @{
            Pattern = "(import\s*\{[^}]*\}\s*from\s*['""]@inertiajs/vue3['""];?)"
            Replacement = "`$1`nimport { route } from 'ziggy-js';"
            Description = "Добавлен импорт route"
        }
    )
    Fix-File $file $replacements "Добавление route импорта"
}

# ============================================================================
# 2. ИСПРАВЛЕНИЕ ТИПИЗАЦИИ ПАРАМЕТРОВ ФУНКЦИЙ
# ============================================================================

Write-Log "🎯 2. Исправляем типизацию параметров функций..." -Color "Cyan"

# Паттерны для исправления типизации
$typingPatterns = @(
    @{
        Pattern = "const\s+(\w+)\s*=\s*(async\s*)?\(\s*([^)]+)\s*\)\s*=>"
        Replacement = "const `$1 = `$2(`$3: any) =>"
        Description = "Добавлена типизация any для параметров функций"
    },
    @{
        Pattern = "function\s+(\w+)\s*\(\s*([^)]+)\s*\)"
        Replacement = "function `$1(`$2: any)"
        Description = "Добавлена типизация any для параметров обычных функций"
    }
)

# Файлы для исправления типизации
$storeFiles = @(
    "resources/js/src/entities/master/model/masterStore.ts",
    "resources/js/src/entities/service/model/serviceStore.ts", 
    "resources/js/src/entities/user/model/userStore.ts",
    "resources/js/src/features/booking/ui/BookingWidget/useBooking.ts",
    "resources/js/Pages/Bookings/Index.vue"
)

foreach ($file in $storeFiles) {
    Fix-File $file $typingPatterns "Исправление типизации параметров"
}

# ============================================================================
# 3. ИСПРАВЛЕНИЕ ОШИБОК ТИПИЗАЦИИ ПЕРЕМЕННЫХ
# ============================================================================

Write-Log "🔧 3. Исправляем ошибки типизации переменных..." -Color "Cyan"

# Исправление error типов в stores
$errorFixPatterns = @(
    @{
        Pattern = "error:\s*Ref<Error\s*\|\s*null>"
        Replacement = "error: Ref<string | null>"
        Description = "Исправлен тип error на string | null"
    },
    @{
        Pattern = "catch\s*\(\s*(\w+)\s*\)\s*\{[^}]*error\.value\s*=\s*\1\.message"
        Replacement = "catch (`$1) { error.value = (`$1 as Error).message"
        Description = "Добавлено приведение типа в catch блоке"
    },
    @{
        Pattern = "resolve\(\)"
        Replacement = "resolve(undefined)"
        Description = "Исправлен вызов resolve() на resolve(undefined)"
    }
)

$allStoreFiles = Get-ChildItem -Path "resources/js/src/entities/*/model/*.ts" -Recurse
foreach ($file in $allStoreFiles) {
    Fix-File $file.FullName $errorFixPatterns "Исправление типизации error"
}

# ============================================================================
# 4. ИСПРАВЛЕНИЕ ДУБЛИРУЮЩИХСЯ ЭКСПОРТОВ
# ============================================================================

Write-Log "📋 4. Исправляем дублирующиеся экспорты..." -Color "Cyan"

$duplicateExportPatterns = @(
    @{
        Pattern = "(export\s*\{\s*default\s+as\s+(\w+)\s*\}.*\n[\s\S]*?)export\s*\{\s*[^}]*\2[^}]*\}"
        Replacement = "`$1"
        Description = "Удален дублирующийся экспорт"
    }
)

$indexFiles = Get-ChildItem -Path "resources/js" -Name "index.ts" -Recurse
foreach ($file in $indexFiles) {
    $fullPath = "resources/js/" + $file
    Fix-File $fullPath $duplicateExportPatterns "Исправление дублирующихся экспортов"
}

# ============================================================================
# 5. ИСПРАВЛЕНИЕ НЕИСПОЛЬЗУЕМЫХ ИМПОРТОВ
# ============================================================================

Write-Log "🧹 5. Удаляем неиспользуемые импорты..." -Color "Cyan"

$unusedImportPatterns = @(
    @{
        Pattern = "import\s+type\s*\{\s*([^}]*),\s*(\w+)\s*\}\s*from\s*[^;]+;\s*\n(?![^]*\2[^a-zA-Z_])"
        Replacement = ""
        Description = "Удален неиспользуемый тип импорт"
    },
    @{
        Pattern = "import\s*\{\s*([^}]*),\s*(\w+)\s*\}\s*from\s*[^;]+;\s*\n(?![^]*\2[^a-zA-Z_])"
        Replacement = "import { `$1 } from"
        Description = "Удален неиспользуемый импорт"
    }
)

$allVueFiles = Get-ChildItem -Path "resources/js" -Include "*.vue", "*.ts" -Recurse
foreach ($file in $allVueFiles) {
    Fix-File $file.FullName $unusedImportPatterns "Удаление неиспользуемых импортов"
}

# ============================================================================
# 6. ИСПРАВЛЕНИЕ СПЕЦИФИЧЕСКИХ ОШИБОК
# ============================================================================

Write-Log "🎯 6. Исправляем специфические ошибки..." -Color "Cyan"

# Исправление ошибок в конкретных файлах
$specificFixes = @{
    "resources/js/src/shared/ui/atoms/Input/Input.vue" = @(
        @{
            Pattern = ":Readonly=['""]Readonly['""]"
            Replacement = ":readonly=`"readonly`""
            Description = "Исправлен Readonly на readonly"
        }
    )
    "resources/js/src/shared/ui/molecules/Modal/AlertModal.vue" = @(
        @{
            Pattern = 'variant="info"'
            Replacement = 'variant="primary"'
            Description = "Исправлен variant с info на primary"
        }
    )
    "resources/js/src/shared/ui/molecules/Modal/ConfirmModal.vue" = @(
        @{
            Pattern = 'variant="info"'
            Replacement = 'variant="primary"'
            Description = "Исправлен variant с info на primary"
        }
    )
}

foreach ($filePath in $specificFixes.Keys) {
    Fix-File $filePath $specificFixes[$filePath] "Специфические исправления"
}

# ============================================================================
# ФИНАЛЬНАЯ ПРОВЕРКА И СТАТИСТИКА
# ============================================================================

Write-Log "`n📊 СТАТИСТИКА ИСПРАВЛЕНИЙ:" -Color "Cyan"
Write-Log "=" * 40 -Color "Gray"
Write-Log "✅ Исправлено файлов: $filesFixed" -Color "Green"
Write-Log "🔧 Исправлено ошибок: $errorsFixed" -Color "Green"

if ($filesFixed -gt 0) {
    Write-Log "`n🚀 Запускаем проверку TypeScript..." -Color "Yellow"
    
    try {
        $buildResult = npm run build 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Log "✅ Сборка прошла успешно! Все ошибки исправлены." -Color "Green"
        } else {
            Write-Log "⚠️  Остались ошибки. Результат сборки:" -Color "Yellow"
            Write-Host $buildResult -ForegroundColor "Gray"
        }
    } catch {
        Write-Log "❌ Ошибка при запуске сборки: $_" -Color "Red"
    }
} else {
    Write-Log "ℹ️  Не найдено файлов для исправления." -Color "Blue"
}

Write-Log "`n🎉 СКРИПТ ЗАВЕРШЕН!" -Color "Green"
Write-Host "=" * 60 -ForegroundColor "Gray"