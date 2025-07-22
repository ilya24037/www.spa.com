# Настройка планировщика задач для SPA Platform

param(
    [Parameter(Mandatory=$false)]
    [string]$TaskName = "SPAPlatform-Deploy",
    
    [Parameter(Mandatory=$false)]
    [string]$Schedule = "Daily", # Daily, Weekly, Monthly
    
    [Parameter(Mandatory=$false)]
    [string]$Time = "02:00" # Время запуска
)

# Путь к проекту
$projectPath = Split-Path -Parent $PSScriptRoot
$scriptPath = Join-Path $projectPath "scripts\deploy.ps1"

# Создание действия
$action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-ExecutionPolicy Bypass -File `"$scriptPath`" -Environment production"

# Создание триггера
switch ($Schedule) {
    "Daily" {
        $trigger = New-ScheduledTaskTrigger -Daily -At $Time
    }
    "Weekly" {
        $trigger = New-ScheduledTaskTrigger -Weekly -DaysOfWeek Sunday -At $Time
    }
    "Monthly" {
        $trigger = New-ScheduledTaskTrigger -Monthly -DaysOfMonth 1 -At $Time
    }
    default {
        $trigger = New-ScheduledTaskTrigger -Daily -At $Time
    }
}

# Настройки задачи
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

# Создание задачи
try {
    # Удаляем существующую задачу если есть
    $existingTask = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    if ($existingTask) {
        Write-Host "Удаление существующей задачи $TaskName..."
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
    }
    
    # Создаем новую задачу
    Write-Host "Создание задачи $TaskName..."
    Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings -Description "Автоматический деплой SPA Platform"
    
    Write-Host "✅ Задача $TaskName успешно создана"
    Write-Host "📅 Расписание: $Schedule в $Time"
    Write-Host "📝 Команды управления:"
    Write-Host "   • Запуск: Start-ScheduledTask -TaskName $TaskName"
    Write-Host "   • Остановка: Stop-ScheduledTask -TaskName $TaskName"
    Write-Host "   • Статус: Get-ScheduledTask -TaskName $TaskName"
    Write-Host "   • Удаление: Unregister-ScheduledTask -TaskName $TaskName"
    
} catch {
    Write-Error "Ошибка создания задачи: $($_.Exception.Message)"
    exit 1
} 