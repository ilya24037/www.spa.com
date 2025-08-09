# Requires -Version 5.1
<#
.SYNOPSIS
  Собирает "контекст‑пак" по модулю: релевантные файлы + краткие выдержки и сигнатуры, сохраняет в Markdown.

.DESCRIPTION
  - Поддерживает пресеты модулей (-Module) и явные пути (-Paths)
  - Исключает большие/вспомогательные директории (node_modules, vendor, public/build, storage, bootstrap/cache)
  - Для каждого файла добавляет:
      * Заголовок с относительным путём
      * Первые строки (по умолчанию 20)
      * Извлечённые сигнатуры (class/function/export/defineStore/defineProps/Route::...)
  - По умолчанию сохраняет в storage/ai-sessions/<yyyy-mm-dd>/<module>-<HHmmss>.md

.PARAMETER Module
  Имя пресета: routes | booking | search | ads | media | masters

.PARAMETER Paths
  Явный список путей/папок для включения (перекрывает Module)

.PARAMETER Out
  Путь к выходному .md. Если не указан — используется путь по умолчанию.

.EXAMPLE
  .\scripts\ai\create-context-pack.ps1 -Module routes

.EXAMPLE
  .\scripts\ai\create-context-pack.ps1 -Paths routes, app/Domain/Booking -Out storage/ai-sessions/2025-08-08/custom.md
#>

[CmdletBinding()]
param(
    [Parameter(Position=0)]
    [ValidateSet('routes','booking','search','ads','media','masters')]
    [string]$Module,

    [Parameter(Position=1)]
    [string[]]$Paths,

    [Parameter(Position=2)]
    [string]$Out
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Resolve-RootPath([string]$p) {
    if ([string]::IsNullOrWhiteSpace($p)) { return $null }
    $np = (Resolve-Path -LiteralPath $p -ErrorAction SilentlyContinue)
    if ($np) { return $np.ProviderPath }
    # попробовать относительный от корня скрипта
    $base = Split-Path -Parent $PSCommandPath
    $try = Join-Path (Split-Path -Parent $base) $p
    $np2 = (Resolve-Path -LiteralPath $try -ErrorAction SilentlyContinue)
    if ($np2) { return $np2.ProviderPath }
    return $null
}

# Пресеты модулей → корневые папки для сканирования
$presets = @{ 
    routes  = @('routes', 'app/Application/Http/Controllers');
    booking = @('app/Domain/Booking', 'resources/js/Pages/Bookings', 'resources/js/src/features/booking', 'resources/js/src/entities/booking');
    search  = @('app/Domain/Search', 'resources/js/src/features/search', 'resources/js/widgets/header', 'resources/js/Components/Header');
    ads     = @('app/Domain/Ad', 'resources/js/Pages', 'resources/js/src/entities/ad', 'resources/js/src/features/booking-form');
    media   = @('app/Domain/Media', 'resources/js/src/features/gallery', 'resources/js/src/entities/master', 'resources/js/widgets/master-profile');
    masters = @('app/Domain/Master', 'resources/js/Pages/Masters', 'resources/js/src/entities/master', 'resources/js/src/features/masters-filter');
}

$includePatterns = @('*.php','*.vue','*.ts','*.js')
$excludeDirParts = @('node_modules','vendor','public\\build','public/build','storage','bootstrap\\cache','bootstrap/cache','.git')

function Should-ExcludePath([string]$path) {
    foreach ($part in $excludeDirParts) {
        if ($path -like "*${part}*") { return $true }
    }
    return $false
}

function Get-RelPath([string]$full) {
    $root = (Resolve-Path -LiteralPath .).ProviderPath
    $fullResolved = (Resolve-Path -LiteralPath $full).ProviderPath
    $rel = $fullResolved.Replace($root, '')
    $trimBackslash = [char]92  # '\\'
    $trimSlash = [char]47      # '/'
    return $rel.TrimStart($trimBackslash).TrimStart($trimSlash)
}

function Get-LangTag([string]$file) {
    switch -Regex ($file) {
        '\\.php$' { return 'php' }
        '\\.vue$' { return 'vue' }
        '\\.ts$'  { return 'ts' }
        '\\.js$'  { return 'js' }
        default     { return '' }
    }
}

function Extract-Signatures([string]$file) {
    $patterns = @(
        '^\s*(class|interface|trait)\s+\w+',
        '^\s*(public|protected|private|static)\s+function\s+\w+\s*\(',
        '^\s*function\s+\w+\s*\(',
        '^\s*export\s+(const|function|class)\s+\w+',
        'defineStore\(',
        'defineProps\(',
        'defineEmits\(',
        'Route::\w+\('
    )
    $lines = @()
    foreach ($pat in $patterns) {
        $matches = Select-String -Path $file -Pattern $pat -SimpleMatch:$false -CaseSensitive:$false -Encoding UTF8 -ErrorAction SilentlyContinue
        if ($matches) {
            $lines += $matches | ForEach-Object { $_.Line.Trim() }
        }
    }
    $uniq = $lines | Where-Object { $_ -and $_.Length -gt 0 } | Select-Object -Unique | Select-Object -First 40
    return $uniq
}

function Read-Head([string]$file, [int]$count = 20) {
    try {
        return Get-Content -LiteralPath $file -TotalCount $count -Encoding UTF8
    } catch {
        return @()
    }
}

# Определяем входные корни
$roots = @()
if ($Paths -and $Paths.Count -gt 0) {
    $roots = $Paths
} elseif ($Module -and $presets.ContainsKey($Module)) {
    $roots = $presets[$Module]
} else {
    Write-Host "[info] Ни Module, ни Paths не заданы. Использую корень проекта." -ForegroundColor Yellow
    $roots = @('.')
}

$resolvedRoots = @()
foreach ($r in $roots) {
    $rp = Resolve-RootPath -p $r
    if ($rp) { $resolvedRoots += $rp } else { Write-Host "[warn] Путь не найден: $r" -ForegroundColor Yellow }
}
if ($resolvedRoots.Count -eq 0) { throw "Нет валидных путей для сканирования." }

# Готовим выходной файл
if (-not $Out -or [string]::IsNullOrWhiteSpace($Out)) {
    $date = Get-Date -Format 'yyyy-MM-dd'
    $time = Get-Date -Format 'HHmmss'
    $outDir = Join-Path 'storage/ai-sessions' $date
    if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir | Out-Null }
    $name = if ($Module) { "$Module-$time.md" } else { "context-$time.md" }
    $Out = Join-Path $outDir $name
} else {
    $parent = Split-Path -Parent $Out
    if ($parent -and -not (Test-Path $parent)) { New-Item -ItemType Directory -Path $parent | Out-Null }
}

