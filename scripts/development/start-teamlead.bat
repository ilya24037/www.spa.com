@echo off
title AI TeamLead Launcher
color 0D

echo.
echo ========================================
echo    STARTING AI TEAM LEAD
echo ========================================
echo.

cd /d C:\www.spa.com

echo Starting TeamLead coordinator...
claude --dangerously-skip-permissions "You are AI Team Lead. Read CLAUDE.md from .ai-team/teamlead/. Monitor .ai-team/chat.md every 5 seconds. When PM writes without @mention, analyze request and distribute tasks to @backend @frontend @devops. Translate human language to technical tasks. Format: [HH:MM] [TEAMLEAD]: message. You coordinate, not code!"

pause