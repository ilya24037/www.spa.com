# Integration Bridge between old chat.md and new Virtual Office system
# Syncs messages and tasks between both systems

param(
    [string]$Mode = "sync"  # sync, migrate, monitor
)

$chatPath = "C:\www.spa.com\.ai-team\chat.md"
$virtualOfficePath = "C:\www.spa.com\.ai-team\virtual-office"

# Function to parse chat.md for mentions and convert to inbox messages
function Sync-ChatToInbox {
    if (!(Test-Path $chatPath)) {
        Write-Host "Chat file not found" -ForegroundColor Red
        return
    }

    $chatContent = Get-Content $chatPath -Tail 100 -Encoding UTF8
    $agents = @("teamlead", "backend", "frontend", "qa", "devops")

    foreach ($line in $chatContent) {
        # Check for @mentions
        foreach ($agent in $agents) {
            if ($line -match "@$agent") {
                # Extract message details
                $timePattern = '\[(\d{2}:\d{2})\]'
                $fromPattern = '\[([A-Z]+)\]:'

                $time = if ($line -match $timePattern) { $matches[1] } else { "00:00" }
                $from = if ($line -match $fromPattern) { $matches[1] } else { "UNKNOWN" }

                # Extract message text (after the second ]:)
                $messageText = $line -replace '^\[[^\]]+\]\s*\[[^\]]+\]:\s*', ''

                # Check if message already exists in inbox
                $inboxPath = Join-Path $virtualOfficePath "inbox\$agent"
                $messageHash = [System.BitConverter]::ToString(
                    [System.Security.Cryptography.MD5]::Create().ComputeHash(
                        [System.Text.Encoding]::UTF8.GetBytes($messageText)
                    )
                ).Replace("-", "").Substring(0, 8)

                $existingMsg = Get-ChildItem -Path $inboxPath -Filter "*$messageHash*.json" -ErrorAction SilentlyContinue

                if (!$existingMsg) {
                    # Create new inbox message
                    $msgId = "SYNC-$messageHash"
                    $message = @{
                        id = $msgId
                        from = $from.ToLower()
                        to = $agent
                        message = $messageText
                        timestamp = (Get-Date).ToString("yyyy-MM-dd") + " " + $time + ":00"
                        status = "unread"
                        source = "chat.md"
                    }

                    $msgFile = Join-Path $inboxPath "$msgId.json"
                    $message | ConvertTo-Json | Out-File $msgFile -Encoding UTF8

                    Write-Host "📥 Synced message to $agent inbox" -ForegroundColor Green
                }
            }
        }
    }
}

# Function to sync Virtual Office messages back to chat.md
function Sync-InboxToChat {
    $agents = @("teamlead", "backend", "frontend", "qa", "devops")

    foreach ($agent in $agents) {
        $outboxPath = Join-Path $virtualOfficePath "outbox\$agent"

        if (Test-Path $outboxPath) {
            $messages = Get-ChildItem -Path $outboxPath -Filter "*.json" |
                       Where-Object { $_.LastWriteTime -gt (Get-Date).AddMinutes(-5) }

            foreach ($msgFile in $messages) {
                $msg = Get-Content $msgFile.FullName | ConvertFrom-Json

                # Check if already in chat
                $chatContent = Get-Content $chatPath -Tail 50 -Encoding UTF8
                $msgExists = $chatContent | Where-Object { $_ -match [regex]::Escape($msg.message) }

                if (!$msgExists) {
                    # Add to chat
                    $time = (Get-Date $msg.timestamp).ToString("HH:mm")
                    $chatLine = "[$time] [$($agent.ToUpper())]: $($msg.message)"
                    Add-Content -Path $chatPath -Value $chatLine -Encoding UTF8

                    Write-Host "📤 Synced $agent message to chat.md" -ForegroundColor Green
                }
            }
        }
    }
}

# Function to monitor and sync continuously
function Start-SyncMonitor {
    Write-Host "🔄 INTEGRATION BRIDGE MONITOR" -ForegroundColor Cyan
    Write-Host "Syncing chat.md ↔ Virtual Office" -ForegroundColor Gray
    Write-Host "Press Ctrl+C to stop..." -ForegroundColor Gray
    Write-Host ""

    $syncCount = 0

    while ($true) {
        $syncCount++

        # Sync from chat to inbox
        Sync-ChatToInbox

        # Sync from outbox to chat
        Sync-InboxToChat

        # Update status
        $statusFile = Join-Path $virtualOfficePath "..\system\bridge-status.json"
        $status = @{
            last_sync = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
            sync_count = $syncCount
            mode = "bidirectional"
            active = $true
        }
        $status | ConvertTo-Json | Out-File $statusFile -Encoding UTF8

        # Display status every 10 syncs
        if ($syncCount % 10 -eq 0) {
            Write-Host "[$((Get-Date).ToString('HH:mm:ss'))] Sync cycle $syncCount completed" -ForegroundColor Gray
        }

        Start-Sleep -Seconds 10
    }
}

# Function to migrate all historical data
function Start-Migration {
    Write-Host "🚀 MIGRATING TO VIRTUAL OFFICE" -ForegroundColor Cyan
    Write-Host "=" * 50

    # Parse entire chat history
    if (Test-Path $chatPath) {
        $chatContent = Get-Content $chatPath -Encoding UTF8
        Write-Host "Found $($chatContent.Count) lines in chat.md" -ForegroundColor Gray

        # Extract tasks from chat
        $taskPattern = 'task|TODO|TASK|задач'
        $tasks = $chatContent | Where-Object { $_ -match $taskPattern }

        Write-Host "Found $($tasks.Count) potential tasks" -ForegroundColor Gray

        foreach ($taskLine in $tasks) {
            # Try to extract task details
            if ($taskLine -match '@(\w+).*?([A-Za-zА-Яа-я\s]+)') {
                $assignee = $matches[1]
                $title = $matches[2].Trim()

                if ($title.Length -gt 10) {
                    # Create task in Virtual Office
                    & "$PSScriptRoot\task-manager.ps1" -Action create `
                        -Title $title `
                        -Description "Migrated from chat.md" `
                        -Assignee $assignee `
                        -Priority "normal"

                    Write-Host "✅ Migrated task: $title → $assignee" -ForegroundColor Green
                }
            }
        }
    }

    Write-Host "`nMigration complete!" -ForegroundColor Green
}

# Main execution
switch ($Mode) {
    "sync" {
        Write-Host "Performing one-time sync..." -ForegroundColor Cyan
        Sync-ChatToInbox
        Sync-InboxToChat
        Write-Host "✅ Sync complete" -ForegroundColor Green
    }
    "monitor" {
        Start-SyncMonitor
    }
    "migrate" {
        Start-Migration
    }
    default {
        Write-Host "Integration Bridge - Modes:" -ForegroundColor Cyan
        Write-Host "  sync    - One-time sync between systems"
        Write-Host "  monitor - Continuous monitoring and sync"
        Write-Host "  migrate - Migrate historical data to Virtual Office"
        Write-Host ""
        Write-Host "Examples:" -ForegroundColor Yellow
        Write-Host "  .\integration-bridge.ps1 -Mode sync"
        Write-Host "  .\integration-bridge.ps1 -Mode monitor"
        Write-Host "  .\integration-bridge.ps1 -Mode migrate"
    }
}