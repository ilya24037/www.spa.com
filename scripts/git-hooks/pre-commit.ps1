# Pre-commit hook (PowerShell)
# Собирает контекст‑паки по модулям, затронутым в коммите, не блокируя коммит

Set-StrictMode -Version Latest
$ErrorActionPreference = 'SilentlyContinue'

function Write-Info($msg) { Write-Host "[pre-commit] $msg" -ForegroundColor Cyan }
function Write-Warn($msg) { Write-Host "[pre-commit] $msg" -ForegroundColor Yellow }

try {
    # Определяем корень репозитория
    $repoRoot = (git rev-parse --show-toplevel 2>$null)
    if (-not $repoRoot) { $repoRoot = (Resolve-Path -LiteralPath .).ProviderPath }
    Set-Location $repoRoot

    # Список заиндексированных файлов
    $staged = git diff --cached --name-only | Where-Object { $_ -and $_.Trim().Length -gt 0 }
    if (($staged | Measure-Object).Count -eq 0) {
        Write-Info "Нет заиндексированных файлов — пропускаю сборку контекста"
        exit 0
    }

    function Get-ModuleForPath([string]$p) {
        $pp = $p -replace '\\','/'
        if ($pp -match '^(routes/|app/Application/Http/Controllers/)') { return 'routes' }
        if ($pp -match '^(app/Domain/Booking|resources/js/Pages/Bookings|resources/js/src/features/booking|resources/js/src/entities/booking)') { return 'booking' }
        if ($pp -match '^(app/Domain/Search|resources/js/src/features/search|resources/js/widgets/header|resources/js/Components/Header)') { return 'search' }
        if ($pp -match '^(app/Domain/Ad|resources/js/src/entities/ad|resources/js/src/features/booking-form)') { return 'ads' }
        if ($pp -match '^(app/Domain/Media|resources/js/src/features/gallery|resources/js/src/entities/master|resources/js/widgets/master-profile)') { return 'media' }
        if ($pp -match '^(app/Domain/Master|resources/js/Pages/Masters|resources/js/src/entities/master|resources/js/src/features/masters-filter)') { return 'masters' }
        return $null
    }

    $modules = @()
    foreach ($f in $staged) {
        $m = Get-ModuleForPath $f
        if ($m) { $modules += $m }
    }
    $modules = $modules | Select-Object -Unique

    if (($modules | Measure-Object).Count -eq 0) {
        Write-Info "Не найдено затронутых модулей — пропускаю"
        exit 0
    }

    $scriptPath = Join-Path $repoRoot 'scripts/ai/create-context-pack.ps1'
    if (-not (Test-Path $scriptPath)) {
        Write-Warn "Не найден $scriptPath — пропускаю"
        exit 0
    }

    foreach ($m in $modules) {
        try {
            Write-Info "Собираю контекст для модуля: $m"
            & $scriptPath -Module $m | Out-Null
        } catch {
            Write-Warn "Ошибка при сборке контекста для '$m': $($_.Exception.Message)"
        }
    }

    Write-Info "Готово. Коммит продолжается."
    exit 0
} catch {
    Write-Warn "Непредвиденная ошибка: $($_.Exception.Message). Коммит не блокирую."
    exit 0
}


