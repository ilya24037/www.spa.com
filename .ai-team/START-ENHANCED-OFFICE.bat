@echo off
chcp 65001 >nul 2>&1
title 🏢 Enhanced Virtual Office with Knowledge Base

echo ╔════════════════════════════════════════════════════════════════╗
echo ║     🏢 ENHANCED VIRTUAL OFFICE - SPA PLATFORM EDITION          ║
echo ║                   AI Team with Knowledge Base                   ║
echo ╔════════════════════════════════════════════════════════════════╝
echo.

:: Проверка Claude
where claude >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Claude не найден! Установите: npm install -g @anthropic-ai/claude-code
    pause
    exit /b 1
)

:: Синхронизация знаний
echo 📚 Синхронизация базы знаний проекта...
powershell -ExecutionPolicy Bypass -File scripts\knowledge-sync.ps1 -Mode sync
if %errorlevel% neq 0 (
    echo ⚠️  Предупреждение: синхронизация знаний завершена с ошибками
    echo    Продолжаем запуск...
)
echo.

:: Создание необходимых директорий
echo 📁 Проверка структуры...
if not exist "virtual-office\inbox" mkdir "virtual-office\inbox"
if not exist "virtual-office\inbox\teamlead" mkdir "virtual-office\inbox\teamlead"
if not exist "virtual-office\inbox\backend" mkdir "virtual-office\inbox\backend"
if not exist "virtual-office\inbox\frontend" mkdir "virtual-office\inbox\frontend"
if not exist "virtual-office\inbox\qa" mkdir "virtual-office\inbox\qa"
if not exist "virtual-office\inbox\devops" mkdir "virtual-office\inbox\devops"
if not exist "virtual-office\outbox" mkdir "virtual-office\outbox"
if not exist "virtual-office\tasks" mkdir "virtual-office\tasks"
if not exist "virtual-office\reports" mkdir "virtual-office\reports"
if not exist "virtual-office\channels\general" mkdir "virtual-office\channels\general"
if not exist "virtual-office\channels\standup" mkdir "virtual-office\channels\standup"
if not exist "virtual-office\channels\help" mkdir "virtual-office\channels\help"
if not exist "virtual-office\metrics" mkdir "virtual-office\metrics"
if not exist "system\logs" mkdir "system\logs"

:: Инициализация статуса
echo 🔧 Инициализация системы...
echo {} > virtual-office\system\status.json
echo [] > virtual-office\tasks\active.json
echo.

:: Запуск сервера
echo 🌐 Запуск AI Team Server...
start /min cmd /c "node ai-team-server.cjs"
timeout /t 2 /nobreak >nul

:: Запуск агентов с улучшенными инструкциями
echo 🤖 Запуск агентов с расширенными знаниями...
echo.

echo    [1/5] TeamLead Agent (координатор)...
start /min powershell -ExecutionPolicy Bypass -WindowStyle Hidden -Command "& {
    $ErrorActionPreference = 'SilentlyContinue'
    while ($true) {
        $inbox = Get-ChildItem 'virtual-office\inbox\teamlead' -Filter '*.json' 2>$null
        if ($inbox) {
            foreach ($msg in $inbox) {
                $content = Get-Content $msg.FullName -Raw | ConvertFrom-Json
                Write-Output 'Processing: ' $content.message

                # Используем улучшенные инструкции
                claude chat --file 'teamlead\CLAUDE_ENHANCED.md' --file 'virtual-office\knowledge\KNOWLEDGE_MAP.md' ""Process task: $($content.message)""

                Move-Item $msg.FullName 'virtual-office\outbox\' -Force
            }
        }
        Start-Sleep -Seconds 5
    }
}"

echo    [2/5] Backend Agent (Laravel DDD)...
start /min powershell -ExecutionPolicy Bypass -WindowStyle Hidden -Command "& {
    $ErrorActionPreference = 'SilentlyContinue'
    while ($true) {
        $inbox = Get-ChildItem 'virtual-office\inbox\backend' -Filter '*.json' 2>$null
        if ($inbox) {
            foreach ($msg in $inbox) {
                $content = Get-Content $msg.FullName -Raw | ConvertFrom-Json

                # Используем улучшенные инструкции с паттернами
                claude chat --file 'backend\CLAUDE_ENHANCED.md' --file 'virtual-office\knowledge\lessons\BUSINESS_LOGIC_FIRST.md' ""Process: $($content.message)""

                Move-Item $msg.FullName 'virtual-office\outbox\' -Force
            }
        }
        Start-Sleep -Seconds 10
    }
}"

