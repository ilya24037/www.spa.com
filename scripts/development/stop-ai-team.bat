@echo off
title Stop AI Team - SPA Platform
color 0C

echo ========================================
echo     STOPPING AI TEAM - SPA PLATFORM
echo ========================================
echo.

echo Closing AI Team windows...
echo.

REM Kill all PowerShell windows with AI team titles
taskkill /FI "WINDOWTITLE eq AI Backend Developer*" /F 2>nul
taskkill /FI "WINDOWTITLE eq AI Frontend Developer*" /F 2>nul
taskkill /FI "WINDOWTITLE eq AI DevOps Engineer*" /F 2>nul
taskkill /FI "WINDOWTITLE eq AI Team Chat Monitor*" /F 2>nul
taskkill /FI "WINDOWTITLE eq AI Team Control*" /F 2>nul

REM Kill any claude processes that might be hanging
taskkill /IM claude.exe /F 2>nul

echo.
echo Adding shutdown message to chat...
set TIME=%TIME:~0,5%
echo [%TIME%] [SYSTEM]: AI Team shutting down... >> .ai-team\chat.md

echo.
echo AI Team stopped successfully!
echo.
pause