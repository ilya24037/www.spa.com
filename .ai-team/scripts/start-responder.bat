@echo off
title AI Team Auto Responder
color 0A

echo ========================================
echo     AI TEAM AUTO RESPONDER
echo ========================================
echo.
echo Starting automatic chat responder...
echo This will monitor chat.md and respond to @mentions
echo.

cd /d "C:\www.spa.com\.ai-team\scripts"

powershell -ExecutionPolicy Bypass -File "ai-team-responder.ps1"

pause