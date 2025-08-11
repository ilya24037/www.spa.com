@echo off
title Claude Code Launcher
color 0A

:MENU
cls
echo ================================================
echo           CLAUDE CODE LAUNCHER
echo ================================================
echo.
echo   QUICK LAUNCH:
echo   [1] Skip permissions (Fast mode)
echo   [2] Normal mode
echo   [3] Continue last conversation
echo.
echo   MODEL SELECTION:
echo   [4] Opus model
echo   [5] Sonnet model
echo.
echo   ADVANCED:
echo   [6] Debug mode
echo   [7] Connect to IDE
echo   [8] Resume previous session
echo.
echo   CONFIG:
echo   [9] Settings
echo   [0] Check updates
echo   [X] Exit
echo.
echo ================================================
echo.

set /p choice="Select option: "

if /i "%choice%"=="1" goto SKIP
if /i "%choice%"=="2" goto NORMAL
if /i "%choice%"=="3" goto CONTINUE
if /i "%choice%"=="4" goto OPUS
if /i "%choice%"=="5" goto SONNET
if /i "%choice%"=="6" goto DEBUG
if /i "%choice%"=="7" goto IDE
if /i "%choice%"=="8" goto RESUME
if /i "%choice%"=="9" goto CONFIG
if /i "%choice%"=="0" goto UPDATE
if /i "%choice%"=="x" exit

echo Invalid choice!
timeout /t 2 >nul
goto MENU

:SKIP
cls
echo Starting Claude Code (Skip permissions)...
start cmd /k claude --dangerously-skip-permissions
goto MENU

:NORMAL
cls
echo Starting Claude Code (Normal mode)...
start cmd /k claude
goto MENU

:CONTINUE
cls
echo Continuing last conversation...
start cmd /k claude -c
goto MENU

:OPUS
cls
echo Starting Claude Code with Opus model...
start cmd /k claude --model opus
goto MENU

:SONNET
cls
echo Starting Claude Code with Sonnet model...
start cmd /k claude --model sonnet
goto MENU

:DEBUG
cls
echo Starting Claude Code in Debug mode...
start cmd /k claude --debug
goto MENU

:IDE
cls
echo Starting Claude Code with IDE connection...
start cmd /k claude --ide
goto MENU

:RESUME
cls
echo Resuming previous session...
start cmd /k claude --resume
goto MENU

:CONFIG
cls
echo Opening configuration...
start cmd /k claude config
goto MENU

:UPDATE
cls
echo Checking for updates...
start cmd /k claude update
goto MENU