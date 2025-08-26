@echo off
title Test AI Agents
color 0A

echo.
echo ========================================
echo    TESTING AI AGENTS STARTUP
echo ========================================
echo.

:: Kill old processes
echo Cleaning up old processes...
taskkill /F /FI "WINDOWTITLE eq *AI*Agent*" >nul 2>&1
taskkill /F /FI "WINDOWTITLE eq *claude*" >nul 2>&1
timeout /t 2 >nul

:: Start chat server
echo Starting chat server...
start "Chat Server" /min powershell -NoExit -ExecutionPolicy Bypass -File "chat-server-clean.ps1" -Port 8082
timeout /t 3 >nul

:: Clear chat file
echo Clearing old chat messages...
echo [%time:~0,5%] [SYSTEM]: Chat cleared for testing > .ai-team\chat.md
echo.

:: Start one agent for testing
echo Starting Backend agent for testing...
powershell -ExecutionPolicy Bypass -Command "& { & '.\ai-agent-manager.ps1' -Action 'start-backend' }"
timeout /t 3 >nul

:: Send test message
echo.
echo Sending test message to Backend...
echo [%time:~0,5%] [PM]: @backend проверка связи, ответь если слышишь >> .ai-team\chat.md

echo.
echo ========================================
echo    TEST STARTED
echo ========================================
echo.
echo Check http://localhost:8082 in browser
echo Look for Backend response in chat
echo.
echo If Backend responds - agents are working!
echo.
pause