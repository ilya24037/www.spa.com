# AI Team Launcher PowerShell Script
# Установка кодировки UTF-8
chcp 65001 | Out-Null
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "    AI TEAM LAUNCHER - SPA PLATFORM" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Cyan

# Функция для запуска AI помощника
function Start-AIAssistant {
    param(
        [string]$Title,
        [string]$Role,
        [string]$Folder,
        [string]$Color,
        [string]$Mention
    )
    
    $script = @"
chcp 65001 | Out-Null
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
cd 'C:\www.spa.com\.ai-team\$Folder'
Write-Host '$Role STARTING...' -ForegroundColor $Color
claude --dangerously-skip-permissions --mode normal 'You are $Role for SPA Platform. Read CLAUDE.md in current folder for your instructions. Monitor ../chat.md file every 30 seconds. Execute tasks with @$Mention or @all mentions. Write results back to chat. Start with greeting in chat.'
"@
    
    Start-Process powershell -ArgumentList "-NoExit", "-Command", $script -WindowStyle Normal
}

# Запуск Backend Developer
Write-Host "Starting Backend Developer..." -ForegroundColor Green
Start-AIAssistant -Title "AI Backend Developer" -Role "Backend Developer" -Folder "backend" -Color "Green" -Mention "backend"
Start-Sleep -Seconds 2

# Запуск Frontend Developer
Write-Host "Starting Frontend Developer..." -ForegroundColor Cyan
Start-AIAssistant -Title "AI Frontend Developer" -Role "Frontend Developer" -Folder "frontend" -Color "Cyan" -Mention "frontend"
Start-Sleep -Seconds 2

# Запуск DevOps Engineer
Write-Host "Starting DevOps Engineer..." -ForegroundColor Yellow
Start-AIAssistant -Title "AI DevOps Engineer" -Role "DevOps Engineer" -Folder "devops" -Color "Yellow" -Mention "devops"
Start-Sleep -Seconds 2

# Запуск Chat Monitor
Write-Host "Starting Chat Monitor..." -ForegroundColor Magenta
$chatMonitor = @"
chcp 65001 | Out-Null
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
cd 'C:\www.spa.com'
Write-Host 'МОНИТОР ЧАТА АКТИВЕН' -ForegroundColor Magenta
Write-Host '===================='
Write-Host ''
Get-Content '.ai-team\chat.md' -Wait -Tail 20 -Encoding UTF8
"@
Start-Process powershell -ArgumentList "-NoExit", "-Command", $chatMonitor -WindowStyle Normal
Start-Sleep -Seconds 2

# Запуск Control Center
Write-Host "Starting Control Center..." -ForegroundColor White
$controlCenter = @"
chcp 65001 | Out-Null
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
cd 'C:\www.spa.com'
Clear-Host

Write-Host ''
Write-Host '  ====================================' -ForegroundColor Cyan
Write-Host '     ЦЕНТР УПРАВЛЕНИЯ AI КОМАНДОЙ' -ForegroundColor Green
Write-Host '  ====================================' -ForegroundColor Cyan
Write-Host ''
Write-Host '  Доступные команды:' -ForegroundColor Yellow
Write-Host '  ------------------' -ForegroundColor Yellow
Write-Host '  vsem "сообщение"      - Отправить всей команде' -ForegroundColor White
Write-Host '  back "сообщение"      - Отправить Backend' -ForegroundColor White
Write-Host '  front "сообщение"     - Отправить Frontend' -ForegroundColor White
Write-Host '  devops "сообщение"    - Отправить DevOps' -ForegroundColor White
Write-Host '  komande "b,f" "текст" - Отправить группе' -ForegroundColor White
Write-Host '  status                - Показать статусы задач' -ForegroundColor White
Write-Host '  chat                  - Показать последние сообщения' -ForegroundColor White
Write-Host '  ochistit              - Очистить чат' -ForegroundColor White
Write-Host '  pomosh / help         - Показать справку' -ForegroundColor White
Write-Host ''
Write-Host '  Английские: msg-all, msg-back, msg-front, msg-dev' -ForegroundColor Gray
Write-Host '  Примеры:' -ForegroundColor Gray
Write-Host '  vsem "создать систему отзывов"' -ForegroundColor DarkGray
Write-Host '  back "создать модель User"' -ForegroundColor DarkGray
Write-Host '  komande "f,d" "подготовить деплой"' -ForegroundColor DarkGray
Write-Host ''
Write-Host '  Команда запускается...' -ForegroundColor Green
Write-Host ''

