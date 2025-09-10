@echo off
title TeamLead Dual Mode
color 0D

echo.
echo ========================================
echo    TEAMLEAD - DUAL MODE
echo ========================================
echo.
echo TeamLead будет работать в ДВУХ режимах:
echo.
echo 1. ТЕРМИНАЛ - вы можете общаться напрямую
echo    - Показывать скриншоты
echo    - Обсуждать архитектуру
echo    - Анализировать код
echo.
echo 2. ЧАТ - TeamLead читает .ai-team/chat.md
echo    - Отвечает на @teamlead и @all
echo    - Координирует команду
echo    - Дублирует важные решения из терминала
echo.

cd /d C:\www.spa.com

echo Starting TeamLead in dual mode...
echo.

claude --dangerously-skip-permissions "You are TeamLead in DUAL MODE. IMPORTANT: 1) Monitor C:\www.spa.com\.ai-team\chat.md file every 5 seconds. When you see @teamlead or @all in chat, respond there with format [HH:MM] [TEAMLEAD]: message. 2) Also respond to direct terminal input from PM. When PM shows you screenshots or code in terminal, analyze it here, then write coordination tasks to chat for the team. You work in TWO channels simultaneously - terminal with PM and chat with team. Never write code yourself, only coordinate!"

pause