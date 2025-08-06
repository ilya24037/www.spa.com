#!/usr/bin/env pwsh
# Скрипт для автоматического исправления TS2304 ошибок в SPA Platform
# Использование: .\fix-ts2304-errors.ps1

Write-Host "🔧 Исправление TS2304 ошибок в проекте SPA Platform" -ForegroundColor Green
Write-Host "=" * 60

$resourcesPath = "resources\js"
$backupPath = "ts-backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"

# Создание резервной копии
Write-Host "📁 Создание резервной копии в: $backupPath" -ForegroundColor Yellow
Copy-Item -Recurse $resourcesPath $backupPath

# Счетчики
$fixed = 0
$errors = 0

# Функция исправления файла
function Fix-VueFile {
    param($filePath)
    
    try {
        $content = Get-Content $filePath -Raw -Encoding UTF8
        $originalContent = $content
        $changed = $false
        
        # 1. Замена <script setup> на <script setup lang="ts">
        if ($content -match '<script setup>') {
            $content = $content -replace '<script setup>', '<script setup lang="ts">'
            $changed = $true
            Write-Host "  ✓ Добавлен lang='ts'" -ForegroundColor Green
        }
        
        # 2. Добавление типов для глобальных переменных
        $globalVars = @{
            'window\.' = 'window as any'
            'document\.' = 'document as any' 
            'console\.' = 'console as any'
            '\bconfirm\(' = '(window as any).confirm('
            '\balert\(' = '(window as any).alert('
            '\bprompt\(' = '(window as any).prompt('
            'new URLSearchParams' = 'new (window as any).URLSearchParams'
            'new URL\(' = 'new (window as any).URL('
            '\bfetch\(' = '(window as any).fetch('
            'localStorage\.' = '(window as any).localStorage.'
            'sessionStorage\.' = '(window as any).sessionStorage.'
        }
        
        foreach ($pattern in $globalVars.Keys) {
            if ($content -match $pattern) {
                $replacement = $globalVars[$pattern]
                $content = $content -replace $pattern, $replacement
                $changed = $true
                Write-Host "  ✓ Исправлен паттерн: $pattern" -ForegroundColor Green
            }
        }
        
        # 3. Исправление импортов Inertia.js
        if ($content -match '\broute\(' -and $content -notmatch "import.*route.*from.*ziggy") {
            # Поиск существующих импортов из @inertiajs/vue3
            if ($content -match "import\s+{([^}]+)}\s+from\s+'@inertiajs/vue3'") {
                $imports = $matches[1]
                if ($imports -notmatch 'router') {
                    $newImports = $imports.Trim() + ', router'
                    $content = $content -replace "import\s+{[^}]+}\s+from\s+'@inertiajs/vue3'", "import { $newImports } from '@inertiajs/vue3'"
                    $changed = $true
                }
            }
            
            # Добавление импорта route из ziggy-js
            $scriptMatch = $content -match '<script setup lang="ts">(.*?)</script>'
            if ($scriptMatch) {
                $imports = "import { route } from 'ziggy-js'`n"
                $content = $content -replace '(<script setup lang="ts">)', "`$1`n$imports"
                $changed = $true
                Write-Host "  ✓ Добавлен импорт route из ziggy-js" -ForegroundColor Green
            }
        }
        
        # 4. Добавление типизации для useForm и usePage
        $composables = @{
            'useForm\(' = 'useForm'
            'usePage\(' = 'usePage'
        }
        
        foreach ($pattern in $composables.Keys) {
            if ($content -match $pattern -and $content -notmatch "import.*$($composables[$pattern]).*from.*@inertiajs") {
                # Добавить импорт если его нет
                if ($content -match "import\s+{([^}]+)}\s+from\s+'@inertiajs/vue3'") {
                    $imports = $matches[1]
                    $composableName = $composables[$pattern]
                    if ($imports -notmatch $composableName) {
                        $newImports = $imports.Trim() + ", $composableName"
                        $content = $content -replace "import\s+{[^}]+}\s+from\s+'@inertiajs/vue3'", "import { $newImports } from '@inertiajs/vue3'"
                        $changed = $true
                        Write-Host "  ✓ Добавлен импорт $composableName" -ForegroundColor Green
                    }
                }
            }
        }
        
        # Сохранение файла если были изменения
        if ($changed) {
            Set-Content -Path $filePath -Value $content -Encoding UTF8 -NoNewline
            $script:fixed++
            Write-Host "✅ Исправлен: $filePath" -ForegroundColor Green
        }
        
    } catch {
        Write-Host "❌ Ошибка в файле $filePath`: $($_.Exception.Message)" -ForegroundColor Red
        $script:errors++
    }
}

# Поиск и исправление файлов
Write-Host "🔍 Поиск Vue файлов с ошибками..." -ForegroundColor Cyan

Get-ChildItem -Path $resourcesPath -Recurse -Filter "*.vue" | ForEach-Object {
    $filePath = $_.FullName
    $relativePath = $filePath.Replace((Get-Location).Path + "\", "")
    
    # Проверка на наличие проблем
    $content = Get-Content $filePath -Raw -Encoding UTF8 -ErrorAction SilentlyContinue
    
    if ($content -match '<script setup>' -or 
        $content -match '\b(window|document|console|confirm|alert|fetch|localStorage)\.' -or
        ($content -match '\broute\(' -and $content -notmatch "import.*route.*from")) {
        
        Write-Host "🔧 Исправление: $relativePath" -ForegroundColor Yellow
        Fix-VueFile $filePath
    }
}

# Исправление глобальных типов
$globalTypesPath = "$resourcesPath\types\global.d.ts"
if (Test-Path $globalTypesPath) {
    Write-Host "🔧 Обновление глобальных типов..." -ForegroundColor Yellow
    
    $globalContent = Get-Content $globalTypesPath -Raw
    
    # Добавление недостающих глобальных типов
    $newTypes = @"

// Дополнительные глобальные типы для исправления TS2304
declare global {
  interface Window {
    route: Route
    axios: any
    Echo?: any
    confirm: (message: string) => boolean
    alert: (message: string) => void
    prompt: (message: string, defaultValue?: string) => string | null
  }
  
  const route: Route
  const console: Console
  const window: Window & typeof globalThis
  const document: Document
  const URLSearchParams: typeof URLSearchParams
  const URL: typeof URL
  const fetch: typeof fetch
  const localStorage: Storage
  const sessionStorage: Storage
}
"@
    
    if ($globalContent -notmatch "confirm.*boolean") {
        $globalContent += $newTypes
        Set-Content -Path $globalTypesPath -Value $globalContent -Encoding UTF8
        Write-Host "✅ Обновлены глобальные типы" -ForegroundColor Green
    }
}

# Итоги
Write-Host "`n" + "=" * 60
Write-Host "📊 ИТОГИ ИСПРАВЛЕНИЯ:" -ForegroundColor Cyan
Write-Host "✅ Исправлено файлов: $fixed" -ForegroundColor Green
Write-Host "❌ Ошибок: $errors" -ForegroundColor Red
Write-Host "📁 Резервная копия: $backupPath" -ForegroundColor Yellow

if ($fixed -gt 0) {
    Write-Host "`n🔄 Рекомендуется запустить проверку типов:" -ForegroundColor Yellow
    Write-Host "npm run type-check" -ForegroundColor White
}

Write-Host "`n🎉 Скрипт завершен!" -ForegroundColor Green