# PowerShell Installation Script
# Установка PowerShell разными методами для SPA Platform

param(
    [Parameter(Mandatory=$true)]
    [ValidateSet("winget", "msi", "zip")]
    [string]$Method,
    
    [Parameter(Mandatory=$false)]
    [string]$Version = "7.5.2",
    
    [Parameter(Mandatory=$false)]
    [switch]$Force
)

# Цвета для вывода
$Colors = @{
    Success = "Green"
    Warning = "Yellow"
    Error = "Red"
    Info = "Cyan"
}

function Write-ColorOutput {
    param(
        [string]$Message,
        [string]$Color = "White"
    )
    Write-Host $Message -ForegroundColor $Colors[$Color]
}

function Test-Administrator {
    $currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Install-PowerShellWinGet {
    Write-ColorOutput "📦 Установка PowerShell через WinGet..." "Info"
    
    try {
        # Проверяем наличие WinGet
        $wingetVersion = winget --version 2>$null
        if (-not $wingetVersion) {
            Write-ColorOutput "❌ WinGet не установлен. Установите Microsoft Store или App Installer" "Error"
            return $false
        }
        
        Write-ColorOutput "Найден WinGet: $wingetVersion" "Success"
        
        # Устанавливаем PowerShell
        Write-ColorOutput "Установка PowerShell..." "Info"
        winget install --id Microsoft.PowerShell --source winget --accept-package-agreements --accept-source-agreements
        
        if ($LASTEXITCODE -eq 0) {
            Write-ColorOutput "✅ PowerShell успешно установлен через WinGet" "Success"
            return $true
        } else {
            Write-ColorOutput "❌ Ошибка установки через WinGet" "Error"
            return $false
        }
    } catch {
        Write-ColorOutput "❌ Ошибка: $($_.Exception.Message)" "Error"
        return $false
    }
}

function Install-PowerShellMSI {
    Write-ColorOutput "📦 Установка PowerShell через MSI..." "Info"
    
    try {
        # Проверяем права администратора
        if (-not (Test-Administrator)) {
            Write-ColorOutput "❌ Требуются права администратора для установки MSI" "Error"
            return $false
        }
        
        # URL для скачивания
        $downloadUrl = "https://github.com/PowerShell/PowerShell/releases/download/v$Version/PowerShell-$Version-win-x64.msi"
        $downloadPath = "$env:TEMP\PowerShell-$Version-win-x64.msi"
        
        Write-ColorOutput "Скачивание MSI файла..." "Info"
        Invoke-WebRequest -Uri $downloadUrl -OutFile $downloadPath
        
        if (Test-Path $downloadPath) {
            Write-ColorOutput "MSI файл скачан: $downloadPath" "Success"
            
            # Установка
            Write-ColorOutput "Установка PowerShell..." "Info"
            $installArgs = @(
                "/i", $downloadPath,
                "/quiet",
                "/norestart",
                "ADD_EXPLORER_CONTEXT_MENU_OPENPOWERSHELL=1",
                "ADD_FILE_CONTEXT_MENU_RUNPOWERSHELL=1",
                "ENABLE_PSREMOTING=1",
                "REGISTER_MANIFEST=1"
            )
            
            Start-Process -FilePath "msiexec.exe" -ArgumentList $installArgs -Wait
            
            # Очистка
            Remove-Item $downloadPath -Force
            
            Write-ColorOutput "✅ PowerShell успешно установлен через MSI" "Success"
            return $true
        } else {
            Write-ColorOutput "❌ Ошибка скачивания MSI файла" "Error"
            return $false
        }
    } catch {
        Write-ColorOutput "❌ Ошибка: $($_.Exception.Message)" "Error"
        return $false
    }
}

function Install-PowerShellZIP {
    Write-ColorOutput "📦 Установка PowerShell через ZIP..." "Info"
    
    try {
        # URL для скачивания
        $downloadUrl = "https://github.com/PowerShell/PowerShell/releases/download/v$Version/PowerShell-$Version-win-x64.zip"
        $downloadPath = "$env:TEMP\PowerShell-$Version-win-x64.zip"
        $extractPath = "C:\PowerShell\$Version"
        
        Write-ColorOutput "Скачивание ZIP файла..." "Info"
        Invoke-WebRequest -Uri $downloadUrl -OutFile $downloadPath
        
        if (Test-Path $downloadPath) {
            Write-ColorOutput "ZIP файл скачан: $downloadPath" "Success"
            
            # Создаем папку
            New-Item -ItemType Directory -Force -Path $extractPath | Out-Null
            
            # Распаковка
            Write-ColorOutput "Распаковка в $extractPath..." "Info"
            Expand-Archive -Path $downloadPath -DestinationPath $extractPath -Force
            
            # Очистка
            Remove-Item $downloadPath -Force
            
            # Добавление в PATH (временно)
            $env:PATH += ";$extractPath"
            
            # Создание ярлыка на рабочем столе
            $desktopPath = [Environment]::GetFolderPath("Desktop")
            $shortcutPath = "$desktopPath\PowerShell $Version.lnk"
            
            $WshShell = New-Object -comObject WScript.Shell
            $Shortcut = $WshShell.CreateShortcut($shortcutPath)
            $Shortcut.TargetPath = "$extractPath\pwsh.exe"
            $Shortcut.WorkingDirectory = $extractPath
            $Shortcut.Description = "PowerShell $Version"
            $Shortcut.Save()
            
            Write-ColorOutput "✅ PowerShell успешно установлен через ZIP" "Success"
            Write-ColorOutput "📁 Расположение: $extractPath" "Info"
            Write-ColorOutput "🔗 Ярлык создан на рабочем столе" "Info"
            Write-ColorOutput "⚠️ Добавьте $extractPath в переменную PATH для постоянного доступа" "Warning"
            
            return $true
        } else {
            Write-ColorOutput "❌ Ошибка скачивания ZIP файла" "Error"
            return $false
        }
    } catch {
        Write-ColorOutput "❌ Ошибка: $($_.Exception.Message)" "Error"
        return $false
    }
}

function Test-PowerShellInstallation {
    Write-ColorOutput "🔍 Проверка установки PowerShell..." "Info"
    
    try {
        $version = pwsh --version 2>$null
        if ($version) {
            Write-ColorOutput "✅ PowerShell установлен: $version" "Success"
            return $true
        }
    } catch {
        # Пробуем найти в разных местах
        $possiblePaths = @(
            "${env:ProgramFiles}\PowerShell\7\pwsh.exe",
            "C:\PowerShell\$Version\pwsh.exe",
            "${env:ProgramFiles}\PowerShell\7.5\pwsh.exe"
        )
        
        foreach ($path in $possiblePaths) {
            if (Test-Path $path) {
                $version = & $path --version
                Write-ColorOutput "✅ PowerShell найден: $version" "Success"
                Write-ColorOutput "📁 Расположение: $path" "Info"
                return $true
            }
        }
    }
    
    Write-ColorOutput "❌ PowerShell не найден" "Error"
    return $false
}

function Show-InstallationSummary {
    Write-ColorOutput "`n📊 Сводка установки:" "Info"
    Write-ColorOutput "   • Метод: $Method" "Info"
    Write-ColorOutput "   • Версия: $Version" "Info"
    Write-ColorOutput "   • Статус: $(if(Test-PowerShellInstallation){'Успешно'}else{'Ошибка'})" "Info"
    
    Write-ColorOutput "`n📝 Следующие шаги:" "Info"
    Write-ColorOutput "   • Перезапустите терминал" "Info"
    Write-ColorOutput "   • Проверьте: pwsh --version" "Info"
    Write-ColorOutput "   • Запустите деплой: .\scripts\deploy.ps1" "Info"
}

# Основная логика
Write-ColorOutput "🚀 Установка PowerShell для SPA Platform" "Info"
Write-ColorOutput "Метод: $Method" "Info"
Write-ColorOutput "Версия: $Version" "Info"

# Проверяем существующую установку
if (Test-PowerShellInstallation) {
    if ($Force) {
        Write-ColorOutput "⚠️ PowerShell уже установлен. Принудительная переустановка..." "Warning"
    } else {
        Write-ColorOutput "✅ PowerShell уже установлен" "Success"
        Show-InstallationSummary
        exit 0
    }
}

# Установка в зависимости от метода
$success = $false

switch ($Method) {
    "winget" {
        $success = Install-PowerShellWinGet
    }
    "msi" {
        $success = Install-PowerShellMSI
    }
    "zip" {
        $success = Install-PowerShellZIP
    }
}

if ($success) {
    Write-ColorOutput "`n🎉 Установка завершена успешно!" "Success"
    Show-InstallationSummary
} else {
    Write-ColorOutput "`n❌ Установка завершена с ошибками" "Error"
    exit 1
} 