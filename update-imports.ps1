# Путь к проекту
$projectPath = "D:\www.spa.com\resources\js"

# Таблица соответствия старых и новых путей
$importMappings = @{
    # UI компоненты - Кнопки
    "@/Components/PrimaryButton.vue" = "@/Components/UI/Buttons/PrimaryButton.vue"
    "@/Components/SecondaryButton.vue" = "@/Components/UI/Buttons/SecondaryButton.vue"
    "@/Components/DangerButton.vue" = "@/Components/UI/Buttons/DangerButton.vue"
    
    # UI компоненты - Формы
    "@/Components/TextInput.vue" = "@/Components/UI/Forms/TextInput.vue"
    "@/Components/InputLabel.vue" = "@/Components/UI/Forms/InputLabel.vue"
    "@/Components/InputError.vue" = "@/Components/UI/Forms/InputError.vue"
    "@/Components/Checkbox.vue" = "@/Components/UI/Forms/Checkbox.vue"
    
    # UI компоненты - Навигация
    "@/Components/NavLink.vue" = "@/Components/UI/Navigation/NavLink.vue"
    "@/Components/ResponsiveNavLink.vue" = "@/Components/UI/Navigation/ResponsiveNavLink.vue"
    "@/Components/Dropdown.vue" = "@/Components/UI/Navigation/Dropdown.vue"
    "@/Components/DropdownLink.vue" = "@/Components/UI/Navigation/DropdownLink.vue"
    
    # UI компоненты - Модальное окно
    "@/Components/Modal.vue" = "@/Components/UI/Modal.vue"
    
    # Common компоненты
    "@/Components/ApplicationLogo.vue" = "@/Components/Common/ApplicationLogo.vue"
    "@/Components/StarRating.vue" = "@/Components/Common/StarRating.vue"
    
    # Layout компоненты
    "@/Components/SidebarColumn.vue" = "@/Components/Layout/SidebarColumn.vue"
    "@/Components/Cards.vue" = "@/Components/Layout/CardsList.vue"
    
    # Map компоненты
    "@/Components/Map.vue" = "@/Components/Map/Map.vue"
}

# Функция для обновления импортов в файле
function Update-ImportsInFile {
    param (
        [string]$filePath
    )
    
    # Читаем содержимое файла
    $content = Get-Content -Path $filePath -Raw -Encoding UTF8
    $originalContent = $content
    $updated = $false
    
    # Обновляем каждый импорт
    foreach ($oldImport in $importMappings.Keys) {
        $newImport = $importMappings[$oldImport]
        
        # Проверяем различные форматы импорта
        $patterns = @(
            "import .* from '$oldImport'",
            "import .* from `"$oldImport`"",
            "from '$oldImport'",
            "from `"$oldImport`""
        )
        
        foreach ($pattern in $patterns) {
            if ($content -match $pattern) {
                $content = $content -replace [regex]::Escape($oldImport), $newImport
                $updated = $true
                Write-Host "  Обновлен импорт: $oldImport -> $newImport" -ForegroundColor Green
            }
        }
    }
    
    # Сохраняем файл, если были изменения
    if ($updated) {
        Set-Content -Path $filePath -Value $content -Encoding UTF8 -NoNewline
        Write-Host "Файл обновлен: $filePath" -ForegroundColor Yellow
    }
    
    return $updated
}

# Главная функция
Write-Host "Начинаем обновление импортов..." -ForegroundColor Cyan
Write-Host ""

# Счетчики
$totalFiles = 0
$updatedFiles = 0

# Находим все .vue и .js файлы
$files = Get-ChildItem -Path $projectPath -Recurse -Include "*.vue", "*.js" | 
         Where-Object { $_.FullName -notmatch "node_modules" }

foreach ($file in $files) {
    $totalFiles++
    Write-Host "Проверяем: $($file.Name)" -ForegroundColor DarkGray
    
    if (Update-ImportsInFile -filePath $file.FullName) {
        $updatedFiles++
        Write-Host ""
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Обновление завершено!" -ForegroundColor Green
Write-Host "Статистика:" -ForegroundColor Yellow
Write-Host "  - Проверено файлов: $totalFiles"
Write-Host "  - Обновлено файлов: $updatedFiles"
Write-Host "========================================" -ForegroundColor Cyan