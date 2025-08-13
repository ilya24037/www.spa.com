@echo off
echo ========================================
echo   Установка Chromium
echo ========================================
echo.

:: Создаем папку для Chromium
echo Создание папки для Chromium...
mkdir "C:\Program Files\Chromium" 2>nul

:: Распаковка архива
echo Распаковка Chromium...
powershell -Command "Expand-Archive -Path '%USERPROFILE%\Downloads\chromium.zip' -DestinationPath 'C:\Program Files\Chromium' -Force"

:: Создание ярлыка на рабочем столе
echo Создание ярлыка на рабочем столе...
powershell -Command "$WshShell = New-Object -comObject WScript.Shell; $Shortcut = $WshShell.CreateShortcut('%USERPROFILE%\Desktop\Chromium.lnk'); $Shortcut.TargetPath = 'C:\Program Files\Chromium\chrome-win\chrome.exe'; $Shortcut.Save()"

:: Добавление в PATH (опционально)
echo Добавление в PATH...
setx PATH "%PATH%;C:\Program Files\Chromium\chrome-win" /M 2>nul

echo.
echo ========================================
echo   Установка завершена!
echo   Ярлык создан на рабочем столе
echo ========================================
pause