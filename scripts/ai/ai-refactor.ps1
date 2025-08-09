# AI Refactor Helper - автоматизация рефакторинга с Claude
param(
    [Parameter(Mandatory=$true)]
    [string]$ComponentPath,
    
    [Parameter(Mandatory=$false)]
    [string]$Template = "refactor-component"
)

# Цвета для вывода
$Host.UI.RawUI.ForegroundColor = "White"

Write-Host "🤖 AI Refactor Assistant" -ForegroundColor Cyan
Write-Host "========================" -ForegroundColor Cyan

# Проверяем существование компонента
if (-not (Test-Path $ComponentPath)) {
    Write-Host "❌ Компонент не найден: $ComponentPath" -ForegroundColor Red
    exit 1
}

# Получаем имя компонента
$ComponentName = [System.IO.Path]::GetFileNameWithoutExtension($ComponentPath)

# Читаем шаблон
$TemplatePath = "scripts\ai\templates\$Template.md"
if (-not (Test-Path $TemplatePath)) {
    Write-Host "❌ Шаблон не найден: $TemplatePath" -ForegroundColor Red
    exit 1
}

$TemplateContent = Get-Content $TemplatePath -Raw
$TemplateContent = $TemplateContent -replace '\{COMPONENT_NAME\}', $ComponentName

# Читаем содержимое компонента
$ComponentContent = Get-Content $ComponentPath -Raw

# Формируем промпт с контекстной инженерией
$Prompt = @"
Ultrathink, вспомни CLAUDE.md и AI_CONTEXT.md

$TemplateContent

## Текущий код компонента:
``````vue
$ComponentContent
``````

## Напоминание контекста:
- Проект: SPA Platform (Laravel 12 + Vue 3)
- Архитектура: FSD
- TypeScript обязателен
- Путь компонента: $ComponentPath

Резюмируй план действий и начни рефакторинг.
КРИТИЧЕСКИ ВАЖНО: сохрани обратную совместимость!
"@

# Копируем в буфер обмена
$Prompt | Set-Clipboard

Write-Host "✅ Промпт скопирован в буфер обмена!" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Что делать дальше:" -ForegroundColor Yellow
Write-Host "1. Откройте Claude (claude.ai или Claude Code)" -ForegroundColor White
Write-Host "2. Вставьте промпт (Ctrl+V)" -ForegroundColor White
Write-Host "3. Claude выполнит рефакторинг по шаблону" -ForegroundColor White
Write-Host ""
Write-Host "💡 Совет: используйте команду 'Резюмируй что сделано' для проверки" -ForegroundColor Cyan