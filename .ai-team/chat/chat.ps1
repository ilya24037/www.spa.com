# AI Team Chat - PowerShell Terminal Interface
# Интерактивный чат для координации работы Claude и Cursor AI

$Global:ChatFile = "$PSScriptRoot\agent-chat.md"
$Global:CurrentUser = "User"
$Global:Messages = @()

# Colors
function Write-Claude { param($Text) Write-Host $Text -ForegroundColor Magenta }
function Write-Cursor { param($Text) Write-Host $Text -ForegroundColor Red }
function Write-User { param($Text) Write-Host $Text -ForegroundColor Cyan }
function Write-System { param($Text) Write-Host $Text -ForegroundColor Yellow }
function Write-Success { param($Text) Write-Host $Text -ForegroundColor Green }

function Show-Header {
    Clear-Host
    Write-Host "============================================================" -ForegroundColor Yellow
    Write-Host "       🚀 AI TEAM CHAT - SPA Platform Development" -ForegroundColor Yellow -NoNewline
    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Yellow
    Write-Host "Commands: /help, /clear, /status, /tasks, /switch, /exit" -ForegroundColor DarkGray
    Write-Host "------------------------------------------------------------"
    Write-Host ""
}

function Show-Status {
    Write-Success "📊 Project Status:"
    Write-Host "  • MVP Progress: " -NoNewline
    Write-Host "86%" -ForegroundColor Green
    Write-Host "  • Booking System: " -NoNewline
    Write-Host "60%" -ForegroundColor Yellow
    Write-Host "  • Search System: " -NoNewline
    Write-Host "0%" -ForegroundColor Red
    Write-Host "  • Active Agent: " -NoNewline
    Write-Host $Global:CurrentUser -ForegroundColor Cyan
}

function Show-Tasks {
    Write-Success "📋 Current Tasks:"
    Write-Host "  ✅ " -NoNewline
    Write-Success "Синхронизация документации - Completed"
    Write-Host "  🔄 " -NoNewline
    Write-System "Система бронирования - In Progress (60%)"
    Write-Host "  ⏳ " -NoNewline
    Write-Host "Поиск мастеров - Pending (0%)" -ForegroundColor DarkGray
    Write-Host "  ⏳ " -NoNewline
    Write-Host "Система отзывов - Pending" -ForegroundColor DarkGray
}

function Show-Help {
    Write-System "📖 Help:"
    Write-Host "  /help    - Show this help"
    Write-Host "  /clear   - Clear screen"
    Write-Host "  /status  - Show project status"
    Write-Host "  /tasks   - Show current tasks"
    Write-Host "  /switch  - Switch between User/Claude/Cursor"
    Write-Host "  /reload  - Reload messages from file"
    Write-Host "  /exit    - Exit chat"
    Write-Host "  @Claude  - Mention Claude in message"
    Write-Host "  @Cursor  - Mention Cursor in message"
}

function Switch-User {
    $users = @("User", "Claude", "Cursor")
    $currentIndex = $users.IndexOf($Global:CurrentUser)
    $Global:CurrentUser = $users[($currentIndex + 1) % 3]

    $icon = switch($Global:CurrentUser) {
        "User" { "👤" }
        "Claude" { "🤖" }
        "Cursor" { "⚡" }
    }

    Write-Host ""
    Write-Host "$icon Switched to: " -NoNewline

    switch($Global:CurrentUser) {
        "User" { Write-User $Global:CurrentUser }
        "Claude" { Write-Claude $Global:CurrentUser }
        "Cursor" { Write-Cursor $Global:CurrentUser }
    }
    Write-Host ""
}

function Save-Message {
    param($Author, $Message, $Status = "В процессе")

    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm"
    $entry = @"

### $timestamp [$Author] Сообщение из терминала
**Статус**: $Status
**Сообщение**: $Message
---
"@

    Add-Content -Path $Global:ChatFile -Value $entry -Encoding UTF8
}

function Load-Messages {
    if (Test-Path $Global:ChatFile) {
        $content = Get-Content $Global:ChatFile -Raw -Encoding UTF8
        # Simple parsing - in production would be more sophisticated
        Write-Host "Messages loaded from agent-chat.md" -ForegroundColor DarkGray
    }
}

function Show-RecentMessages {
    Write-System "📝 Recent activity:"
    Write-Host ""

    # Show sample messages
    Write-Claude "🤖 [Claude] 10:00 - Created communication file"
    Write-Host "   Создал общий файл коммуникации для синхронизации" -ForegroundColor DarkGray
    Write-Host ""

    Write-Cursor "⚡ [Cursor] 10:15 - Configured auto-reading"
    Write-Host "   Настроил автоматическое чтение правил" -ForegroundColor DarkGray
    Write-Host ""
}

function Start-Chat {
    Show-Header
    Load-Messages
    Show-RecentMessages

    Write-Host ""
    Write-System "Type your message or /help for commands:"
    Write-Host ""

    while ($true) {
        $icon = switch($Global:CurrentUser) {
            "User" { "👤" }
            "Claude" { "🤖" }
            "Cursor" { "⚡" }
        }

        # Create prompt
        $prompt = "$icon $($Global:CurrentUser)> "
        switch($Global:CurrentUser) {
            "User" { Write-Host $prompt -ForegroundColor Cyan -NoNewline }
            "Claude" { Write-Host $prompt -ForegroundColor Magenta -NoNewline }
            "Cursor" { Write-Host $prompt -ForegroundColor Red -NoNewline }
        }

        $input = Read-Host

        if ([string]::IsNullOrWhiteSpace($input)) { continue }

        # Process commands
        if ($input.StartsWith("/")) {
            switch ($input.ToLower()) {
                "/exit" {
                    Write-Success "Goodbye! 👋"
                    return
                }
                "/clear" { Show-Header; Show-RecentMessages }
                "/status" { Show-Status }
                "/tasks" { Show-Tasks }
                "/help" { Show-Help }
                "/switch" { Switch-User }
                "/reload" {
                    Load-Messages
                    Write-Success "Messages reloaded!"
                }
                default {
                    Write-Host "Unknown command. Type /help for available commands." -ForegroundColor DarkGray
                }
            }
            continue
        }

        # Process mentions
        if ($input -like "*@Claude*") {
            Write-Claude "🤖 Claude: Analyzing request..."
        }
        if ($input -like "*@Cursor*") {
            Write-Cursor "⚡ Cursor: Processing..."
        }

        # Save message
        Save-Message -Author $Global:CurrentUser -Message $input
        Write-Success "✓ Message saved to agent-chat.md"
        Write-Host ""
    }
}

# Start the chat
Start-Chat