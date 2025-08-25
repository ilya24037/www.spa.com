# Список всех секций для копирования
$sections = @(

    'LocationSection', 
    'WorkFormatSection',
    'ServiceProviderSection',
    'ExperienceSection',
    'DescriptionSection',
    'PriceSection',
    'ParametersSection', 
    'PromoSection',
    'PhotosSection',
    'VideosSection', 
    'GeoSection',
    'ContactsSection',
    'FeaturesSection',
    'ScheduleSection'
)

Write-Host "Начинаю копирование секций в FSD структуру..." -ForegroundColor Cyan

# Копируем каждую секцию
foreach ($section in $sections) {
    $sourcePath = "resources\js\src\entities\ad\ui\AdForm\sections\$section.vue"
    $targetPath = "resources\js\src\features\AdSections\$section\ui\$section.vue"
    
    Write-Host "Копирую $section..." -ForegroundColor Green
    
    if (Test-Path $sourcePath) {
        Copy-Item $sourcePath $targetPath -Force
        Write-Host "✅ $section скопирован" -ForegroundColor Green
    } else {
        Write-Host "❌ $sourcePath не найден" -ForegroundColor Red
    }
}

Write-Host "" 
Write-Host "🎉 Готово! Все секции скопированы в features/AdSections/" -ForegroundColor Cyan
Write-Host "📁 Структура FSD готова для тестирования" -ForegroundColor Yellow