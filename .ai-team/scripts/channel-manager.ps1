# Channel Manager for Virtual Office
# Manages communication channels (general, standup, help)

param(
    [string]$Action = "list",  # post, list, clear, monitor
    [string]$Channel = "general",  # general, standup, help
    [string]$Message = "",
    [string]$From = "SYSTEM"
)

$basePath = "C:\www.spa.com\.ai-team\virtual-office\channels"
$validChannels = @("general", "standup", "help")

# Validate channel
if ($Channel -notin $validChannels) {
    Write-Host "Invalid channel. Use: general, standup, or help" -ForegroundColor Red
    exit
}

$channelPath = Join-Path $basePath $Channel

# Function to post message to channel
function Post-ToChannel {
    param(
        [string]$ChannelName,
        [string]$MessageText,
        [string]$Author
    )

    $timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
    $messageId = "CH-MSG-" + (Get-Random -Maximum 9999)

    $message = @{
        id = $messageId
        channel = $ChannelName
        from = $Author
        message = $MessageText
        timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        type = switch ($ChannelName) {
            "standup" { "status_update" }
            "help" { "help_request" }
            default { "general" }
        }
    }

    $fileName = "$timestamp-$messageId.json"
    $filePath = Join-Path $channelPath $fileName

    $message | ConvertTo-Json | Out-File $filePath -Encoding UTF8

    Write-Host "📤 Message posted to #$ChannelName" -ForegroundColor Green
    return $messageId
}

# Function to list channel messages
function Get-ChannelMessages {
    param(
        [string]$ChannelName,
        [int]$Limit = 20
    )

    Write-Host "`n📢 Channel: #$ChannelName" -ForegroundColor Cyan
    Write-Host "=" * 60

    if (!(Test-Path $channelPath)) {
        Write-Host "Channel is empty" -ForegroundColor Yellow
        return
    }

    $messages = Get-ChildItem -Path $channelPath -Filter "*.json" |
                Sort-Object LastWriteTime -Descending |
                Select-Object -First $Limit

    if ($messages.Count -eq 0) {
        Write-Host "No messages in channel" -ForegroundColor Yellow
        return
    }

    foreach ($msgFile in $messages) {
        $msg = Get-Content $msgFile.FullName | ConvertFrom-Json

        $typeIcon = switch ($msg.type) {
            "status_update" { "📊" }
            "help_request" { "🆘" }
            default { "💬" }
        }

        Write-Host "$typeIcon [$($msg.timestamp)] $($msg.from):"
        Write-Host "   $($msg.message)" -ForegroundColor Gray
        Write-Host ""
    }
}

# Function to clear old messages
function Clear-Channel {
    param(
        [string]$ChannelName,
        [int]$KeepLast = 50
    )

    $messages = Get-ChildItem -Path $channelPath -Filter "*.json" |
                Sort-Object LastWriteTime -Descending

    if ($messages.Count -le $KeepLast) {
        Write-Host "Channel has $($messages.Count) messages, keeping all" -ForegroundColor Yellow
        return
    }

    $toDelete = $messages | Select-Object -Skip $KeepLast
    $deleteCount = $toDelete.Count

    foreach ($file in $toDelete) {
        Remove-Item $file.FullName -Force
    }

    Write-Host "🗑️ Cleared $deleteCount old messages from #$ChannelName" -ForegroundColor Green
}

# Function to monitor all channels
function Monitor-Channels {
    while ($true) {
        Clear-Host
        Write-Host "📡 CHANNEL MONITOR" -ForegroundColor Cyan
        Write-Host "=" * 60
        Write-Host "Time: $(Get-Date -Format 'HH:mm:ss')" -ForegroundColor Gray
        Write-Host ""

        foreach ($ch in $validChannels) {
            $chPath = Join-Path $basePath $ch
            $msgCount = 0
            $lastMessage = "No messages"

            if (Test-Path $chPath) {
                $messages = Get-ChildItem -Path $chPath -Filter "*.json"
                $msgCount = $messages.Count

                if ($msgCount -gt 0) {
                    $latest = $messages | Sort-Object LastWriteTime -Descending | Select-Object -First 1
                    $msg = Get-Content $latest.FullName | ConvertFrom-Json
                    $lastMessage = "$($msg.from): $($msg.message.Substring(0, [Math]::Min(40, $msg.message.Length)))..."
                }
            }

            $icon = switch ($ch) {
                "general" { "💬" }
                "standup" { "📊" }
                "help" { "🆘" }
            }

            Write-Host "$icon #$ch ($msgCount messages)"
            Write-Host "   Last: $lastMessage" -ForegroundColor Gray
            Write-Host ""
        }

        Write-Host "Press Ctrl+C to stop monitoring..." -ForegroundColor Gray
        Start-Sleep -Seconds 3
    }
}

# Function for daily standup
function Start-DailyStandup {
    Write-Host "🎯 STARTING DAILY STANDUP" -ForegroundColor Cyan
    Write-Host "=" * 60

    $agents = @("teamlead", "backend", "frontend", "qa", "devops")

    # Post standup request
    Post-ToChannel -ChannelName "standup" -Author "SYSTEM" -MessageText "Daily standup started. All agents please report status."

    # Request status from each agent
    foreach ($agent in $agents) {
        $prompt = "Please provide standup update: 1) What did you complete? 2) What are you working on? 3) Any blockers?"

        # Send to agent's inbox
        $msgPath = "C:\www.spa.com\.ai-team\virtual-office\inbox\$agent"
        $msgId = "STANDUP-$(Get-Date -Format 'yyyyMMdd')"

        $standupRequest = @{
            id = $msgId
            from = "SYSTEM"
            to = $agent
            message = $prompt
            type = "standup_request"
            timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        }

        $msgFile = Join-Path $msgPath "$msgId.json"
        $standupRequest | ConvertTo-Json | Out-File $msgFile -Encoding UTF8
    }

    Write-Host "✅ Standup requests sent to all agents" -ForegroundColor Green
}

# Main execution
switch ($Action) {
    "post" {
        if ($Message) {
            Post-ToChannel -ChannelName $Channel -MessageText $Message -Author $From
        } else {
            Write-Host "Message is required for posting" -ForegroundColor Red
        }
    }
    "list" {
        Get-ChannelMessages -ChannelName $Channel
    }
    "clear" {
        Clear-Channel -ChannelName $Channel
    }
    "monitor" {
        Monitor-Channels
    }
    "standup" {
        Start-DailyStandup
    }
    default {
        Write-Host "Channel Manager - Available Actions:" -ForegroundColor Cyan
        Write-Host "  post    - Post message to channel"
        Write-Host "  list    - List channel messages"
        Write-Host "  clear   - Clear old messages"
        Write-Host "  monitor - Monitor all channels"
        Write-Host "  standup - Start daily standup"
        Write-Host ""
        Write-Host "Examples:" -ForegroundColor Yellow
        Write-Host "  .\channel-manager.ps1 -Action post -Channel general -Message 'Hello team' -From CEO"
        Write-Host "  .\channel-manager.ps1 -Action list -Channel standup"
        Write-Host "  .\channel-manager.ps1 -Action standup"
    }
}