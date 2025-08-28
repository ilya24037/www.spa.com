# Скрипт для окончательного исправления кодировки в терминале Cursor

# Принудительно устанавливаем кодовую страницу UTF-8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
[Console]::InputEncoding = [System.Text.Encoding]::UTF8

# Устанавливаем кодовую страницу системы
try {
    cmd /c "chcp 65001" 2>$null | Out-Null
    Write-Host "UTF-8 codepage set successfully" -ForegroundColor Green
} catch {
    Write-Host "Warning: Could not set codepage" -ForegroundColor Yellow
}

# Проверяем текущую кодовую страницу
$currentCP = cmd /c "chcp" 2>$null
Write-Host "Current codepage: $currentCP" -ForegroundColor Cyan

# Тестируем команды
Write-Host "`nTesting commands:" -ForegroundColor Yellow

Write-Host "npm test:" -ForegroundColor White
try {
    $npmVersion = npm --version 2>$null
    if ($npmVersion) {
        Write-Host "  npm version: $npmVersion" -ForegroundColor Green
    } else {
        Write-Host "  npm: command not found or error" -ForegroundColor Red
    }
} catch {
    Write-Host "  npm: error occurred" -ForegroundColor Red
}

Write-Host "`ncomposer test:" -ForegroundColor White
try {
    $composerInfo = composer --version 2>$null | Select-Object -First 1
    if ($composerInfo) {
        Write-Host "  composer works fine" -ForegroundColor Green
    } else {
        Write-Host "  composer: command not found or error" -ForegroundColor Red
    }
} catch {
    Write-Host "  composer: error occurred" -ForegroundColor Red
}

Write-Host "`nLaravel aliases test:" -ForegroundColor White
try {
    $artisanVersion = php artisan --version 2>$null | Select-Object -First 1
    if ($artisanVersion) {
        Write-Host "  $artisanVersion" -ForegroundColor Green
        Write-Host "  Laravel aliases: art, tinker, serve, migrate" -ForegroundColor Green
    } else {
        Write-Host "  Laravel not found in current directory" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  Laravel artisan: error occurred" -ForegroundColor Red
}

Write-Host "`n=== ENCODING FIX COMPLETE ===" -ForegroundColor Magenta