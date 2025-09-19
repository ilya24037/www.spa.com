# knowledge-sync.ps1 - Синхронизация знаний проекта с агентами
# Автоматически обновляет базу знаний всех агентов из документации проекта

param(
    [string]$Mode = "sync",  # sync|check|update
    [switch]$Verbose
)

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot\..

Write-Host "📚 Knowledge Sync System for Virtual Office" -ForegroundColor Cyan
Write-Host "=" * 60

# Пути к документации
$DocsPath = "..\..\docs"
$KnowledgeMapPath = "$DocsPath\KNOWLEDGE_MAP_2025.md"
$LessonsPath = "$DocsPath\LESSONS"
$ProblemsPath = "$DocsPath\PROBLEMS"
$RefactoringPath = "$DocsPath\REFACTORING"

# Пути к агентам
$AgentsPath = "."
$VirtualOfficePath = "virtual-office\knowledge"

# Функция логирования
function Write-Log {
    param([string]$Message, [string]$Type = "INFO")

    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] [$Type] $Message"

    switch ($Type) {
        "ERROR" { Write-Host $logMessage -ForegroundColor Red }
        "SUCCESS" { Write-Host $logMessage -ForegroundColor Green }
        "WARNING" { Write-Host $logMessage -ForegroundColor Yellow }
        default { Write-Host $logMessage -ForegroundColor White }
    }

    # Сохранение в лог файл
    Add-Content -Path "system\logs\knowledge-sync.log" -Value $logMessage
}

# Создание структуры если не существует
function Initialize-KnowledgeStructure {
    Write-Log "Инициализация структуры знаний..." "INFO"

    $directories = @(
        "virtual-office\knowledge",
        "virtual-office\knowledge\lessons",
        "virtual-office\knowledge\problems",
        "virtual-office\knowledge\patterns",
        "virtual-office\knowledge\refactoring",
        "system\logs"
    )

    foreach ($dir in $directories) {
        if (-not (Test-Path $dir)) {
            New-Item -ItemType Directory -Path $dir -Force | Out-Null
            Write-Log "Создана директория: $dir" "SUCCESS"
        }
    }
}

