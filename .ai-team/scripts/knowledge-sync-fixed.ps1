# knowledge-sync-fixed.ps1 - Knowledge synchronization for Virtual Office
# Fixed version without encoding issues

param(
    [string]$Mode = "sync",
    [switch]$Verbose
)

$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot\..

Write-Host "Knowledge Sync System for Virtual Office" -ForegroundColor Cyan
Write-Host ("=" * 60)

# Paths
$DocsPath = "..\docs"
$KnowledgeMapPath = "$DocsPath\KNOWLEDGE_MAP_2025.md"
$LessonsPath = "$DocsPath\LESSONS"
$ProblemsPath = "$DocsPath\PROBLEMS"
$RefactoringPath = "$DocsPath\REFACTORING"

$AgentsPath = "."
$VirtualOfficePath = "virtual-office\knowledge"

# Logging function
function Write-Log {
    param([string]$Message, [string]$Type = "INFO")

    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] [$Type] $Message"

    switch ($Type) {
        "ERROR" { Write-Host $logMessage -ForegroundColor Red }
        "SUCCESS" { Write-Host $logMessage -ForegroundColor Green }
        "WARNING" { Write-Host $logMessage -ForegroundColor Yellow }
        default { Write-Host $logMessage -ForegroundColor White }
    }

    # Save to log
    if (-not (Test-Path "system\logs")) {
        New-Item -ItemType Directory -Path "system\logs" -Force | Out-Null
    }
    Add-Content -Path "system\logs\knowledge-sync.log" -Value $logMessage -ErrorAction SilentlyContinue
}

# Initialize structure
function Initialize-KnowledgeStructure {
    Write-Log "Initializing knowledge structure..." "INFO"

    $directories = @(
        "virtual-office\knowledge",
        "virtual-office\knowledge\lessons",
        "virtual-office\knowledge\problems",
        "virtual-office\knowledge\patterns",
        "virtual-office\knowledge\refactoring",
        "system\logs"
    )

    foreach ($dir in $directories) {
        if (-not (Test-Path $dir)) {
            New-Item -ItemType Directory -Path $dir -Force | Out-Null
            Write-Log "Created directory: $dir" "SUCCESS"
        }
    }
}

