@echo off
title AI TeamLead Hybrid Mode
color 0D

echo.
echo ========================================
echo    STARTING AI TEAM LEAD - HYBRID MODE
echo ========================================
echo.
echo TeamLead will work in TWO modes:
echo 1. TERMINAL - analyze screenshots, code, architecture with PM
echo 2. CHAT - coordinate team through .ai-team/chat.md
echo.

cd /d C:\www.spa.com

echo Starting TeamLead with hybrid configuration...
claude --dangerously-skip-permissions "You are AI Team Lead in HYBRID mode. Read CLAUDE-HYBRID.md from .ai-team/teamlead/. You work in TWO channels: 1) Terminal with PM for detailed analysis, screenshots, code review 2) Chat (.ai-team/chat.md) for team coordination. ALWAYS duplicate important decisions from terminal to chat in format [HH:MM] [TEAMLEAD]: message. When PM shows screenshot or code, analyze in terminal then write task plan in chat for @backend @frontend @devops. You coordinate, never code yourself!"

pause