# Синхронизация карты знаний
function Sync-KnowledgeMap {
    Write-Log "Синхронизация карты знаний..." "INFO"

    if (Test-Path $KnowledgeMapPath) {
        $content = Get-Content $KnowledgeMapPath -Raw -Encoding UTF8

        # Сохранение основной карты
        Set-Content -Path "$VirtualOfficePath\KNOWLEDGE_MAP.md" -Value $content -Encoding UTF8

        # Извлечение быстрых команд для агентов
        $quickCommands = @"
# ⚡ Быстрые команды из карты знаний

## При ошибке "Невозможно выполнить"
``````bash
grep -r "текст ошибки" app/Domain/*/Actions/
``````

## При проблеме сохранения данных
1. Проверить `$fillable в модели
2. Проверить watcher в компоненте
3. Проверить emit формат

## Поиск решений
``````bash
grep -r "похожая проблема" docs/LESSONS/
find app/ -name "*похожий_функционал*"
``````

## Принципы
- KISS - всегда простейшее решение
- Бизнес-логика сначала
- Минимальные изменения
"@
        Set-Content -Path "$VirtualOfficePath\QUICK_REFERENCE.md" -Value $quickCommands -Encoding UTF8

        Write-Log "Карта знаний синхронизирована" "SUCCESS"
    } else {
        Write-Log "Карта знаний не найдена: $KnowledgeMapPath" "WARNING"
    }
}

# Синхронизация уроков
function Sync-Lessons {
    Write-Log "Синхронизация извлеченных уроков..." "INFO"

    if (Test-Path $LessonsPath) {
        # Копирование критических файлов
        $criticalLessons = @(
            "APPROACHES\BUSINESS_LOGIC_FIRST.md",
            "ANTI_PATTERNS\FRONTEND_FIRST_DEBUGGING.md",
            "WORKFLOWS\NEW_TASK_WORKFLOW.md",
            "QUICK_WINS\STATUS_VALIDATION_PATTERNS.md",
            "QUICK_REFERENCE.md"
        )

        foreach ($lesson in $criticalLessons) {
            $sourcePath = Join-Path $LessonsPath $lesson
            if (Test-Path $sourcePath) {
                $fileName = Split-Path $lesson -Leaf
                Copy-Item $sourcePath "$VirtualOfficePath\lessons\$fileName" -Force
                if ($Verbose) {
                    Write-Log "Скопирован урок: $fileName" "SUCCESS"
                }
            }
        }

        # Создание сводки уроков
        $lessonsSummary = @"
# 📚 Сводка критических уроков проекта

## ⚡ Главные принципы
1. **BUSINESS_LOGIC_FIRST** - при ошибках сначала backend
2. **Анти-паттерн FRONTEND_FIRST** - НЕ меняй frontend при API ошибках
3. **KISS** - простейшее решение всегда лучше
4. **Watchers обязательны** - иначе данные потеряются

## 🔍 Быстрые решения
- Ошибка статуса → grep → Action → минимальное изменение (5-30 мин)
- Данные не сохраняются → проверь `$fillable и watchers (10 мин)
- Новая функция → найди аналог → скопируй паттерн (30-60 мин)

## 🚫 Чего НЕ делать
- НЕ создавай новые API при ошибках валидации
- НЕ усложняй простые задачи
- НЕ игнорируй существующие паттерны
"@
        Set-Content -Path "$VirtualOfficePath\lessons\SUMMARY.md" -Value $lessonsSummary -Encoding UTF8

        Write-Log "Уроки синхронизированы: $($criticalLessons.Count) файлов" "SUCCESS"
    }
}

# Синхронизация проблем
function Sync-Problems {
    Write-Log "Синхронизация решенных проблем..." "INFO"

    if (Test-Path $ProblemsPath) {
        # Создание индекса проблем
        $problems = Get-ChildItem -Path $ProblemsPath -Filter "*.md" | Select-Object -First 20

        $problemsIndex = @"
# 🐛 Индекс решенных проблем

## Топ частых проблем
"@

        foreach ($problem in $problems) {
            $content = Get-Content $problem.FullName -First 10 | Out-String
            $title = ($content | Select-String -Pattern "^#\s+(.+)" | Select-Object -First 1).Matches[0].Groups[1].Value

            if ($title) {
                $problemsIndex += "`n- **$($problem.BaseName)**: $title"
            }
        }

        Set-Content -Path "$VirtualOfficePath\problems\INDEX.md" -Value $problemsIndex -Encoding UTF8

        Write-Log "Индекс проблем создан: $($problems.Count) записей" "SUCCESS"
    }
}

# Обновление конфигурации агентов
function Update-AgentConfigs {
    Write-Log "Обновление конфигурации агентов..." "INFO"

    # Добавление путей к знаниям в agents.json
    $configPath = "system\agents.json"
    if (Test-Path $configPath) {
        $config = Get-Content $configPath -Raw | ConvertFrom-Json

        # Добавление knowledge_path для каждого агента
        foreach ($agentName in $config.agents.PSObject.Properties.Name) {
            $agent = $config.agents.$agentName

            if (-not $agent.knowledge_path) {
                Add-Member -InputObject $agent -MemberType NoteProperty -Name "knowledge_path" -Value "virtual-office/knowledge" -Force
            }

            # Специфичные знания для агентов
            switch ($agentName) {
                "backend" {
                    Add-Member -InputObject $agent -MemberType NoteProperty -Name "patterns" -Value @(
                        "BUSINESS_LOGIC_FIRST",
                        "Repository Pattern",
                        "Service Layer"
                    ) -Force
                }
                "frontend" {
                    Add-Member -InputObject $agent -MemberType NoteProperty -Name "patterns" -Value @(
                        "Watcher Pattern",
                        "Computed Protection",
                        "FSD Architecture"
                    ) -Force
                }
                "qa" {
                    Add-Member -InputObject $agent -MemberType NoteProperty -Name "test_patterns" -Value @(
                        "Data Chain Testing",
                        "Regression Testing",
                        "Status Validation"
                    ) -Force
                }
            }
        }

        $config | ConvertTo-Json -Depth 10 | Set-Content $configPath -Encoding UTF8
        Write-Log "Конфигурация агентов обновлена" "SUCCESS"
    }
}

# Создание быстрых скриптов для агентов
function Create-QuickScripts {
    Write-Log "Создание быстрых скриптов..." "INFO"

    # Скрипт быстрого поиска решения
    $quickFixScript = @'
# quick-fix.ps1 - Быстрый поиск решения проблемы
param([string]$Error)

Write-Host "🔍 Поиск решения для: $Error" -ForegroundColor Cyan

# Поиск в уроках
$lessons = Get-ChildItem "virtual-office\knowledge\lessons" -Filter "*.md"
foreach ($lesson in $lessons) {
    $content = Get-Content $lesson.FullName -Raw
    if ($content -match $Error) {
        Write-Host "✅ Найдено решение в: $($lesson.Name)" -ForegroundColor Green
        Write-Host $content
        break
    }
}

# Поиск в проблемах
$problems = Get-Content "virtual-office\knowledge\problems\INDEX.md" -Raw
if ($problems -match $Error) {
    Write-Host "📋 Похожие проблемы найдены в индексе" -ForegroundColor Yellow
}
'@

    Set-Content -Path "scripts\quick-fix.ps1" -Value $quickFixScript -Encoding UTF8

    Write-Log "Быстрые скрипты созданы" "SUCCESS"
}

# Проверка синхронизации
function Check-Sync {
    Write-Log "Проверка статуса синхронизации..." "INFO"

    $status = @{
        KnowledgeMap = Test-Path "$VirtualOfficePath\KNOWLEDGE_MAP.md"
        Lessons = Test-Path "$VirtualOfficePath\lessons\SUMMARY.md"
        Problems = Test-Path "$VirtualOfficePath\problems\INDEX.md"
        QuickRef = Test-Path "$VirtualOfficePath\QUICK_REFERENCE.md"
    }

    Write-Host "`n📊 Статус синхронизации:" -ForegroundColor Cyan
    foreach ($item in $status.GetEnumerator()) {
        $icon = if ($item.Value) { "✅" } else { "❌" }
        $color = if ($item.Value) { "Green" } else { "Red" }
        Write-Host "$icon $($item.Key)" -ForegroundColor $color
    }

    $synced = ($status.Values | Where-Object { $_ -eq $true }).Count
    $total = $status.Count

    Write-Host "`nСинхронизировано: $synced/$total" -ForegroundColor $(if ($synced -eq $total) { "Green" } else { "Yellow" })

    return $synced -eq $total
}

# Основная логика
try {
    Initialize-KnowledgeStructure

    switch ($Mode) {
        "sync" {
            Write-Host "`n🔄 Полная синхронизация..." -ForegroundColor Cyan
            Sync-KnowledgeMap
            Sync-Lessons
            Sync-Problems
            Update-AgentConfigs
            Create-QuickScripts

            if (Check-Sync) {
                Write-Log "Синхронизация завершена успешно!" "SUCCESS"
            } else {
                Write-Log "Синхронизация завершена с предупреждениями" "WARNING"
            }
        }

        "check" {
            Check-Sync
        }

        "update" {
            Write-Host "`n🔄 Обновление конфигураций..." -ForegroundColor Cyan
            Update-AgentConfigs
            Write-Log "Конфигурации обновлены" "SUCCESS"
        }

        default {
            Write-Log "Неизвестный режим: $Mode" "ERROR"
            exit 1
        }
    }

    Write-Host "`n✅ Готово!" -ForegroundColor Green
    Write-Host "Агенты теперь имеют доступ к полной базе знаний проекта." -ForegroundColor Cyan
    Write-Host "Используйте quick-fix.ps1 для быстрого поиска решений." -ForegroundColor Yellow
}
catch {
    Write-Log "Ошибка: $_" "ERROR"
    exit 1
}