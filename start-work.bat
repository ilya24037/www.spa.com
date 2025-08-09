@echo off
chcp 65001 >nul
title SPA Platform - Autostart with Cursor

cls
echo.
echo ========================================================
echo         SPA PLATFORM - AUTOSTART WORK DAY
echo ========================================================
echo.

:: 1. START PROJECT
echo [1/6] Starting Laravel + Vue...
start /min "SPA Dev Server" cmd /c "cd /d C:\www.spa.com && composer dev"
timeout /t 3 >nul

:: 2. START SMART CONTEXT WATCHER
echo [2/6] Starting smart context watcher...
start /min "Smart Context Watcher" powershell -WindowStyle Hidden -ExecutionPolicy Bypass -File "C:\www.spa.com\scripts\ai\smart-watcher.ps1"
timeout /t 2 >nul

:: 3. OPEN CURSOR
echo [3/6] Opening Cursor editor...
start "" "cursor" C:\www.spa.com
timeout /t 3 >nul

:: 4. GENERATE DAILY CONTEXT
echo [4/6] Generating smart AI context...
powershell -ExecutionPolicy Bypass -File "C:\www.spa.com\scripts\ai\daily-context.ps1"

:: 5. OPEN BROWSER
echo [5/6] Opening project in browser...
timeout /t 5 >nul
start "" "http://localhost:8000"

:: 6. START CLAUDE CODE (optional)
echo [6/6] Starting Claude Code...
cd /d C:\www.spa.com
start /min "Claude Code" cmd /k "npx claude-code"

:: FINAL MESSAGE
cls
echo.
echo ========================================================
echo                   ALL READY TO WORK!
echo ========================================================
echo.
echo What is running:
echo.
echo   [OK] Laravel server:     http://localhost:8000
echo   [OK] Vite (Vue):         http://localhost:5173
echo   [OK] Smart watcher:      Tracking changes
echo   [OK] Cursor:             Ready with AI
echo   [OK] Claude Code:        Available in background
echo   [OK] Browser:            Open with project
echo.
echo Daily context copied to clipboard!
echo.
echo How to work:
echo.
echo   1. In Cursor press Ctrl+K for AI commands
echo   2. In Claude Code start with: "Ultrathink, remember CLAUDE.md"
echo   3. Context already in clipboard - just Ctrl+V
echo.
echo Everything works automatically:
echo   - File changes are tracked
echo   - Context updates every 30 sec
echo   - Critical files are prioritized
echo.
echo Have a productive day! Everything is set up!
echo.
pause
exit