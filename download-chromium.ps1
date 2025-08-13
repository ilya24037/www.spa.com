# Скрипт для скачивания последней версии Chromium
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Скачивание Chromium для Windows" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Получаем последнюю версию
Write-Host "Получение информации о последней версии..." -ForegroundColor Yellow
$lastChangeUrl = "https://www.googleapis.com/download/storage/v1/b/chromium-browser-snapshots/o/Win_x64%2FLAST_CHANGE?alt=media"
$version = Invoke-RestMethod -Uri $lastChangeUrl

Write-Host "Последняя версия: $version" -ForegroundColor Green
Write-Host ""

# Формируем URL для скачивания
$downloadUrl = "https://www.googleapis.com/download/storage/v1/b/chromium-browser-snapshots/o/Win_x64%2F$version%2Fchrome-win.zip?alt=media"
$outputPath = "$env:USERPROFILE\Downloads\chromium-latest.zip"

# Скачиваем
Write-Host "Скачивание Chromium..." -ForegroundColor Yellow
Write-Host "URL: $downloadUrl" -ForegroundColor Gray
Write-Host "Сохранение в: $outputPath" -ForegroundColor Gray
Write-Host ""

try {
    $ProgressPreference = 'SilentlyContinue'
    Invoke-WebRequest -Uri $downloadUrl -OutFile $outputPath -UseBasicParsing
    Write-Host "✓ Скачивание завершено!" -ForegroundColor Green
    Write-Host ""
    
    # Проверяем размер файла
    $fileInfo = Get-Item $outputPath
    $sizeMB = [math]::Round($fileInfo.Length / 1MB, 2)
    Write-Host "Размер файла: $sizeMB MB" -ForegroundColor Cyan
    
    # Распаковка
    Write-Host ""
    Write-Host "Распаковка архива..." -ForegroundColor Yellow
    $extractPath = "$env:USERPROFILE\Downloads\Chromium"
    
    # Удаляем старую папку если есть
    if (Test-Path $extractPath) {
        Remove-Item -Path $extractPath -Recurse -Force
    }
    
    # Распаковываем
    Expand-Archive -Path $outputPath -DestinationPath $extractPath -Force
    Write-Host "✓ Распаковка завершена!" -ForegroundColor Green
    Write-Host ""
    
    # Создаем ярлык на рабочем столе
    Write-Host "Создание ярлыка..." -ForegroundColor Yellow
    $WshShell = New-Object -comObject WScript.Shell
    $Shortcut = $WshShell.CreateShortcut("$env:USERPROFILE\Desktop\Chromium.lnk")
    $Shortcut.TargetPath = "$extractPath\chrome-win\chrome.exe"
    $Shortcut.WorkingDirectory = "$extractPath\chrome-win"
    $Shortcut.IconLocation = "$extractPath\chrome-win\chrome.exe"
    $Shortcut.Save()
    Write-Host "✓ Ярлык создан на рабочем столе!" -ForegroundColor Green
    
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "  Установка завершена успешно!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Chromium находится в: $extractPath\chrome-win" -ForegroundColor White
    Write-Host "Запустить можно через ярлык на рабочем столе" -ForegroundColor White
    
} catch {
    Write-Host "✗ Ошибка при скачивании: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "Нажмите любую клавишу для выхода..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")