"# Context Pack" | Out-File -FilePath $Out -Encoding UTF8
"" | Out-File -FilePath $Out -Append -Encoding UTF8
"- Generated: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" | Out-File -FilePath $Out -Append -Encoding UTF8
"- Module: ${Module}" | Out-File -FilePath $Out -Append -Encoding UTF8
"- Roots: $($roots -join ', ')" | Out-File -FilePath $Out -Append -Encoding UTF8
"" | Out-File -FilePath $Out -Append -Encoding UTF8

$allFiles = @()
foreach ($root in $resolvedRoots) {
    foreach ($pat in $includePatterns) {
        $files = Get-ChildItem -LiteralPath $root -Recurse -File -Filter $pat -ErrorAction SilentlyContinue |
            Where-Object { -not (Should-ExcludePath $_.FullName) }
        if ($files) { $allFiles += $files }
    }
}

$allFiles = $allFiles | Sort-Object FullName -Unique

if ($allFiles.Count -eq 0) {
    Write-Host "[info] Файлов не найдено по заданным критериям." -ForegroundColor Yellow
    "> No files found." | Out-File -FilePath $Out -Append -Encoding UTF8
    Write-Host "Saved to: $Out"
    exit 0
}

foreach ($f in $allFiles) {
    $rel = Get-RelPath -full $f.FullName
    "## $rel" | Out-File -FilePath $Out -Append -Encoding UTF8

    $lang = Get-LangTag -file $f.FullName
    $head = Read-Head -file $f.FullName -count 20
    if ($null -ne $head -and ($head | Measure-Object).Count -gt 0) {
        ('```' + $lang) | Out-File -FilePath $Out -Append -Encoding UTF8
        ($head -join "`n") | Out-File -FilePath $Out -Append -Encoding UTF8
        '```' | Out-File -FilePath $Out -Append -Encoding UTF8
    }

    $sigs = Extract-Signatures -file $f.FullName
    if ($null -ne $sigs -and ($sigs | Measure-Object).Count -gt 0) {
        '**Signatures:**' | Out-File -FilePath $Out -Append -Encoding UTF8
        '```' | Out-File -FilePath $Out -Append -Encoding UTF8
        ($sigs -join "`n") | Out-File -FilePath $Out -Append -Encoding UTF8
        '```' | Out-File -FilePath $Out -Append -Encoding UTF8
    }

    "" | Out-File -FilePath $Out -Append -Encoding UTF8
}

Write-Host ('Saved to: ' + $Out) -ForegroundColor Green


