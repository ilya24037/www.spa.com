@echo off
chcp 65001 >nul
color 0A
title Умный помощник разработчика v3.1

echo.
echo ╔═══════════════════════════════════════╗
echo ║     🤖 УМНЫЙ ПОМОЩНИК v3.1           ║  
echo ║     Максимальная автоматизация       ║
echo ╚═══════════════════════════════════════╝
echo.

cd /d D:\www.spa.com

:menu
echo 🚀 Что хотите сделать?
echo.
echo [1] 📝 Быстрый отчет для ИИ + открыть файл
echo [2] 📊 Полный анализ проекта 
echo [3] 🔬 Детальный дамп (все метрики)
echo [4] 💾 Сохранить в GitHub Desktop (ручной)
echo [5] ⚡ Автоматическое слежение
echo [6] 📂 Просто открыть AI_CONTEXT.md
echo [7] 📋 Показать содержимое в консоли
echo [8] 🔥 АВТОКОММИТ + отправка в GitHub
echo [9] 🛠️ Настроить шаблон коммитов
echo [0] ❌ Выход
echo.

set /p choice="Выберите (0-9): "

if "%choice%"=="1" goto quick_and_open
if "%choice%"=="2" goto full_analysis
if "%choice%"=="3" goto complete_dump
if "%choice%"=="4" goto github_save
if "%choice%"=="5" goto auto_watch
if "%choice%"=="6" goto open_context
if "%choice%"=="7" goto show_content
if "%choice%"=="8" goto auto_commit
if "%choice%"=="9" goto setup_template
if "%choice%"=="0" exit
goto menu

:quick_and_open
cls
echo 📝 Создаю быстрый отчет...
echo.
php artisan ai:context --quick 2>nul
if exist AI_CONTEXT.md (
    echo ✅ Готово! Открываю файл...
    start notepad AI_CONTEXT.md
    echo.
    echo 💡 СКОПИРУЙТЕ ВЕСЬ ТЕКСТ (Ctrl+A, Ctrl+C) И ВСТАВЬТЕ В ЧАТ С ИИ
) else (
    echo ❌ Ошибка: файл AI_CONTEXT.md не создан
)
pause
goto menu

:full_analysis
cls  
echo 📊 Запускаю полный анализ...
echo.
php artisan ai:context 2>nul
if exist AI_CONTEXT.md (
    echo ✅ Полный анализ готов! Открываю файл...
    start notepad AI_CONTEXT.md
) else (
    echo ❌ Ошибка: файл не создан
)
pause
goto menu

:complete_dump
cls
echo 🔬 Максимально детальный анализ...
echo.
echo 📊 Это займет немного времени...
php artisan ai:context --full 2>nul
if exist AI_CONTEXT.md (
    echo ✅ Детальный дамп готов! Открываю файл...
    start notepad AI_CONTEXT.md
    echo.
    echo 💡 В файле полная информация:
    echo    - Метрики качества кода
    echo    - Анализ производительности  
    echo    - Проверка безопасности
    echo    - Детальная статистика
) else (
    echo ❌ Ошибка: файл не создан
)
pause
goto menu

:github_save
cls
echo 💾 Инструкция для ручного коммита:
echo.
echo 📋 ПОШАГОВАЯ ИНСТРУКЦИЯ:
echo    1. Откройте GitHub Desktop
echo    2. Увидите список измененных файлов слева
echo    3. В поле "Summary" напишите что сделали
echo    4. Нажмите синюю кнопку "Commit to main"
echo    5. Нажмите "Push origin" (отправить в GitHub)
echo.
echo 💡 ИЛИ используйте пункт [8] для автокоммита!
pause
goto menu

:auto_watch
cls
echo ⚡ Запускаю автоматическое слежение...
echo.
echo 💡 Система будет каждые 5 минут обновлять AI_CONTEXT.md
echo    Вы всегда будете иметь актуальный отчет для ИИ!
echo.
echo ⚠️  Для остановки закройте это окно или нажмите Ctrl+C
echo.
if exist auto-context.bat (
    start auto-context.bat
    echo ✅ Автослежение запущено в отдельном окне!
) else (
    echo ❌ Файл auto-context.bat не найден
    echo 💡 Создайте файл auto-context.bat
)
pause
goto menu

