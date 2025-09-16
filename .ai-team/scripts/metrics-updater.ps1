# Metrics Updater for Virtual Office
# Updates metrics when agents complete tasks or process messages

param(
    [string]$Agent,
    [string]$Action,  # task_completed, message_processed, bug_found, test_run, deployment
    [int]$Count = 1
)

$metricsPath = "C:\www.spa.com\.ai-team\system\metrics.json"
$statusPath = "C:\www.spa.com\.ai-team\system\status.json"

# Initialize metrics file if not exists
if (!(Test-Path $metricsPath)) {
    $initialMetrics = @{
        agents = @{
            teamlead = @{ tasks_completed = 0; messages_processed = 0; uptime_hours = 0 }
            backend = @{ tasks_completed = 0; messages_processed = 0; uptime_hours = 0 }
            frontend = @{ tasks_completed = 0; messages_processed = 0; uptime_hours = 0 }
            qa = @{ tasks_completed = 0; bugs_found = 0; tests_run = 0; uptime_hours = 0 }
            devops = @{ tasks_completed = 0; deployments = 0; uptime_hours = 0 }
        }
        totals = @{
            tasks_created = 0
            tasks_completed = 0
            messages_sent = 0
            reports_generated = 0
        }
        last_updated = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
    }

    $initialMetrics | ConvertTo-Json -Depth 3 | Out-File $metricsPath -Encoding UTF8
}

# Function to update metrics
function Update-Metrics {
    param(
        [string]$AgentName,
        [string]$MetricAction,
        [int]$Value
    )

    # Load current metrics
    $metrics = Get-Content $metricsPath | ConvertFrom-Json

    # Update specific metric
    switch ($MetricAction) {
        "task_completed" {
            $metrics.agents.$AgentName.tasks_completed += $Value
            $metrics.totals.tasks_completed += $Value
        }
        "message_processed" {
            $metrics.agents.$AgentName.messages_processed += $Value
            $metrics.totals.messages_sent += $Value
        }
        "bug_found" {
            if ($AgentName -eq "qa") {
                $metrics.agents.qa.bugs_found += $Value
            }
        }
        "test_run" {
            if ($AgentName -eq "qa") {
                $metrics.agents.qa.tests_run += $Value
            }
        }
        "deployment" {
            if ($AgentName -eq "devops") {
                $metrics.agents.devops.deployments += $Value
            }
        }
    }

    # Update timestamp
    $metrics.last_updated = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

    # Save metrics
    $metrics | ConvertTo-Json -Depth 3 | Out-File $metricsPath -Encoding UTF8

    Write-Host "📊 Metrics updated: $AgentName - $MetricAction (+$Value)" -ForegroundColor Green
}

# Function to update agent status
function Update-Status {
    param(
        [string]$AgentName
    )

    if (!(Test-Path $statusPath)) {
        $initialStatus = @{
            agents = @{}
        }
        $initialStatus | ConvertTo-Json | Out-File $statusPath -Encoding UTF8
    }

    $status = Get-Content $statusPath | ConvertFrom-Json

    # Create PSObject for proper JSON serialization
    if (-not $status.agents) {
        $status | Add-Member -NotePropertyName agents -NotePropertyValue ([PSCustomObject]@{})
    }

    # Update agent status
    $agentStatus = @{
        status = "active"
        last_seen = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        last_action = $Action
    }

    # Add or update agent in status
    if ($status.agents.PSObject.Properties.Match($AgentName).Count) {
        $status.agents.$AgentName = $agentStatus
    } else {
        $status.agents | Add-Member -NotePropertyName $AgentName -NotePropertyValue $agentStatus
    }

    $status | ConvertTo-Json -Depth 2 | Out-File $statusPath -Encoding UTF8
}

# Main execution
if ($Agent -and $Action) {
    Update-Metrics -AgentName $Agent -MetricAction $Action -Value $Count
    Update-Status -AgentName $Agent
} else {
    Write-Host "Metrics Updater Usage:" -ForegroundColor Cyan
    Write-Host "  -Agent <name>   : Agent name (teamlead, backend, frontend, qa, devops)"
    Write-Host "  -Action <type>  : Action type (task_completed, message_processed, etc.)"
    Write-Host "  -Count <number> : Count to add (default 1)"
    Write-Host ""
    Write-Host "Examples:" -ForegroundColor Yellow
    Write-Host "  .\metrics-updater.ps1 -Agent backend -Action task_completed"
    Write-Host "  .\metrics-updater.ps1 -Agent qa -Action bug_found -Count 3"
    Write-Host "  .\metrics-updater.ps1 -Agent devops -Action deployment"
}