echo    [3/5] Frontend Agent (Vue 3 FSD)...
start /min powershell -ExecutionPolicy Bypass -WindowStyle Hidden -Command "& {
    $ErrorActionPreference = 'SilentlyContinue'
    while ($true) {
        $inbox = Get-ChildItem 'virtual-office\inbox\frontend' -Filter '*.json' 2>$null
        if ($inbox) {
            foreach ($msg in $inbox) {
                $content = Get-Content $msg.FullName -Raw | ConvertFrom-Json

                # Используем улучшенные инструкции с правилами Vue
                claude chat --file 'frontend\CLAUDE_ENHANCED.md' --file 'virtual-office\knowledge\QUICK_REFERENCE.md' ""Process: $($content.message)""

                Move-Item $msg.FullName 'virtual-office\outbox\' -Force
            }
        }
        Start-Sleep -Seconds 10
    }
}"

echo    [4/5] QA Agent (тестировщик)...
start /min powershell -ExecutionPolicy Bypass -WindowStyle Hidden -Command "& {
    $ErrorActionPreference = 'SilentlyContinue'
    while ($true) {
        $inbox = Get-ChildItem 'virtual-office\inbox\qa' -Filter '*.json' 2>$null
        if ($inbox) {
            foreach ($msg in $inbox) {
                $content = Get-Content $msg.FullName -Raw | ConvertFrom-Json

                # Используем инструкции с чек-листами
                claude chat --file 'qa\CLAUDE_ENHANCED.md' --file 'virtual-office\knowledge\problems\INDEX.md' ""Test: $($content.message)""

                Move-Item $msg.FullName 'virtual-office\outbox\' -Force
            }
        }
        Start-Sleep -Seconds 10
    }
}"

echo    [5/5] DevOps Agent (инфраструктура)...
start /min powershell -ExecutionPolicy Bypass -WindowStyle Hidden -Command "& {
    $ErrorActionPreference = 'SilentlyContinue'
    while ($true) {
        $inbox = Get-ChildItem 'virtual-office\inbox\devops' -Filter '*.json' 2>$null
        if ($inbox) {
            foreach ($msg in $inbox) {
                $content = Get-Content $msg.FullName -Raw | ConvertFrom-Json

                claude chat --file 'devops\CLAUDE.md' ""Deploy: $($content.message)""

                Move-Item $msg.FullName 'virtual-office\outbox\' -Force
            }
        }
        Start-Sleep -Seconds 10
    }
}"

echo.
echo ✅ Все агенты запущены с расширенной базой знаний!
echo.

:: Запуск автоматических стендапов
echo ⏰ Настройка автоматических стендапов...
start /min powershell -ExecutionPolicy Bypass -File scripts\auto-standup.ps1
echo.

:: Информация о доступе
echo ╔════════════════════════════════════════════════════════════════╗
echo ║                    🎯 СИСТЕМА ГОТОВА К РАБОТЕ                   ║
echo ╠════════════════════════════════════════════════════════════════╣
echo ║                                                                 ║
echo ║  📊 Веб-интерфейс:    http://localhost:8082                    ║
echo ║  🎮 CEO Dashboard:    http://localhost:8082/ceo-dashboard.html  ║
echo ║  💬 Чат команды:      http://localhost:8082/chat.html          ║
echo ║                                                                 ║
echo ║  📚 База знаний синхронизирована из docs/                      ║
echo ║  ⚡ Быстрые команды доступны в virtual-office/knowledge/       ║
echo ║                                                                 ║
echo ╠════════════════════════════════════════════════════════════════╣
echo ║                       ПОЛЕЗНЫЕ КОМАНДЫ                          ║
echo ╠════════════════════════════════════════════════════════════════╣
echo ║                                                                 ║
echo ║  Создать задачу:                                               ║
echo ║  powershell scripts\task-manager.ps1 -Action create            ║
echo ║                                                                 ║
echo ║  Быстрый поиск решения:                                        ║
echo ║  powershell scripts\quick-fix.ps1 -Error "текст ошибки"        ║
echo ║                                                                 ║
echo ║  Проверить статус:                                             ║
echo ║  powershell scripts\check-agents.ps1                           ║
echo ║                                                                 ║
echo ║  Синхронизировать знания:                                      ║
echo ║  powershell scripts\knowledge-sync.ps1                         ║
echo ║                                                                 ║
echo ╚════════════════════════════════════════════════════════════════╝
echo.
echo 💡 Подсказка: Агенты теперь знают все уроки из docs/LESSONS/
echo    и автоматически применяют паттерны из опыта проекта!
echo.
echo Нажмите любую клавишу для закрытия этого окна...
pause >nul