# Agent Action Wrapper - Automatically updates metrics when agents perform actions
param(
    [string]$Agent,
    [string]$Action,
    [string]$Details = ""
)

$basePath = "C:\www.spa.com\.ai-team"
$metricsScript = "$basePath\scripts\metrics-updater.ps1"
$chatPath = "$basePath\chat.md"
$timestamp = Get-Date -Format 'HH:mm'

# Define action mappings
$actionMappings = @{
    # Common actions
    "task_start" = @{
        metric = "message_processed"
        log = "Started working on task"
    }
    "task_complete" = @{
        metric = "task_completed"
        log = "Completed task"
    }
    "message_reply" = @{
        metric = "message_processed"
        log = "Processed message"
    }

    # QA specific
    "bug_found" = @{
        metric = "bug_found"
        log = "Found bug"
    }
    "test_run" = @{
        metric = "test_run"
        log = "Ran tests"
    }
    "test_suite_complete" = @{
        metric = "test_run"
        count = 10
        log = "Completed test suite"
    }

    # DevOps specific
    "deploy_start" = @{
        metric = "message_processed"
        log = "Starting deployment"
    }
    "deploy_complete" = @{
        metric = "deployment"
        log = "Deployment completed"
    }

    # Backend/Frontend specific
    "api_created" = @{
        metric = "task_completed"
        log = "Created API endpoint"
    }
    "component_created" = @{
        metric = "task_completed"
        log = "Created UI component"
    }
}

# Process action
if ($actionMappings.ContainsKey($Action)) {
    $mapping = $actionMappings[$Action]
    $count = if ($mapping.count) { $mapping.count } else { 1 }

    # Update metric
    & $metricsScript -Agent $Agent -Action $mapping.metric -Count $count

    # Log to chat
    $logMessage = "[$timestamp] [$($Agent.ToUpper())]: $($mapping.log)"
    if ($Details) {
        $logMessage += " - $Details"
    }
    Add-Content -Path $chatPath -Value $logMessage -Encoding UTF8

    Write-Host "✅ Action processed: $Action for $Agent" -ForegroundColor Green
    Write-Host "   Metric updated: $($mapping.metric) (+$count)" -ForegroundColor Gray
    Write-Host "   Logged to chat: $logMessage" -ForegroundColor Gray
} else {
    Write-Host "⚠️ Unknown action: $Action" -ForegroundColor Yellow
    Write-Host "Available actions:" -ForegroundColor Cyan
    $actionMappings.Keys | ForEach-Object { Write-Host "  - $_" }
}

# Additional automation: Check for keywords in chat and auto-update metrics
function Watch-ChatForActions {
    param([string]$AgentName)

    $keywords = @{
        "completed" = "task_complete"
        "finished" = "task_complete"
        "done" = "task_complete"
        "created" = "task_complete"
        "fixed" = "task_complete"
        "bug" = "bug_found"
        "error" = "bug_found"
        "tested" = "test_run"
        "deployed" = "deploy_complete"
        "starting" = "task_start"
        "working on" = "task_start"
    }

    # Read last line from chat
    $lastLine = Get-Content $chatPath -Tail 1

    if ($lastLine -match "\[$($AgentName.ToUpper())\]:") {
        foreach ($keyword in $keywords.Keys) {
            if ($lastLine -match $keyword) {
                $action = $keywords[$keyword]
                Write-Host "🔍 Auto-detected action: $action from keyword '$keyword'" -ForegroundColor Cyan

                # Auto-update metric
                if ($actionMappings.ContainsKey($action)) {
                    $mapping = $actionMappings[$action]
                    & $metricsScript -Agent $AgentName -Action $mapping.metric
                    Write-Host "📊 Auto-updated metric: $($mapping.metric)" -ForegroundColor Green
                }
                break
            }
        }
    }
}

# If called with -Watch parameter, monitor chat
if ($Action -eq "watch") {
    Write-Host "👀 Watching chat for $Agent actions..." -ForegroundColor Yellow
    while ($true) {
        Watch-ChatForActions -AgentName $Agent
        Start-Sleep -Seconds 5
    }
}

# Default behavior if no specific action
if (-not $Action -and $Agent) {
    # Just show available actions
    Write-Host "Agent Action Wrapper" -ForegroundColor Cyan
    Write-Host "Agent: $Agent" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Usage:" -ForegroundColor Cyan
    Write-Host "  -Agent <name> -Action <action> [-Details <text>]"
    Write-Host "  -Agent <name> -Action watch     # Monitor chat for auto-metrics"
    Write-Host ""
    Write-Host "Available actions:" -ForegroundColor Yellow
    $actionMappings.Keys | ForEach-Object { Write-Host "  - $_" }
}