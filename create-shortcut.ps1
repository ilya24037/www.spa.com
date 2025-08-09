# Создание ярлыка на рабочем столе для быстрого запуска

$WshShell = New-Object -ComObject WScript.Shell
$Desktop = [System.Environment]::GetFolderPath('Desktop')
$Shortcut = $WshShell.CreateShortcut("$Desktop\🚀 Start SPA Work.lnk")
$Shortcut.TargetPath = "C:\www.spa.com\start-work.bat"
$Shortcut.WorkingDirectory = "C:\www.spa.com"
$Shortcut.IconLocation = "C:\Windows\System32\cmd.exe,0"
$Shortcut.Description = "Запустить весь стек разработки SPA Platform"
$Shortcut.Save()

Write-Host "✅ Ярлык создан на рабочем столе!" -ForegroundColor Green
Write-Host "   Имя: 🚀 Start SPA Work" -ForegroundColor Cyan
Write-Host ""
Write-Host "Теперь просто дважды кликните на ярлык утром!" -ForegroundColor Yellow
pause