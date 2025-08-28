# Быстрый тест команды chcp

Write-Host "Testing chcp command..." -ForegroundColor Yellow

# Тест 1: Прямой вызов через cmd
Write-Host "`n1. Direct cmd call:" -ForegroundColor Cyan
try {
    $result = cmd /c "chcp"
    Write-Host $result -ForegroundColor Green
} catch {
    Write-Host "Error with direct cmd call" -ForegroundColor Red
}

# Тест 2: Через нашу функцию
Write-Host "`n2. Through PowerShell function:" -ForegroundColor Cyan
try {
    chcp
} catch {
    Write-Host "Error with PowerShell function" -ForegroundColor Red
}

# Тест 3: Установка UTF-8 кодовой страницы
Write-Host "`n3. Setting UTF-8 codepage:" -ForegroundColor Cyan
try {
    chcp 65001
} catch {
    Write-Host "Error setting UTF-8 codepage" -ForegroundColor Red
}

Write-Host "`nTest completed!" -ForegroundColor Magenta