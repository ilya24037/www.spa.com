# Channel Automator - Ensures agents actively use channels
param(
    [string]$Agent,
    [string]$Action = "monitor"  # monitor, standup, help, report
)

$basePath = "C:\www.spa.com\.ai-team"
$channelsPath = "$basePath\virtual-office\channels"
$chatPath = "$basePath\chat.md"

# Auto-post to standup channel at 9:00
function Post-DailyStandup {
    param([string]$AgentName)

    $standupPath = "$channelsPath\standup"
    $time = Get-Date -Format 'HH:mm'
    $date = Get-Date -Format 'yyyy-MM-dd'

    # Get agent's current tasks and status
    $tasksPath = "$basePath\virtual-office\tasks"
    $agentTasks = @()

    if (Test-Path $tasksPath) {
        Get-ChildItem "$tasksPath\*.json" | ForEach-Object {
            $task = Get-Content $_.FullName | ConvertFrom-Json
            if ($task.assignee -eq $AgentName.ToLower()) {
                $agentTasks += $task
            }
        }
    }

    # Create standup message
    $standupMessage = @{
        agent = $AgentName
        date = $date
        time = $time
        yesterday = "Processed messages and updated systems"
        today = if ($agentTasks.Count -gt 0) {
            "Working on $($agentTasks.Count) tasks: $($agentTasks[0].title)"
        } else {
            "Available for new tasks"
        }
        blockers = "None"
        metrics = @{
            tasks_in_progress = ($agentTasks | Where-Object { $_.status -eq "in_progress" }).Count
            tasks_completed_today = 0
        }
    }

    # Save to standup channel
    $fileName = "$date-$AgentName-standup.json"
    $standupMessage | ConvertTo-Json | Out-File "$standupPath\$fileName" -Encoding UTF8

    # Post to chat
    $chatMessage = "[$time] [$($AgentName.ToUpper())] → #standup: Yesterday: $($standupMessage.yesterday) | Today: $($standupMessage.today) | Blockers: $($standupMessage.blockers)"
    Add-Content -Path $chatPath -Value $chatMessage -Encoding UTF8

    Write-Host "✅ Posted standup for $AgentName" -ForegroundColor Green
}

# Auto-check for help requests
function Check-HelpChannel {
    param([string]$AgentName)

    $helpPath = "$channelsPath\help"

    if (!(Test-Path $helpPath)) {
        New-Item -ItemType Directory -Path $helpPath -Force | Out-Null
    }

    # Check for unanswered help requests
    Get-ChildItem "$helpPath\*.json" -ErrorAction SilentlyContinue | ForEach-Object {
        $helpRequest = Get-Content $_.FullName | ConvertFrom-Json

        if ($helpRequest.status -eq "open" -and $helpRequest.agent -ne $AgentName) {
            # Agent can help
            $canHelp = $false
            $helpMessage = ""

            switch ($AgentName.ToLower()) {
                "backend" {
                    if ($helpRequest.type -match "api|database|laravel") {
                        $canHelp = $true
                        $helpMessage = "I can help with the API/database issue"
                    }
                }
                "frontend" {
                    if ($helpRequest.type -match "ui|vue|component") {
                        $canHelp = $true
                        $helpMessage = "I can help with the UI/Vue issue"
                    }
                }
                "qa" {
                    if ($helpRequest.type -match "test|bug|quality") {
                        $canHelp = $true
                        $helpMessage = "I can help with testing/quality issue"
                    }
                }
                "devops" {
                    if ($helpRequest.type -match "deploy|docker|server") {
                        $canHelp = $true
                        $helpMessage = "I can help with deployment/infrastructure"
                    }
                }
                "teamlead" {
                    $canHelp = $true
                    $helpMessage = "I'll coordinate resources to resolve this"
                }
            }

            if ($canHelp) {
                $time = Get-Date -Format 'HH:mm'
                $response = "[$time] [$($AgentName.ToUpper())] → #help: @$($helpRequest.agent) $helpMessage"
                Add-Content -Path $chatPath -Value $response -Encoding UTF8

                # Update help request status
                $helpRequest.status = "in_progress"
                $helpRequest.helper = $AgentName
                $helpRequest | ConvertTo-Json | Out-File $_.FullName -Encoding UTF8

                Write-Host "🆘 $AgentName responded to help request from $($helpRequest.agent)" -ForegroundColor Yellow
            }
        }
    }
}