# Функции для управления
function msg-all { 
    param([string]`$msg) 
    `$time = Get-Date -Format 'HH:mm'
    "[`$time] [PM]: @all `$msg" | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8 -Append
    Write-Host "Сообщение отправлено ВСЕМ: `$msg" -ForegroundColor Green 
}

function msg-back { 
    param([string]`$msg) 
    `$time = Get-Date -Format 'HH:mm'
    "[`$time] [PM]: @backend `$msg" | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8 -Append
    Write-Host "Сообщение отправлено BACKEND: `$msg" -ForegroundColor Blue 
}

function msg-front { 
    param([string]`$msg) 
    `$time = Get-Date -Format 'HH:mm'
    "[`$time] [PM]: @frontend `$msg" | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8 -Append
    Write-Host "Сообщение отправлено FRONTEND: `$msg" -ForegroundColor Cyan 
}

function msg-dev { 
    param([string]`$msg) 
    `$time = Get-Date -Format 'HH:mm'
    "[`$time] [PM]: @devops `$msg" | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8 -Append
    Write-Host "Сообщение отправлено DEVOPS: `$msg" -ForegroundColor Yellow 
}

function msg-team { 
    param([string]`$teams, [string]`$msg) 
    `$time = Get-Date -Format 'HH:mm'
    `$mentions = ''
    if (`$teams -match 'b') {`$mentions += '@backend '}
    if (`$teams -match 'f') {`$mentions += '@frontend '}
    if (`$teams -match 'd') {`$mentions += '@devops '}
    "[`$time] [PM]: `$mentions`$msg" | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8 -Append
    Write-Host "Сообщение отправлено [`$teams]: `$msg" -ForegroundColor Magenta 
}

# Русские алиасы
function vsem { param([string]`$msg) msg-all `$msg }
function back { param([string]`$msg) msg-back `$msg }
function front { param([string]`$msg) msg-front `$msg }
function devops { param([string]`$msg) msg-dev `$msg }
function komande { param([string]`$teams, [string]`$msg) msg-team `$teams `$msg }

function status { 
    Write-Host 'Статусы задач:' -ForegroundColor Yellow
    Get-Content '.ai-team\chat.md' -Encoding UTF8 | Select-String 'done|working|blocked' | Select-Object -Last 10 | ForEach-Object { 
        if (`$_ -match 'done') { Write-Host `$_ -ForegroundColor Green } 
        elseif (`$_ -match 'working') { Write-Host `$_ -ForegroundColor Yellow } 
        elseif (`$_ -match 'blocked') { Write-Host `$_ -ForegroundColor Red } 
        else { Write-Host `$_ } 
    } 
}

function chat { 
    Write-Host 'Последние сообщения:' -ForegroundColor Cyan
    Get-Content '.ai-team\chat.md' -Encoding UTF8 -Tail 15 | ForEach-Object { 
        if (`$_ -match '\[PM\]') { Write-Host `$_ -ForegroundColor White } 
        elseif (`$_ -match '\[BACKEND\]') { Write-Host `$_ -ForegroundColor Blue } 
        elseif (`$_ -match '\[FRONTEND\]') { Write-Host `$_ -ForegroundColor Cyan } 
        elseif (`$_ -match '\[DEVOPS\]') { Write-Host `$_ -ForegroundColor Yellow } 
        else { Write-Host `$_ -ForegroundColor Gray } 
    } 
}

function clear-chat { 
    Clear-Content '.ai-team\chat.md'
    `$time = Get-Date -Format 'HH:mm'
    "[`$time] [SYSTEM]: Чат очищен. AI команда готова." | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8
    Write-Host 'Чат очищен!' -ForegroundColor Green 
}

function ochistit { clear-chat }

function help { 
    Write-Host ''
    Write-Host 'Команды AI Team:' -ForegroundColor Yellow
    Write-Host '  msg-all "text"  - Отправить всем' -ForegroundColor White
    Write-Host '  msg-back "text" - Отправить Backend' -ForegroundColor White
    Write-Host '  msg-front "text" - Отправить Frontend' -ForegroundColor White
    Write-Host '  msg-dev "text" - Отправить DevOps' -ForegroundColor White
    Write-Host '  msg-team "b,f,d" "text" - Отправить группе' -ForegroundColor White
    Write-Host '  vsem "text" - То же что msg-all (рус)' -ForegroundColor White
    Write-Host '  back "text" - То же что msg-back (рус)' -ForegroundColor White
    Write-Host '  front "text" - То же что msg-front (рус)' -ForegroundColor White
    Write-Host '  devops "text" - То же что msg-dev (рус)' -ForegroundColor White
    Write-Host '  komande "b,f,d" "text" - То же что msg-team (рус)' -ForegroundColor White
    Write-Host '  status - Показать статусы' -ForegroundColor White
    Write-Host '  chat - Показать чат' -ForegroundColor White
    Write-Host '  clear-chat / ochistit - Очистить чат' -ForegroundColor White
    Write-Host '' 
}

function pomosh { help }
"@
Start-Process powershell -ArgumentList "-NoExit", "-Command", $controlCenter -WindowStyle Normal

Write-Host "`n✅ AI Team запущена!" -ForegroundColor Green
Write-Host "Открыто 5 окон:" -ForegroundColor Yellow
Write-Host "  1. Backend Developer" -ForegroundColor White
Write-Host "  2. Frontend Developer" -ForegroundColor White
Write-Host "  3. DevOps Engineer" -ForegroundColor White
Write-Host "  4. Chat Monitor" -ForegroundColor White
Write-Host "  5. Control Center" -ForegroundColor White
Write-Host "`nИспользуйте Control Center для управления командой!" -ForegroundColor Cyan