# Sync knowledge map
function Sync-KnowledgeMap {
    Write-Log "Syncing knowledge map..." "INFO"

    if (Test-Path $KnowledgeMapPath) {
        $content = Get-Content $KnowledgeMapPath -Raw -Encoding UTF8
        Set-Content -Path "$VirtualOfficePath\KNOWLEDGE_MAP.md" -Value $content -Encoding UTF8

        # Create quick reference
        $quickRef = @"
# Quick Commands from Knowledge Map

## For errors "Cannot perform action"
grep -r "error text" app/Domain/*/Actions/

## For data save issues
1. Check fillable in model
2. Check watcher in component
3. Check emit format

## Search solutions
grep -r "similar problem" docs/LESSONS/
find app/ -name "*similar_feature*"

## Principles
- KISS - simplest solution first
- Business logic first
- Minimal changes
"@
        Set-Content -Path "$VirtualOfficePath\QUICK_REFERENCE.md" -Value $quickRef -Encoding UTF8
        Write-Log "Knowledge map synchronized" "SUCCESS"
    } else {
        Write-Log "Knowledge map not found: $KnowledgeMapPath" "WARNING"
    }
}

# Sync lessons
function Sync-Lessons {
    Write-Log "Syncing lessons..." "INFO"

    if (Test-Path $LessonsPath) {
        $criticalLessons = @(
            "APPROACHES\BUSINESS_LOGIC_FIRST.md",
            "ANTI_PATTERNS\FRONTEND_FIRST_DEBUGGING.md",
            "WORKFLOWS\NEW_TASK_WORKFLOW.md",
            "QUICK_WINS\STATUS_VALIDATION_PATTERNS.md"
        )

        $count = 0
        foreach ($lesson in $criticalLessons) {
            $sourcePath = Join-Path $LessonsPath $lesson
            if (Test-Path $sourcePath) {
                $fileName = Split-Path $lesson -Leaf
                Copy-Item $sourcePath "$VirtualOfficePath\lessons\$fileName" -Force -ErrorAction SilentlyContinue
                $count++
                if ($Verbose) {
                    Write-Log "Copied lesson: $fileName" "SUCCESS"
                }
            }
        }

        # Create summary
        $summary = @"
# Critical Lessons Summary

## Main Principles
1. BUSINESS_LOGIC_FIRST - check backend first for errors
2. Avoid FRONTEND_FIRST anti-pattern
3. KISS - simplest solution is best
4. Watchers are mandatory for data persistence

## Quick Solutions
- Status error: grep > Action > minimal change (5-30 min)
- Data not saving: check fillable and watchers (10 min)
- New feature: find analog > copy pattern (30-60 min)

## What NOT to do
- Do not create new APIs for validation errors
- Do not complicate simple tasks
- Do not ignore existing patterns
"@
        Set-Content -Path "$VirtualOfficePath\lessons\SUMMARY.md" -Value $summary -Encoding UTF8
        Write-Log "Lessons synchronized: $count files" "SUCCESS"
    } else {
        Write-Log "Lessons path not found" "WARNING"
    }
}

# Sync problems
function Sync-Problems {
    Write-Log "Syncing problems..." "INFO"

    if (Test-Path $ProblemsPath) {
        $problems = Get-ChildItem -Path $ProblemsPath -Filter "*.md" -ErrorAction SilentlyContinue | Select-Object -First 20

        $index = "# Problem Index`n`n## Top Issues`n"

        foreach ($problem in $problems) {
            try {
                $firstLine = Get-Content $problem.FullName -First 1 -ErrorAction SilentlyContinue
                if ($firstLine) {
                    $title = $firstLine -replace "^#\s*", ""
                    $index += "- **$($problem.BaseName)**: $title`n"
                }
            } catch {
                # Skip file if cannot read
            }
        }

        Set-Content -Path "$VirtualOfficePath\problems\INDEX.md" -Value $index -Encoding UTF8
        Write-Log "Problem index created: $($problems.Count) entries" "SUCCESS"
    } else {
        Write-Log "Problems path not found" "WARNING"
    }
}

# Update agent configs
function Update-AgentConfigs {
    Write-Log "Updating agent configs..." "INFO"

    $configPath = "system\agents.json"
    if (Test-Path $configPath) {
        try {
            $config = Get-Content $configPath -Raw | ConvertFrom-Json

            # Add knowledge paths
            foreach ($agentName in $config.agents.PSObject.Properties.Name) {
                $agent = $config.agents.$agentName

                if (-not $agent.PSObject.Properties["knowledge_path"]) {
                    $agent | Add-Member -MemberType NoteProperty -Name "knowledge_path" -Value "virtual-office/knowledge" -Force
                }
            }

            $config | ConvertTo-Json -Depth 10 | Set-Content $configPath -Encoding UTF8
            Write-Log "Agent configs updated" "SUCCESS"
        } catch {
            Write-Log "Error updating configs: $_" "ERROR"
        }
    }
}

# Create quick scripts
function Create-QuickScripts {
    Write-Log "Creating quick scripts..." "INFO"

    $quickFixScript = @'
# quick-fix.ps1 - Quick problem search
param([string]$Error)

Write-Host "Searching for solution: $Error" -ForegroundColor Cyan

# Search in lessons
$lessons = Get-ChildItem "virtual-office\knowledge\lessons" -Filter "*.md" -ErrorAction SilentlyContinue
foreach ($lesson in $lessons) {
    $content = Get-Content $lesson.FullName -Raw -ErrorAction SilentlyContinue
    if ($content -match $Error) {
        Write-Host "Found solution in: $($lesson.Name)" -ForegroundColor Green
        Write-Host $content
        break
    }
}

# Search in problems
if (Test-Path "virtual-office\knowledge\problems\INDEX.md") {
    $problems = Get-Content "virtual-office\knowledge\problems\INDEX.md" -Raw -ErrorAction SilentlyContinue
    if ($problems -match $Error) {
        Write-Host "Similar problems found in index" -ForegroundColor Yellow
    }
}
'@

    Set-Content -Path "scripts\quick-fix.ps1" -Value $quickFixScript -Encoding UTF8
    Write-Log "Quick scripts created" "SUCCESS"
}

# Check sync status
function Check-Sync {
    Write-Log "Checking sync status..." "INFO"

    $status = @{
        KnowledgeMap = Test-Path "$VirtualOfficePath\KNOWLEDGE_MAP.md"
        Lessons = Test-Path "$VirtualOfficePath\lessons\SUMMARY.md"
        Problems = Test-Path "$VirtualOfficePath\problems\INDEX.md"
        QuickRef = Test-Path "$VirtualOfficePath\QUICK_REFERENCE.md"
    }

    Write-Host "`nSync Status:" -ForegroundColor Cyan
    foreach ($item in $status.GetEnumerator()) {
        $icon = if ($item.Value) { "[OK]" } else { "[MISSING]" }
        $color = if ($item.Value) { "Green" } else { "Red" }
        Write-Host "$icon $($item.Key)" -ForegroundColor $color
    }

    $synced = ($status.Values | Where-Object { $_ -eq $true }).Count
    $total = $status.Count

    Write-Host "`nSynchronized: $synced/$total" -ForegroundColor $(if ($synced -eq $total) { "Green" } else { "Yellow" })

    return $synced -eq $total
}

# Main logic
try {
    Initialize-KnowledgeStructure

    switch ($Mode) {
        "sync" {
            Write-Host "`nPerforming full sync..." -ForegroundColor Cyan
            Sync-KnowledgeMap
            Sync-Lessons
            Sync-Problems
            Update-AgentConfigs
            Create-QuickScripts

            if (Check-Sync) {
                Write-Log "Sync completed successfully!" "SUCCESS"
            } else {
                Write-Log "Sync completed with warnings" "WARNING"
            }
        }

        "check" {
            Check-Sync
        }

        "update" {
            Write-Host "`nUpdating configs..." -ForegroundColor Cyan
            Update-AgentConfigs
            Write-Log "Configs updated" "SUCCESS"
        }

        default {
            Write-Log "Unknown mode: $Mode" "ERROR"
            exit 1
        }
    }

    Write-Host "`nDone!" -ForegroundColor Green
    Write-Host "Agents now have access to project knowledge base." -ForegroundColor Cyan
} catch {
    Write-Log "Error: $_" "ERROR"
    exit 1
}