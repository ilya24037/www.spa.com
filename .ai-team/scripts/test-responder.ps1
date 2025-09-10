# Test Auto Responder
# Простой тест для проверки работы респондера

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "    TEST AUTO RESPONDER" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Добавляем тестовое сообщение в чат
$chatFile = "C:\www.spa.com\.ai-team\chat.md"
$time = Get-Date -Format "HH:mm"

# Добавляем сообщение от PM
$testMessage = "[$time] [PM]: @all тестовая проверка связи - ответьте если работаете!"
Add-Content -Path $chatFile -Value $testMessage -Encoding UTF8

Write-Host "✅ Добавлено тестовое сообщение в чат:" -ForegroundColor Yellow
Write-Host $testMessage -ForegroundColor Cyan
Write-Host ""
Write-Host "Ожидаем ответы от агентов..." -ForegroundColor Yellow

# Ждем 5 секунд
Start-Sleep -Seconds 5

# Читаем последние 10 строк чата
Write-Host ""
Write-Host "Последние сообщения в чате:" -ForegroundColor Green
Write-Host "----------------------------------------" -ForegroundColor DarkGray

$lastLines = Get-Content $chatFile -Tail 10 -Encoding UTF8
foreach ($line in $lastLines) {
    if ($line -match "\[BACKEND\]") {
        Write-Host $line -ForegroundColor Green
    }
    elseif ($line -match "\[FRONTEND\]") {
        Write-Host $line -ForegroundColor Cyan
    }
    elseif ($line -match "\[DEVOPS\]") {
        Write-Host $line -ForegroundColor Yellow
    }
    elseif ($line -match "\[PM\]") {
        Write-Host $line -ForegroundColor Magenta
    }
    else {
        Write-Host $line -ForegroundColor Gray
    }
}

Write-Host ""
Write-Host "✅ Тест завершен!" -ForegroundColor Green
Write-Host ""
Write-Host "Если агенты не ответили, запустите Auto Responder!" -ForegroundColor Yellow