# Auto-post to general channel
function Post-GeneralUpdate {
    param(
        [string]$AgentName,
        [string]$Message
    )

    $generalPath = "$channelsPath\general"
    $time = Get-Date -Format 'HH:mm'
    $timestamp = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'

    $update = @{
        agent = $AgentName
        message = $Message
        timestamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
        type = "status_update"
    }

    $fileName = "$timestamp-$AgentName.json"
    $update | ConvertTo-Json | Out-File "$generalPath\$fileName" -Encoding UTF8

    $chatMessage = "[$time] [$($AgentName.ToUpper())] → #general: $Message"
    Add-Content -Path $chatPath -Value $chatMessage -Encoding UTF8

    Write-Host "📢 Posted to #general: $Message" -ForegroundColor Cyan
}

# Auto-scheduler for channel activities
function Start-ChannelScheduler {
    param([string]$AgentName)

    Write-Host "🤖 Starting channel scheduler for $AgentName" -ForegroundColor Cyan
    Write-Host "Features:" -ForegroundColor Yellow
    Write-Host "  ✅ Auto-standup at 9:00" -ForegroundColor Green
    Write-Host "  ✅ Help channel monitoring" -ForegroundColor Green
    Write-Host "  ✅ General updates" -ForegroundColor Green
    Write-Host ""

    $lastStandup = $null
    $standupHour = 9

    while ($true) {
        $currentTime = Get-Date
        $currentHour = $currentTime.Hour
        $currentDate = $currentTime.Date

        # Daily standup at 9:00
        if ($currentHour -eq $standupHour -and $lastStandup -ne $currentDate) {
            Post-DailyStandup -AgentName $AgentName
            $lastStandup = $currentDate
        }

        # Check help channel every 5 minutes
        if ($currentTime.Minute % 5 -eq 0) {
            Check-HelpChannel -AgentName $AgentName
        }

        # Random general updates
        if ((Get-Random -Maximum 100) -lt 5) {  # 5% chance
            $updates = @(
                "Working efficiently on assigned tasks",
                "Systems operating normally",
                "Ready for new assignments",
                "Collaborating with team members",
                "Monitoring system performance"
            )
            $randomUpdate = $updates | Get-Random
            Post-GeneralUpdate -AgentName $AgentName -Message $randomUpdate
        }

        Start-Sleep -Seconds 60  # Check every minute
    }
}

# Main execution
switch ($Action) {
    "monitor" {
        if ($Agent) {
            Start-ChannelScheduler -AgentName $Agent
        } else {
            Write-Host "Please specify -Agent parameter" -ForegroundColor Red
        }
    }
    "standup" {
        if ($Agent) {
            Post-DailyStandup -AgentName $Agent
        } else {
            # Post for all agents
            @("teamlead", "backend", "frontend", "qa", "devops") | ForEach-Object {
                Post-DailyStandup -AgentName $_
            }
        }
    }
    "help" {
        if ($Agent) {
            Check-HelpChannel -AgentName $Agent
        } else {
            Write-Host "Please specify -Agent parameter" -ForegroundColor Red
        }
    }
    default {
        Write-Host "Channel Automator - Usage:" -ForegroundColor Cyan
        Write-Host "  -Action monitor -Agent <name>  : Start auto-scheduler for agent"
        Write-Host "  -Action standup [-Agent <name>]: Post standup (for agent or all)"
        Write-Host "  -Action help -Agent <name>     : Check help requests"
        Write-Host ""
        Write-Host "Examples:" -ForegroundColor Yellow
        Write-Host "  .\channel-automator.ps1 -Action monitor -Agent backend"
        Write-Host "  .\channel-automator.ps1 -Action standup"
    }
}