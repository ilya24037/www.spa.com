# Create Chrome for Testing shortcut
$WshShell = New-Object -comObject WScript.Shell
$DesktopPath = [Environment]::GetFolderPath("Desktop")
$ShortcutPath = Join-Path $DesktopPath "Chrome for Testing.lnk"
$Shortcut = $WshShell.CreateShortcut($ShortcutPath)
$Shortcut.TargetPath = "C:\Users\user1\.cache\puppeteer\chrome\win64-139.0.7258.66\chrome-win64\chrome.exe"
$Shortcut.WorkingDirectory = "C:\Users\user1\.cache\puppeteer\chrome\win64-139.0.7258.66\chrome-win64"
$Shortcut.IconLocation = "C:\Users\user1\.cache\puppeteer\chrome\win64-139.0.7258.66\chrome-win64\chrome.exe"
$Shortcut.Description = "Chrome for Testing - version 139.0.7258.66"
$Shortcut.Save()

Write-Host "Shortcut created on desktop!" -ForegroundColor Green