@echo off
title AI Team Launcher - SPA Platform
color 0A
chcp 65001 >nul

echo.
echo ========================================
echo     AI TEAM LAUNCHER - SPA PLATFORM
echo ========================================
echo.

:: Проверяем существование папки .ai-team
if not exist ".ai-team" (
    mkdir ".ai-team"
    echo Created .ai-team folder
)

:: Проверяем существование подпапок
if not exist ".ai-team\backend" mkdir ".ai-team\backend"
if not exist ".ai-team\frontend" mkdir ".ai-team\frontend"
if not exist ".ai-team\devops" mkdir ".ai-team\devops"

:: Создаем CLAUDE.md файлы для каждой роли если их нет
if not exist ".ai-team\backend\CLAUDE.md" (
    echo # Backend Developer Instructions > ".ai-team\backend\CLAUDE.md"
    echo You are a Backend Developer for SPA Platform. >> ".ai-team\backend\CLAUDE.md"
    echo Tech stack: Laravel, PHP, MySQL >> ".ai-team\backend\CLAUDE.md"
    echo Monitor ../chat.md every 30 seconds >> ".ai-team\backend\CLAUDE.md"
    echo Execute tasks with @backend or @all >> ".ai-team\backend\CLAUDE.md"
    echo Write results to chat: [HH:MM] [BACKEND]: message >> ".ai-team\backend\CLAUDE.md"
)

if not exist ".ai-team\frontend\CLAUDE.md" (
    echo # Frontend Developer Instructions > ".ai-team\frontend\CLAUDE.md"
    echo You are a Frontend Developer for SPA Platform. >> ".ai-team\frontend\CLAUDE.md"
    echo Tech stack: Vue 3, TypeScript, Tailwind CSS >> ".ai-team\frontend\CLAUDE.md"
    echo Monitor ../chat.md every 30 seconds >> ".ai-team\frontend\CLAUDE.md"
    echo Execute tasks with @frontend or @all >> ".ai-team\frontend\CLAUDE.md"
    echo Write results to chat: [HH:MM] [FRONTEND]: message >> ".ai-team\frontend\CLAUDE.md"
)

if not exist ".ai-team\devops\CLAUDE.md" (
    echo # DevOps Engineer Instructions > ".ai-team\devops\CLAUDE.md"
    echo You are a DevOps Engineer for SPA Platform. >> ".ai-team\devops\CLAUDE.md"
    echo Tech stack: Docker, CI/CD, nginx >> ".ai-team\devops\CLAUDE.md"
    echo Monitor ../chat.md every 30 seconds >> ".ai-team\devops\CLAUDE.md"
    echo Execute tasks with @devops or @all >> ".ai-team\devops\CLAUDE.md"
    echo Write results to chat: [HH:MM] [DEVOPS]: message >> ".ai-team\devops\CLAUDE.md"
)

:: Добавляем стартовое сообщение в чат
echo [%TIME:~0,5%] [SYSTEM]: AI Team starting up... >> .ai-team\chat.md

:: Запускаем Backend Developer
echo Starting Backend Developer...
start "AI Backend" cmd /k "cd /d .ai-team\backend && echo BACKEND DEVELOPER READY && echo ============================= && echo. && claude --dangerously-skip-permissions \"Ты Backend разработчик для SPA Platform (Laravel, PHP). Читай файл ../chat.md каждые 30 секунд. Выполняй задачи с @backend или @all. После выполнения пиши результат в ../chat.md в формате: [HH:MM] [BACKEND]: результат. Начни с приветствия в чате.\""

timeout /t 2 >nul

:: Запускаем Frontend Developer
echo Starting Frontend Developer...
start "AI Frontend" cmd /k "cd /d .ai-team\frontend && echo FRONTEND DEVELOPER READY && echo ============================= && echo. && claude --dangerously-skip-permissions \"Ты Frontend разработчик для SPA Platform (Vue 3, TypeScript). Читай файл ../chat.md каждые 30 секунд. Выполняй задачи с @frontend или @all. После выполнения пиши результат в ../chat.md в формате: [HH:MM] [FRONTEND]: результат. Начни с приветствия в чате.\""

timeout /t 2 >nul

:: Запускаем DevOps Engineer
echo Starting DevOps Engineer...
start "AI DevOps" cmd /k "cd /d .ai-team\devops && echo DEVOPS ENGINEER READY && echo ============================= && echo. && claude --dangerously-skip-permissions \"Ты DevOps инженер для SPA Platform (Docker, CI/CD). Читай файл ../chat.md каждые 30 секунд. Выполняй задачи с @devops или @all. После выполнения пиши результат в ../chat.md в формате: [HH:MM] [DEVOPS]: результат. Начни с приветствия в чате.\""

timeout /t 2 >nul

:: Запускаем Chat Monitor
echo Starting Chat Monitor...
start "Chat Monitor" powershell -NoExit -Command "cd 'C:\www.spa.com'; Write-Host 'CHAT MONITOR ACTIVE' -ForegroundColor Magenta; Write-Host '===================='; Write-Host ''; Get-Content '.ai-team\chat.md' -Wait -Tail 20 -Encoding UTF8"

timeout /t 2 >nul

:: Запускаем Control Center
echo Starting Control Center...
start "Control Center" powershell -NoExit -File "C:\www.spa.com\control-center.ps1"

echo.
echo ========================================
echo    AI TEAM STARTED SUCCESSFULLY!
echo ========================================
echo.
echo 5 windows opened:
echo   1. Backend Developer (Claude)
echo   2. Frontend Developer (Claude)
echo   3. DevOps Engineer (Claude)
echo   4. Chat Monitor
echo   5. Control Center
echo.
echo Use Control Center to manage the team!
echo.
pause