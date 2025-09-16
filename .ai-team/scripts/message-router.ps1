# Message Router for Virtual Office
# Routes messages between agents via inbox/outbox system

param(
    [string]$Action = "monitor",  # monitor, send, check
    [string]$From = "",
    [string]$To = "",
    [string]$Message = "",
    [string]$Priority = "normal"  # low, normal, high, critical
)

$basePath = "C:\www.spa.com\.ai-team\virtual-office"
$chatPath = "C:\www.spa.com\.ai-team\chat.md"

# Function to send message
function Send-AgentMessage {
    param(
        [string]$FromAgent,
        [string]$ToAgent,
        [string]$MessageText,
        [string]$Priority = "normal"
    )

    $timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
    $messageId = "MSG-" + (Get-Random -Maximum 9999)

    $messageObj = @{
        id = $messageId
        from = $FromAgent
        to = $ToAgent
        message = $MessageText
        priority = $Priority
        timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        status = "unread"
    }

    # Save to recipient's inbox
    $inboxPath = Join-Path $basePath "inbox\$ToAgent"
    $fileName = "$timestamp-$messageId.json"
    $filePath = Join-Path $inboxPath $fileName

    $messageObj | ConvertTo-Json | Out-File $filePath -Encoding UTF8

    # Save copy to sender's outbox
    $outboxPath = Join-Path $basePath "outbox\$FromAgent"
    $outFilePath = Join-Path $outboxPath $fileName
    $messageObj.status = "sent"
    $messageObj | ConvertTo-Json | Out-File $outFilePath -Encoding UTF8

    Write-Host "Message $messageId sent from $FromAgent to $ToAgent" -ForegroundColor Green
    return $messageId
}

# Function to check inbox
function Check-Inbox {
    param(
        [string]$Agent
    )

    $inboxPath = Join-Path $basePath "inbox\$Agent"

    if (!(Test-Path $inboxPath)) {
        Write-Host "Inbox not found for $Agent" -ForegroundColor Red
        return
    }

    $messages = Get-ChildItem -Path $inboxPath -Filter "*.json" | Sort-Object LastWriteTime -Descending

    if ($messages.Count -eq 0) {
        Write-Host "No messages in $Agent's inbox" -ForegroundColor Yellow
        return
    }

    Write-Host "`n📬 Inbox for $Agent ($($messages.Count) messages):" -ForegroundColor Cyan
    Write-Host "=" * 50

    foreach ($msgFile in $messages | Select-Object -First 10) {
        $msg = Get-Content $msgFile.FullName | ConvertFrom-Json

        $statusIcon = if ($msg.status -eq "unread") { "🔵" } else { "⚪" }
        $priorityIcon = switch ($msg.priority) {
            "critical" { "🔴" }
            "high" { "🟠" }
            "normal" { "🟢" }
            "low" { "⚪" }
        }

        Write-Host "$statusIcon $priorityIcon [$($msg.timestamp)] From: $($msg.from)"
        Write-Host "   $($msg.message.Substring(0, [Math]::Min(80, $msg.message.Length)))..."
        Write-Host ""
    }
}

# Function to monitor all inboxes
function Monitor-Inboxes {
    $agents = @("teamlead", "backend", "frontend", "qa", "devops")

    while ($true) {
        Clear-Host
        Write-Host "📮 VIRTUAL OFFICE MESSAGE MONITOR" -ForegroundColor Cyan
        Write-Host "=" * 50
        Write-Host "Time: $(Get-Date -Format 'HH:mm:ss')" -ForegroundColor Gray
        Write-Host ""

        foreach ($agent in $agents) {
            $inboxPath = Join-Path $basePath "inbox\$agent"
            $unreadCount = 0

            if (Test-Path $inboxPath) {
                $messages = Get-ChildItem -Path $inboxPath -Filter "*.json"
                foreach ($msgFile in $messages) {
                    $msg = Get-Content $msgFile.FullName | ConvertFrom-Json
                    if ($msg.status -eq "unread") {
                        $unreadCount++
                    }
                }
            }

            $statusColor = if ($unreadCount -gt 0) { "Yellow" } else { "Green" }
            Write-Host "$agent : " -NoNewline
            Write-Host "$unreadCount unread" -ForegroundColor $statusColor
        }

        Write-Host "`nPress Ctrl+C to stop monitoring..." -ForegroundColor Gray
        Start-Sleep -Seconds 5
    }
}

# Function to mark message as read
function Mark-AsRead {
    param(
        [string]$Agent,
        [string]$MessageId
    )

    $inboxPath = Join-Path $basePath "inbox\$Agent"
    $messages = Get-ChildItem -Path $inboxPath -Filter "*$MessageId*.json"

    if ($messages.Count -eq 0) {
        Write-Host "Message $MessageId not found" -ForegroundColor Red
        return
    }

    foreach ($msgFile in $messages) {
        $msg = Get-Content $msgFile.FullName | ConvertFrom-Json
        $msg.status = "read"
        $msg | ConvertTo-Json | Out-File $msgFile.FullName -Encoding UTF8
        Write-Host "Message $MessageId marked as read" -ForegroundColor Green
    }
}

# Main execution
switch ($Action) {
    "send" {
        if ($From -and $To -and $Message) {
            Send-AgentMessage -FromAgent $From -ToAgent $To -MessageText $Message -Priority $Priority
        } else {
            Write-Host "Usage: -Action send -From <agent> -To <agent> -Message <text> [-Priority <level>]" -ForegroundColor Red
        }
    }
    "check" {
        if ($From) {
            Check-Inbox -Agent $From
        } else {
            Write-Host "Usage: -Action check -From <agent>" -ForegroundColor Red
        }
    }
    "monitor" {
        Monitor-Inboxes
    }
    "read" {
        if ($From -and $Message) {
            Mark-AsRead -Agent $From -MessageId $Message
        } else {
            Write-Host "Usage: -Action read -From <agent> -Message <messageId>" -ForegroundColor Red
        }
    }
    default {
        Write-Host "Available actions: send, check, monitor, read" -ForegroundColor Yellow
        Write-Host "Examples:" -ForegroundColor Cyan
        Write-Host "  .\message-router.ps1 -Action send -From ceo -To backend -Message 'Create new API' -Priority high"
        Write-Host "  .\message-router.ps1 -Action check -From backend"
        Write-Host "  .\message-router.ps1 -Action monitor"
        Write-Host "  .\message-router.ps1 -Action read -From backend -Message MSG-1234"
    }
}