:open_context
cls
echo 📂 Открываю AI_CONTEXT.md...
if exist AI_CONTEXT.md (
    start notepad AI_CONTEXT.md
    echo ✅ Файл открыт в блокноте!
    echo.
    echo 💡 Скопируйте весь текст и вставьте в чат с ИИ
) else (
    echo ❌ Файл не найден. Создайте отчет (пункт 1)
)
pause
goto menu

:show_content
cls
echo 📋 Содержимое AI_CONTEXT.md:
echo.
echo ================================================
if exist AI_CONTEXT.md (
    type AI_CONTEXT.md
) else (
    echo ❌ Файл не найден. Создайте отчет (пункт 1)
)
echo ================================================
echo.
pause
goto menu

:auto_commit
cls
echo 🔥 АВТОМАТИЧЕСКИЙ КОММИТ + GitHub...
echo.

:: Генерируем свежий контекст
echo 📝 Обновляю контекст для ИИ...
php artisan ai:context --auto --quick >nul 2>&1

:: Анализируем изменения и создаем умное сообщение
echo 🔍 Анализирую изменения...

git status --porcelain > temp_status.txt 2>nul
if exist temp_status.txt (
    set commit_msg=feat: обновление проекта

    :: Проверяем типы измененных файлов
    findstr /i "Models" temp_status.txt >nul && set commit_msg=feat: обновление моделей данных
    findstr /i "Controller" temp_status.txt >nul && set commit_msg=feat: разработка API контроллеров  
    findstr /i "Component" temp_status.txt >nul && set commit_msg=ui: создание Vue компонентов
    findstr /i "migration" temp_status.txt >nul && set commit_msg=db: изменения структуры БД
    findstr /i "AI_CONTEXT" temp_status.txt >nul && set commit_msg=docs: обновление ИИ помощника
    findstr /i "\.bat" temp_status.txt >nul && set commit_msg=chore: улучшение автоматизации

    del temp_status.txt >nul 2>&1

    echo 💬 Сообщение: %commit_msg%
    echo.

    :: Выполняем коммит
    echo 📦 Создаю коммит...
    git add .
    git commit -m "%commit_msg%"

    if %ERRORLEVEL% EQU 0 (
        echo ✅ Коммит создан!
        echo.
        echo 🚀 Отправляю в GitHub...
        git push origin main
        
        if %ERRORLEVEL% EQU 0 (
            echo ✅ ГОТОВО! Все изменения в GitHub!
            echo.
            echo 🎉 Можете продолжать работу!
        ) else (
            echo ❌ Ошибка отправки в GitHub
            echo 💡 Проверьте интернет соединение
        )
    ) else (
        echo ❌ Ошибка создания коммита
        echo 💡 Возможно нет изменений для коммита
    )
) else (
    echo ❌ Ошибка при проверке статуса git
    echo 💡 Проверьте что проект инициализирован как git репозиторий
)

echo.
pause
goto menu

:setup_template
cls
echo 🛠️ Настройка шаблона коммитов для GitHub Desktop...
echo.

:: Создаем шаблон
echo feat: обновление проекта > .gitmessage
echo. >> .gitmessage
echo - Автоматически сгенерированный коммит >> .gitmessage
echo - Контекст для ИИ обновлен >> .gitmessage
echo - Готово к анализу помощником >> .gitmessage
echo. >> .gitmessage
echo # Типы коммитов: >> .gitmessage
echo # feat: новая функциональность >> .gitmessage
echo # fix: исправление бага >> .gitmessage
echo # docs: обновление документации >> .gitmessage
echo # ui: изменения интерфейса >> .gitmessage
echo # db: изменения БД >> .gitmessage
echo # refactor: рефакторинг >> .gitmessage
echo # test: добавление тестов >> .gitmessage
echo # chore: обновление зависимостей >> .gitmessage

:: Настраиваем Git
git config commit.template .gitmessage 2>nul

echo ✅ Шаблон настроен!
echo.
echo 💡 Теперь GitHub Desktop будет автоматически
echo    заполнять поле Summary этим шаблоном!
echo.
echo 🔄 Перезапустите GitHub Desktop чтобы увидеть изменения
echo.
pause
goto menu