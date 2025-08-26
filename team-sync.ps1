# Team Sync Reminder Script
# Напоминает AI агентам синхронизироваться через чат

$chatPath = "C:\www.spa.com\.ai-team\chat.md"

while ($true) {
    # Каждые 5 минут добавляем напоминание в чат
    Start-Sleep -Seconds 300
    
    $time = Get-Date -Format 'HH:mm'
    $reminder = @"

[$time] [SYSTEM]: 📊 TEAM SYNC REMINDER
@all Пожалуйста, обновите статус вашей работы:
- @backend: что сделано по модели/API?
- @frontend: что сделано по компонентам?
- @devops: статус инфраструктуры?
Используйте статусы: 🔄 working | ✅ done | ❌ blocked | 🤝 need help

"@
    
    Add-Content -Path $chatPath -Value $reminder -Encoding UTF8
    Write-Host "Sync reminder sent at $time" -ForegroundColor Yellow
}