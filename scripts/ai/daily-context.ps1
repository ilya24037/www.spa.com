# Daily Context Generator - Генерация стартового контекста дня
# Применяет все 5 хаков из статьи о контекстной инженерии

param(
    [switch]$Silent = $false
)

$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$repoRoot = "C:\www.spa.com"
Set-Location $repoRoot

if (-not $Silent) {
    Write-Host "📋 Генерирую контекст дня..." -ForegroundColor Cyan
}

# Собираем информацию
$date = Get-Date -Format "yyyy-MM-dd HH:mm"
$branch = git branch --show-current 2>$null
$uncommitted = @(git status --short 2>$null).Count
$lastCommit = git log -1 --pretty=format:"%h - %s (%cr)" 2>$null

# Читаем последние TODO из файлов
$todos = @()
Get-ChildItem -Path @("app", "resources/js") -Include "*.php","*.vue","*.ts" -Recurse -ErrorAction SilentlyContinue | 
    Select-Object -First 20 |
    ForEach-Object {
        $content = Get-Content $_ -ErrorAction SilentlyContinue | Select-String "TODO:|FIXME:|HACK:" -Context 0,1
        if ($content) {
            $todos += "- $($_.Name): $($content.Line.Trim())"
        }
    }

# Формируем контекст с применением всех хаков
$context = @"
# 🌅 Daily Context - $date

## 📊 Project State (Context Injection):
- **Branch**: ``$branch``
- **Uncommitted files**: $uncommitted
- **Last commit**: $lastCommit
- **Laravel**: 12.x | **Vue**: 3.x | **TypeScript**: Required

## 🎯 Quick Start Commands:
``````
Ultrathink, вспомни CLAUDE.md и AI_CONTEXT.md
``````

## 📝 Active TODOs in code:
$(if ($todos) { $todos | Select-Object -First 5 | ForEach-Object { $_ } | Out-String } else { "No TODOs found" })

## ⚓ Today's Work Template (Structural Anchor):
1. Review CLAUDE.md architecture rules
2. Check current git status
3. Implement with TypeScript
4. Follow FSD/DDD structure
5. Test changes locally
6. Verify backward compatibility

## 🧠 Smart Reminders (Forced Injection):
- Architecture: Frontend FSD (entities/features/widgets)
- Architecture: Backend DDD (Domain/Application/Infrastructure)
- All Vue components need TypeScript interfaces
- Business logic belongs in Services, not Controllers

## 📋 Summary Request Template:
When needed, ask: "Резюмируй текущий контекст и прогресс"

## ⚠️ CRITICAL - LAST WORD (Most Important):
1. **MUST** use TypeScript for all new code
2. **NEVER** put logic in controllers
3. **ALWAYS** check: "Критически оцени свою работу"
4. **FOLLOW** CLAUDE.md architecture strictly
5. **PRESERVE** backward compatibility always
"@

# Копируем в буфер обмена
$context | Set-Clipboard

if (-not $Silent) {
    Write-Host "✅ Контекст дня создан и скопирован в буфер обмена!" -ForegroundColor Green
    Write-Host ""
    Write-Host "📌 Что делать:" -ForegroundColor Yellow
    Write-Host "1. Вставьте в Claude/Cursor (Ctrl+V)" -ForegroundColor White
    Write-Host "2. Начните работу!" -ForegroundColor White
}

# Сохраняем копию
$contextFile = "storage\ai-sessions\daily\context_$(Get-Date -Format 'yyyy-MM-dd').md"
$dir = Split-Path $contextFile -Parent
if (-not (Test-Path $dir)) {
    New-Item -ItemType Directory -Path $dir -Force | Out-Null
}
$context | Out-File -FilePath $contextFile -Encoding UTF8

exit 0