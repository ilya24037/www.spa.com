@echo off
:: Запуск PowerShell 7 в папке проекта
:: Если PowerShell 7 не установлен, используется обычный PowerShell

:: Проверяем наличие PowerShell 7
where pwsh >nul 2>&1
if %errorlevel%==0 (
    echo Запускаем PowerShell 7...
    start "SPA Terminal" pwsh -NoExit -Command "cd '%~dp0'; cls; Write-Host 'SPA Platform Terminal' -ForegroundColor Cyan; Write-Host 'Laravel проект: %~dp0' -ForegroundColor Green; Write-Host ''; Write-Host 'Полезные команды:' -ForegroundColor Yellow; Write-Host '  php artisan serve     - запустить сервер'; Write-Host '  php artisan migrate   - выполнить миграции'; Write-Host '  npm run dev          - запустить Vite'; Write-Host '  php artisan tinker   - консоль Laravel'; Write-Host ''"
) else (
    echo PowerShell 7 не найден, запускаем Windows PowerShell...
    start "SPA Terminal" powershell -NoExit -Command "cd '%~dp0'; cls; Write-Host 'SPA Platform Terminal' -ForegroundColor Cyan; Write-Host 'Laravel проект: %~dp0' -ForegroundColor Green; Write-Host ''; Write-Host 'Полезные команды:' -ForegroundColor Yellow; Write-Host '  php artisan serve     - запустить сервер'; Write-Host '  php artisan migrate   - выполнить миграции'; Write-Host '  npm run dev          - запустить Vite'; Write-Host '  php artisan tinker   - консоль Laravel'; Write-Host ''"
) 