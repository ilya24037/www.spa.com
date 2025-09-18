@echo off
title Test Single Agent
color 0E

echo ========================================
echo     TEST SINGLE AGENT LAUNCH
echo ========================================
echo.

:: Move to ai-team directory
cd /d C:\www.spa.com\.ai-team

:: Test launching a simple agent
echo Testing Backend agent launch...
echo.

:: Try method 1: Direct Claude command
echo Method 1: Direct Claude command
echo ----------------------------------------
claude --dangerously-skip-permissions "You are Backend developer. Monitor chat.md file every 10 seconds. When you see @backend, respond with Laravel expertise. Format: [HH:MM] [BACKEND]: your response"

echo.
echo If agent started successfully, press Ctrl+C to stop and close this window.
pause