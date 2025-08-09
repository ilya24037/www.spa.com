# AI Create Helper - автоматизация создания новых компонентов
param(
    [Parameter(Mandatory=$true)]
    [ValidateSet("feature", "entity", "widget", "page", "domain")]
    [string]$Type,
    
    [Parameter(Mandatory=$true)]
    [string]$Name
)

Write-Host "🚀 AI Create Assistant" -ForegroundColor Cyan
Write-Host "=====================" -ForegroundColor Cyan

# Словарь шаблонов
$Templates = @{
    "feature" = "create-feature"
    "entity" = "create-entity"
    "widget" = "create-widget"
    "page" = "create-page"
    "domain" = "create-domain"
}

$Template = $Templates[$Type]
$TemplatePath = "scripts\ai\templates\$Template.md"

# Создаем шаблон если не существует
if (-not (Test-Path $TemplatePath)) {
    Write-Host "⚠️ Шаблон не найден, создаю базовый..." -ForegroundColor Yellow
    
    $BaseTemplate = @"
# Создание $Type: $Name

## Контекст:
- Laravel 12, Vue 3, TypeScript
- Архитектура: $(if($Type -in @("domain")) {"DDD"} else {"FSD"})

## Задача:
Создать $Type с именем $Name

## План:
1. Создать структуру папок
2. Добавить TypeScript типизацию
3. Реализовать основную логику
4. Добавить обработку ошибок
5. Создать тесты

## КРИТИЧЕСКИ ВАЖНО:
- TypeScript обязателен
- Следовать архитектуре проекта
- Добавить документацию
"@
    
    New-Item -Path $TemplatePath -Value $BaseTemplate -Force | Out-Null
}

# Читаем шаблон
$TemplateContent = Get-Content $TemplatePath -Raw
$TemplateContent = $TemplateContent -replace '\{FEATURE_NAME\}', $Name
$TemplateContent = $TemplateContent -replace '\{feature-name\}', ($Name -replace '([A-Z])', '-$1').ToLower().TrimStart('-')

# Определяем путь для создания
$BasePath = switch ($Type) {
    "feature" { "resources/js/src/features" }
    "entity" { "resources/js/src/entities" }
    "widget" { "resources/js/src/widgets" }
    "page" { "resources/js/Pages" }
    "domain" { "app/Domain" }
}

# Формируем промпт
$Prompt = @"
Ultrathink, вспомни CLAUDE.md и AI_CONTEXT.md

$TemplateContent

## Дополнительный контекст:
- Тип создаваемого элемента: $Type
- Имя: $Name
- Базовый путь: $BasePath

Помни: архитектура $(if($Type -in @("domain")) {"DDD"} else {"FSD"}), TypeScript обязателен

Создай план и начни реализацию.
КРИТИЧЕСКИ ВАЖНО: следуй архитектуре из CLAUDE.md!
"@

# Копируем в буфер обмена
$Prompt | Set-Clipboard

Write-Host "✅ Промпт для создания $Type '$Name' скопирован!" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Инструкция:" -ForegroundColor Yellow
Write-Host "1. Вставьте в Claude" -ForegroundColor White
Write-Host "2. Claude создаст структуру по шаблону" -ForegroundColor White
Write-Host "3. Проверьте с помощью: 'Резюмируй что создано'" -ForegroundColor White