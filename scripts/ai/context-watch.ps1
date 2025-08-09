# Requires -Version 5.1
<#
.SYNOPSIS
  Фоновый наблюдатель за файлами. Автоматически собирает «контекст‑паки» по модулю при изменениях в проекте.

.USAGE
  Запускать 1 раз в начале работы (см. start-context-watch.bat). Остановить — закрыть окно PowerShell.

.NOTES
  - Сборка выполняется пакетно каждые 30 секунд по накопленным изменениям
  - Исключены тяжёлые директории (vendor, node_modules, public/build, storage, bootstrap/cache)
  - Ничего не блокирует, в случае ошибки просто пишет предупреждение
#>

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# Исправляем кодировку вывода для Windows PowerShell
try { [Console]::OutputEncoding = [System.Text.Encoding]::UTF8 } catch { }

function Write-Info([string]$msg) { Write-Host ("[watch] " + $msg) -ForegroundColor Cyan }
function Write-Warn([string]$msg) { Write-Host ("[watch] " + $msg) -ForegroundColor Yellow }

# Корень репозитория
$repoRoot = (Resolve-Path -LiteralPath .).ProviderPath
Set-Location $repoRoot

# Карта: путь → модуль
function Get-ModuleForPath([string]$p) {
    $pp = $p -replace '\\','/'
    if ($pp -match '^(routes/|app/Application/Http/Controllers/)') { return 'routes' }
    if ($pp -match '^(app/Domain/Booking|resources/js/Pages/Bookings|resources/js/src/features/booking|resources/js/src/entities/booking)') { return 'booking' }
    if ($pp -match '^(app/Domain/Search|resources/js/src/features/search|resources/js/widgets/header|resources/js/Components/Header)') { return 'search' }
    if ($pp -match '^(app/Domain/Ad|resources/js/Pages|resources/js/src/entities/ad|resources/js/src/features/booking-form)') { return 'ads' }
    if ($pp -match '^(app/Domain/Media|resources/js/src/features/gallery|resources/js/src/entities/master|resources/js/widgets/master-profile)') { return 'media' }
    if ($pp -match '^(app/Domain/Master|resources/js/Pages/Masters|resources/js/src/entities/master|resources/js/src/features/masters-filter)') { return 'masters' }
    return $null
}

# Пути, за которыми наблюдаем (только релевантные корни)
$watchRoots = @(
  'routes',
  'app/Application/Http/Controllers',
  'app/Domain/Booking',
  'app/Domain/Search',
  'app/Domain/Ad',
  'app/Domain/Media',
  'app/Domain/Master',
  'resources/js/Pages',
  'resources/js/src/features',
  'resources/js/src/entities',
  'resources/js/widgets/header',
  'resources/js/Components/Header'
)

# Исключения по частям пути
$excludeParts = @('node_modules','vendor','public/build','storage','bootstrap/cache','.git')
function Test-ShouldExclude([string]$path) {
    foreach ($part in $excludeParts) { if ($path -like "*${part}*") { return $true } }
    return $false
}

# Накопитель модулей с меткой времени последнего изменения
$changed = [hashtable]::Synchronized(@{})

$script:handlers = @()
$script:watchers = @()

function Add-Watcher([string]$root) {
    $full = Join-Path $repoRoot $root
    if (-not (Test-Path $full)) { return }
    $fsw = New-Object System.IO.FileSystemWatcher
    $fsw.Path = $full
    $fsw.Filter = '*.*'
    $fsw.IncludeSubdirectories = $true
    $fsw.EnableRaisingEvents = $true

    $action = {
        param($source, $e)
        try {
            $p = $e.FullPath
            if ([string]::IsNullOrWhiteSpace($p) -or (Test-ShouldExclude $p)) { return }
            $rel = (Resolve-Path -LiteralPath $p -ErrorAction SilentlyContinue)
            if (-not $rel) { return }
            $relPath = $rel.ProviderPath.Replace($repoRoot, '').TrimStart([char]92).TrimStart([char]47)
            $m = Get-ModuleForPath $relPath
            if ($m) {
                $changed[$m] = Get-Date
                Write-Info ("Change: ${m} <- " + $relPath)
            }
        } catch { }
    }

    $evts = @()
    $evts += Register-ObjectEvent -InputObject $fsw -EventName Changed -Action $action
    $evts += Register-ObjectEvent -InputObject $fsw -EventName Created -Action $action
    $evts += Register-ObjectEvent -InputObject $fsw -EventName Deleted -Action $action
    $evts += Register-ObjectEvent -InputObject $fsw -EventName Renamed -Action $action

    $script:watchers += $fsw
    $script:handlers += $evts
}

foreach ($r in $watchRoots) { Add-Watcher $r }

# Пакетный таймер: каждые 30с собираем контекст по накопленным модулям
$timer = New-Object System.Timers.Timer 30000
$timer.AutoReset = $true
$timer.Enabled = $true

Register-ObjectEvent -InputObject $timer -EventName Elapsed -Action {
    try {
        $keys = @($changed.Keys)
        if (($keys | Measure-Object).Count -eq 0) { return }
        $changed.Clear()
        $scriptPath = Join-Path $repoRoot 'scripts/ai/create-context-pack.ps1'
        foreach ($m in $keys) {
            try {
                Write-Info ("Build context pack for: ${m}")
                & $scriptPath -Module $m | Out-Null
            } catch { Write-Warn ("Build error for ${m}: " + $_.Exception.Message) }
        }
    } catch { }
} | Out-Null

Write-Host ""; Write-Info "Watcher started. Close this window to stop."
while ($true) { Start-Sleep -Seconds 1 }


