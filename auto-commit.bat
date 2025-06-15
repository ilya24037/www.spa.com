@echo off
chcp 65001 >nul
color 0A
title Автоматический коммит

echo 🤖 Умный автоматический коммит...
cd /d D:\www.spa.com

:: Генерируем контекст для анализа изменений
php artisan ai:context --auto --quick >nul 2>&1

:: Анализируем что изменилось
set commit_msg="feat: обновление проекта"

:: Проверяем типы файлов и создаем умное сообщение
git status --porcelain > temp_status.txt

findstr /i "Models" temp_status.txt >nul && set commit_msg="feat: обновление моделей данных"
findstr /i "Controller" temp_status.txt >nul && set commit_msg="feat: разработка API контроллеров"
findstr /i "Component" temp_status.txt >nul && set commit_msg="ui: создание Vue компонентов"
findstr /i "migration" temp_status.txt >nul && set commit_msg="db: изменения структуры БД"
findstr /i "AI_CONTEXT" temp_status.txt >nul && set commit_msg="docs: обновление контекста для ИИ"
findstr /i "\.bat" temp_status.txt >nul && set commit_msg="chore: обновление автоматизации"

del temp_status.txt >nul 2>&1

echo 📝 Сообщение коммита: %commit_msg%
echo.

:: Выполняем коммит
git add .
git commit -m %commit_msg%

if %ERRORLEVEL% EQU 0 (
    echo ✅ Коммит создан успешно!
    echo.
    echo 🚀 Отправляю в GitHub...
    git push origin main
    
    if %ERRORLEVEL% EQU 0 (
        echo ✅ Изменения отправлены в GitHub!
    ) else (
        echo ❌ Ошибка при отправке в GitHub
    )
) else (
    echo ❌ Ошибка при создании коммита
)

echo.
pause