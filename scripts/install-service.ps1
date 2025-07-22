# Установка Windows службы для SPA Platform
# Требует права администратора

param(
    [Parameter(Mandatory=$false)]
    [string]$ServiceName = "SPAPlatform",
    
    [Parameter(Mandatory=$false)]
    [string]$DisplayName = "SPA Platform Service",
    
    [Parameter(Mandatory=$false)]
    [string]$Description = "Автоматический деплой и управление SPA Platform"
)

# Проверка прав администратора
if (-not ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Error "Требуются права администратора для установки службы"
    exit 1
}

# Путь к проекту
$projectPath = Split-Path -Parent $PSScriptRoot
$scriptPath = Join-Path $projectPath "scripts\deploy.ps1"

# Создание службы
try {
    # Удаляем существующую службу если есть
    $existingService = Get-Service -Name $ServiceName -ErrorAction SilentlyContinue
    if ($existingService) {
        Write-Host "Удаление существующей службы $ServiceName..."
        Stop-Service -Name $ServiceName -Force -ErrorAction SilentlyContinue
        Remove-Service -Name $ServiceName
    }
    
    # Создаем новую службу
    Write-Host "Создание службы $ServiceName..."
    New-Service -Name $ServiceName `
                -DisplayName $DisplayName `
                -Description $Description `
                -StartupType Automatic `
                -BinaryPathName "powershell.exe -ExecutionPolicy Bypass -File `"$scriptPath`" -Environment production"
    
    Write-Host "✅ Служба $ServiceName успешно создана"
    Write-Host "📝 Команды управления:"
    Write-Host "   • Запуск: Start-Service $ServiceName"
    Write-Host "   • Остановка: Stop-Service $ServiceName"
    Write-Host "   • Статус: Get-Service $ServiceName"
    Write-Host "   • Удаление: Remove-Service $ServiceName"
    
} catch {
    Write-Error "Ошибка создания службы: $($_.Exception.Message)"
    exit 1
} 