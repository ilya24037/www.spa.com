# Smart Context Watcher с применением принципов контекстной инженерии
# Основан на статье Habr о контекстной инженерии LLM

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

# Конфигурация приоритетов (по принципу иерархии важности)
$PRIORITY_CRITICAL = @('CLAUDE.md', 'AI_CONTEXT.md', '.env', 'composer.json', 'package.json')
$PRIORITY_HIGH = @('*.ts', '*.vue', '*.php')
$PRIORITY_MEDIUM = @('*.js', '*.css')
$PRIORITY_LOW = @('*.md', '*.txt')

# Корень проекта
$repoRoot = (Resolve-Path -LiteralPath "C:\www.spa.com").ProviderPath
Set-Location $repoRoot

Write-Host "[Smart Watcher] 🚀 Запущен с оптимизацией контекста" -ForegroundColor Cyan
Write-Host "[Smart Watcher] 📁 Отслеживаю: $repoRoot" -ForegroundColor Gray

# Хранилище изменений с приоритетами
$changes = [hashtable]::Synchronized(@{
    critical = @()
    high = @()
    medium = @()
    low = @()
    lastUpdate = $null
})

# Функция определения приоритета файла
function Get-FilePriority([string]$path) {
    $fileName = Split-Path $path -Leaf
    
    if ($PRIORITY_CRITICAL -contains $fileName) { return 'critical' }
    foreach ($pattern in $PRIORITY_HIGH) {
        if ($fileName -like $pattern) { return 'high' }
    }
    foreach ($pattern in $PRIORITY_MEDIUM) {
        if ($fileName -like $pattern) { return 'medium' }
    }
    return 'low'
}

# Функция создания оптимизированного контекста
function Create-OptimizedContext {
    $contextFile = Join-Path $repoRoot "storage\ai-sessions\smart\context_$(Get-Date -Format 'yyyy-MM-dd_HH-mm-ss').md"
    $dir = Split-Path $contextFile -Parent
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }

    $content = @"
# 🧠 Smart Context (Optimized for LLM)
Generated: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')

## 📋 CONTEXT INJECTION (Remember this):
- Project: SPA Platform
- Stack: Laravel 12 + Vue 3 + TypeScript
- Architecture: Backend DDD, Frontend FSD
- Working directory: $repoRoot

"@

    # 1. КРИТИЧЕСКИЕ изменения (первый приоритет)
    if ($changes.critical.Count -gt 0) {
        $content += @"

## 🔴 CRITICAL CHANGES (Highest Priority):
These files affect the entire project structure:

"@
        foreach ($file in $changes.critical | Select-Object -Unique -Last 5) {
            $content += "- ``$file`` - ARCHITECTURE/CONFIG CHANGE`n"
        }
    }

    # 2. ВАЖНЫЕ изменения (бизнес-логика)
    if ($changes.high.Count -gt 0) {
        $content += @"

## 🟡 IMPORTANT CHANGES (Business Logic):
"@
        foreach ($file in $changes.high | Select-Object -Unique -Last 10) {
            $content += "- ``$file```n"
        }
    }

    # 3. Git статус (текущее состояние)
    $gitStatus = git status --short 2>$null
    if ($gitStatus) {
        $content += @"

## 📝 Current Git Status:
``````bash
$gitStatus
``````

"@
    }

    # 4. СТРУКТУРНЫЙ ЯКОРЬ для задач
    $content += @"

## ⚓ TASK TEMPLATE (Structural Anchor):
When implementing changes:
1. Check TypeScript types first
2. Verify FSD/DDD architecture compliance  
3. Ensure backward compatibility
4. Add error handling
5. Update tests if needed

"@

    # 5. ПОСЛЕДНЕЕ СЛОВО (критически важно в конце!)
    $content += @"

