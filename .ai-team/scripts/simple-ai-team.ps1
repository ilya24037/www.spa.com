# Простой запуск AI команды - только мониторинг и управление
# БЕЗ автоматических AI агентов

chcp 65001 | Out-Null
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "    SIMPLE AI TEAM - MANUAL MODE" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Cyan

# Проверяем существование папки
if (-not (Test-Path ".ai-team")) {
    New-Item -ItemType Directory -Path ".ai-team" -Force | Out-Null
    Write-Host "✅ Создана папка .ai-team" -ForegroundColor Green
}

# Проверяем существование chat.md
if (-not (Test-Path ".ai-team\chat.md")) {
    @"
# 💬 AI Team Chat - SPA Platform

## 📋 Формат сообщений
```
[HH:MM] [ROLE]: сообщение
```

## 🏷️ Роли
- **PM** - Project Manager (человек)
- **BACKEND** - Backend Developer (Laravel)
- **FRONTEND** - Frontend Developer (Vue 3)
- **DEVOPS** - DevOps Engineer (Docker/CI)

## 📌 Статусы задач
- ✅ **done** - задача выполнена
- 🔄 **working** - в процессе
- ❌ **blocked** - заблокирован, нужна помощь
- 🤝 **need @role** - нужна помощь коллеги
- 📝 **reviewing** - проверяю результат

---

## 📝 История чата

"@ | Out-File -FilePath ".ai-team\chat.md" -Encoding UTF8
    Write-Host "✅ Создан файл chat.md" -ForegroundColor Green
}

# Добавляем стартовое сообщение
$time = Get-Date -Format 'HH:mm'
"[$time] [SYSTEM]: Simple AI Team started - Manual mode" | Out-File -FilePath '.ai-team\chat.md' -Encoding UTF8 -Append

# Запускаем Chat Monitor
Write-Host "Starting Chat Monitor..." -ForegroundColor Magenta
$chatMonitor = @"
chcp 65001 | Out-Null
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
cd 'C:\www.spa.com'
Write-Host 'CHAT MONITOR ACTIVE' -ForegroundColor Magenta
Write-Host '===================='
Write-Host ''
Write-Host 'Следим за файлом: .ai-team\chat.md' -ForegroundColor Yellow
Write-Host ''
Get-Content '.ai-team\chat.md' -Wait -Tail 20 -Encoding UTF8
"@
Start-Process powershell -ArgumentList "-NoExit", "-Command", $chatMonitor -WindowStyle Normal

# Запускаем Control Center
Write-Host "Starting Control Center..." -ForegroundColor White
Start-Process powershell -ArgumentList "-NoExit", "-File", "C:\www.spa.com\control-center.ps1" -WindowStyle Normal

Write-Host "`n✅ Simple AI Team запущен!" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Открыто 2 окна:" -ForegroundColor Yellow
Write-Host "  1. Chat Monitor - показывает чат" -ForegroundColor White
Write-Host "  2. Control Center - для отправки команд" -ForegroundColor White
Write-Host ""
Write-Host "🚀 КАК РАБОТАТЬ:" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. В Control Center пишите команды:" -ForegroundColor White
Write-Host "   msg-all 'текст'  - сообщение для всех" -ForegroundColor Gray
Write-Host "   msg-back 'текст' - для backend" -ForegroundColor Gray
Write-Host "   msg-front 'текст' - для frontend" -ForegroundColor Gray
Write-Host "   msg-dev 'текст'  - для devops" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Запустите Claude в отдельных терминалах:" -ForegroundColor White
Write-Host "   claude" -ForegroundColor Gray
Write-Host ""
Write-Host "3. В каждом Claude скажите ему роль:" -ForegroundColor White
Write-Host "   'Ты Backend разработчик. Читай C:\www.spa.com\.ai-team\chat.md'" -ForegroundColor Gray
Write-Host "   'Ты Frontend разработчик. Читай C:\www.spa.com\.ai-team\chat.md'" -ForegroundColor Gray
Write-Host "   'Ты DevOps инженер. Читай C:\www.spa.com\.ai-team\chat.md'" -ForegroundColor Gray
Write-Host ""
Write-Host "✨ Готово к работе!" -ForegroundColor Green