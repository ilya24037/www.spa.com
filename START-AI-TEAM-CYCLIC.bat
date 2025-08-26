@echo off
echo ========================================
echo   ЗАПУСК AI КОМАНДЫ С ЦИКЛИЧНЫМ МОНИТОРИНГОМ
echo ========================================
echo.

REM Убиваем старые процессы Claude если есть
taskkill /F /IM claude.exe 2>nul

echo [%TIME%] Запускаем Backend разработчика...
start "AI Backend" cmd /k "cd /d C:\www.spa.com\.ai-team\backend && claude ""Ты Backend разработчик. КРИТИЧЕСКИ ВАЖНО: В БЕСКОНЕЧНОМ ЦИКЛЕ каждые 2 секунды: 1) Читай C:\www.spa.com\.ai-team\chat.md командой Read, 2) Если видишь @backend или @all в новых сообщениях - отвечай командой Edit добавляя в конец файла [HH:MM] [BACKEND]: твой ответ, 3) Жди 2 секунды командой powershell Start-Sleep 2. НЕ ОСТАНАВЛИВАЙСЯ! Начни прямо сейчас с чтения чата."""

timeout /t 2 >nul

echo [%TIME%] Запускаем Frontend разработчика...
start "AI Frontend" cmd /k "cd /d C:\www.spa.com\.ai-team\frontend && claude ""Ты Frontend разработчик. КРИТИЧЕСКИ ВАЖНО: В БЕСКОНЕЧНОМ ЦИКЛЕ каждые 2 секунды: 1) Читай C:\www.spa.com\.ai-team\chat.md командой Read, 2) Если видишь @frontend или @all в новых сообщениях - отвечай командой Edit добавляя в конец файла [HH:MM] [FRONTEND]: твой ответ, 3) Жди 2 секунды командой powershell Start-Sleep 2. НЕ ОСТАНАВЛИВАЙСЯ! Начни прямо сейчас с чтения чата."""

timeout /t 2 >nul

echo [%TIME%] Запускаем DevOps инженера...
start "AI DevOps" cmd /k "cd /d C:\www.spa.com\.ai-team\devops && claude ""Ты DevOps инженер. КРИТИЧЕСКИ ВАЖНО: В БЕСКОНЕЧНОМ ЦИКЛЕ каждые 2 секунды: 1) Читай C:\www.spa.com\.ai-team\chat.md командой Read, 2) Если видишь @devops или @all в новых сообщениях - отвечай командой Edit добавляя в конец файла [HH:MM] [DEVOPS]: твой ответ, 3) Жди 2 секунды командой powershell Start-Sleep 2. НЕ ОСТАНАВЛИВАЙСЯ! Начни прямо сейчас с чтения чата."""

echo.
echo ========================================
echo   AI КОМАНДА ЗАПУЩЕНА!
echo ========================================
echo.
echo Агенты теперь в ЦИКЛИЧНОМ режиме мониторинга
echo Они будут читать чат каждые 2 секунды
echo.
echo Для остановки закройте окна агентов
echo.
pause