## ⚠️ CRITICAL REMINDERS (LAST WORD - READ THIS!):
1. **NEVER** put business logic in controllers
2. **ALWAYS** use TypeScript with proper types
3. **FOLLOW** FSD structure for frontend (src/entities, features, widgets)
4. **FOLLOW** DDD structure for backend (Domain/Application/Infrastructure)
5. **PRESERVE** backward compatibility
6. **CHECK** your work: "Critically evaluate what you did"
"@

    # Сохраняем и копируем в буфер
    $content | Out-File -FilePath $contextFile -Encoding UTF8
    $content | Set-Clipboard
    
    Write-Host "[Smart Watcher] ✅ Контекст создан и скопирован в буфер" -ForegroundColor Green
    Write-Host "[Smart Watcher] 📄 Сохранен: $contextFile" -ForegroundColor Gray
    
    # Очищаем накопленные изменения
    $changes.critical = @()
    $changes.high = @()
    $changes.medium = @()
    $changes.low = @()
    $changes.lastUpdate = Get-Date
}

# Настройка FileSystemWatcher
$watcher = New-Object System.IO.FileSystemWatcher
$watcher.Path = $repoRoot
$watcher.Filter = '*.*'
$watcher.IncludeSubdirectories = $true
$watcher.EnableRaisingEvents = $true

# Исключения
$excludePaths = @('node_modules', 'vendor', 'storage\logs', 'storage\framework', '.git', 'public\build')

function Should-Exclude([string]$path) {
    foreach ($exclude in $excludePaths) {
        if ($path -like "*\$exclude\*" -or $path -like "*/$exclude/*") {
            return $true
        }
    }
    return $false
}

# Обработчик изменений
$action = {
    param($sender, $e)
    
    $path = $e.FullPath
    if (Should-Exclude $path) { return }
    
    $relativePath = $path.Replace($repoRoot, '').TrimStart('\').TrimStart('/')
    $priority = Get-FilePriority $path
    
    # Добавляем в соответствующий приоритет
    if (-not $changes[$priority].Contains($relativePath)) {
        $changes[$priority] += $relativePath
        
        $icon = switch($priority) {
            'critical' { '🔴' }
            'high' { '🟡' }
            'medium' { '🔵' }
            'low' { '⚪' }
        }
        
        Write-Host "[Smart Watcher] $icon Change detected: $relativePath" -ForegroundColor Cyan
    }
}

# Регистрация событий
Register-ObjectEvent -InputObject $watcher -EventName "Changed" -Action $action | Out-Null
Register-ObjectEvent -InputObject $watcher -EventName "Created" -Action $action | Out-Null
Register-ObjectEvent -InputObject $watcher -EventName "Deleted" -Action $action | Out-Null
Register-ObjectEvent -InputObject $watcher -EventName "Renamed" -Action $action | Out-Null

# Таймер для умной генерации контекста (каждые 30 секунд)
$timer = New-Object System.Timers.Timer
$timer.Interval = 30000
$timer.AutoReset = $true

Register-ObjectEvent -InputObject $timer -EventName Elapsed -Action {
    $hasChanges = ($changes.critical.Count + $changes.high.Count + $changes.medium.Count + $changes.low.Count) -gt 0
    
    if ($hasChanges) {
        Write-Host "[Smart Watcher] 🔄 Обнаружены изменения, создаю оптимизированный контекст..." -ForegroundColor Yellow
        Create-OptimizedContext
    }
} | Out-Null

$timer.Start()

# Создаем начальный контекст при запуске
Write-Host "[Smart Watcher] 📋 Создаю начальный контекст дня..." -ForegroundColor Yellow
Create-OptimizedContext

Write-Host "[Smart Watcher] ✅ Готов к работе. Контекст обновляется автоматически." -ForegroundColor Green
Write-Host "[Smart Watcher] 💡 Совет: Контекст всегда в буфере обмена - просто Ctrl+V в Claude!" -ForegroundColor Cyan

# Бесконечный цикл
while ($true) {
    Start-Sleep -Seconds 1
}