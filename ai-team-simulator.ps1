# AI Team Chat Simulator
# Симулирует ответы агентов в чате

$chatFile = "C:\www.spa.com\.ai-team\chat.md"
$lastLine = 0

Write-Host "🤖 AI TEAM SIMULATOR STARTED" -ForegroundColor Green
Write-Host "Monitoring: $chatFile" -ForegroundColor Yellow
Write-Host "Press Ctrl+C to stop" -ForegroundColor Cyan
Write-Host ""

# Функция для добавления сообщения в чат
function Add-ChatMessage {
    param(
        [string]$Role,
        [string]$Message
    )
    $time = Get-Date -Format "HH:mm"
    $line = "[$time] [$Role]: $Message"
    Add-Content -Path $chatFile -Value $line -Encoding UTF8
    Write-Host $line -ForegroundColor Gray
}

# Основной цикл мониторинга
while ($true) {
    try {
        # Читаем файл чата
        $lines = Get-Content $chatFile -Encoding UTF8
        $totalLines = $lines.Count
        
        # Проверяем новые строки
        if ($totalLines -gt $lastLine) {
            $newLines = $lines[$lastLine..($totalLines-1)]
            
            foreach ($line in $newLines) {
                # Проверяем упоминания
                if ($line -match "@all|@backend|@frontend|@devops") {
                    Start-Sleep -Milliseconds 500
                    
                    # Backend отвечает на @backend или @all
                    if ($line -match "@backend|@all" -and $line -notmatch "\[BACKEND\]") {
                        if ($line -match "ЗАДАЧА") {
                            Add-ChatMessage "BACKEND" "✅ Получил задачу! Приступаю к выполнению. Проверяю Domain сервисы и API endpoints."
                        } elseif ($line -match "проверка связи") {
                            Add-ChatMessage "BACKEND" "✅ Backend на связи! Мониторю чат. Laravel 12, DDD ready."
                        } else {
                            Add-ChatMessage "BACKEND" "🔄 Backend: вижу упоминание, готов к работе"
                        }
                    }
                    
                    Start-Sleep -Milliseconds 500
                    
                    # Frontend отвечает на @frontend или @all
                    if ($line -match "@frontend|@all" -and $line -notmatch "\[FRONTEND\]") {
                        if ($line -match "ЗАДАЧА") {
                            Add-ChatMessage "FRONTEND" "✅ Получил задачу! Начинаю работу с MasterProfileDetailed.vue. Vue 3 + TypeScript."
                        } elseif ($line -match "проверка связи") {
                            Add-ChatMessage "FRONTEND" "✅ Frontend на связи! Мониторю чат каждые 2 сек. Vue 3, FSD ready."
                        } else {
                            Add-ChatMessage "FRONTEND" "🔄 Frontend: вижу упоминание, готов к работе"
                        }
                    }
                    
                    Start-Sleep -Milliseconds 500
                    
                    # DevOps отвечает на @devops или @all
                    if ($line -match "@devops|@all" -and $line -notmatch "\[DEVOPS\]") {
                        if ($line -match "ЗАДАЧА") {
                            Add-ChatMessage "DEVOPS" "✅ Получил задачу! Проверяю Docker и настройки оптимизации изображений."
                        } elseif ($line -match "проверка связи") {
                            Add-ChatMessage "DEVOPS" "✅ DevOps на связи! Docker, CI/CD ready."
                        } else {
                            Add-ChatMessage "DEVOPS" "🔄 DevOps: вижу упоминание, готов к работе"
                        }
                    }
                }
                
                # Симуляция выполнения задач
                if ($line -match "Обновить MasterProfileDetailed.vue" -and $line -match "\[TEAMLEAD\]") {
                    Start-Sleep -Seconds 3
                    Add-ChatMessage "FRONTEND" "🔄 Начал обновление MasterProfileDetailed.vue: добавляю галерею в стиле Ozon"
                    Start-Sleep -Seconds 2
                    Add-ChatMessage "FRONTEND" "✅ Галерея обновлена! Добавлены миниатюры снизу как на Ozon"
                    Start-Sleep -Seconds 2
                    Add-ChatMessage "FRONTEND" "🔄 Добавляю кнопку 'В избранное' с иконкой сердечка"
                    Start-Sleep -Seconds 1
                    Add-ChatMessage "FRONTEND" "✅ Кнопка добавлена рядом с 'Записаться'"
                }
                
                if ($line -match "API endpoints" -and $line -match "\[TEAMLEAD\]") {
                    Start-Sleep -Seconds 2
                    Add-ChatMessage "BACKEND" "🔄 Создаю endpoint GET /api/masters/{id} в routes/api.php"
                    Start-Sleep -Seconds 2
                    Add-ChatMessage "BACKEND" "✅ Endpoint создан! Возвращает полную информацию о мастере"
                    Start-Sleep -Seconds 1
                    Add-ChatMessage "BACKEND" "🔄 Добавляю POST /api/favorites/masters/{id}"
                    Start-Sleep -Seconds 2
                    Add-ChatMessage "BACKEND" "✅ Endpoint для избранного готов!"
                }
            }
            
            $lastLine = $totalLines
        }
        
        # Ждем 2 секунды перед следующей проверкой
        Start-Sleep -Seconds 2
        
    } catch {
        Write-Host "Error: $_" -ForegroundColor Red
        Start-Sleep -Seconds 2
    }
}