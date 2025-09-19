@echo off
title Claude Code Launcher v2.0
color 0A

:MENU
cls
echo ================================================================
echo                    CLAUDE CODE LAUNCHER v2.0
echo ================================================================
echo.
echo  MAIN MODES:
echo  [1] Normal launch
echo  [2] Fast mode (skip permissions)
echo  [3] Continue last conversation
echo  [4] Resume specific conversation
echo.
echo  MODELS:
echo  [5] Sonnet model (default)
echo  [6] Opus model (powerful)
echo  [7] Haiku model (fast)
echo.
echo  SPECIAL MODES:
echo  [8] Debug mode
echo  [9] Plan mode (planning only)
echo  [P] Print mode (for scripts)
echo  [I] Connect to IDE
echo.
echo  MANAGEMENT:
echo  [C] Configuration
echo  [M] MCP servers
echo  [D] Diagnostics (doctor)
echo  [U] Check updates
echo  [V] Version info
echo  [H] Help
echo.
echo  [X] Exit
echo.
echo ================================================================
echo.

set /p choice="Select option: "

if /i "%choice%"=="1" goto NORMAL
if /i "%choice%"=="2" goto SKIP_PERMISSIONS
if /i "%choice%"=="3" goto CONTINUE
if /i "%choice%"=="4" goto RESUME
if /i "%choice%"=="5" goto SONNET
if /i "%choice%"=="6" goto OPUS
if /i "%choice%"=="7" goto HAIKU
if /i "%choice%"=="8" goto DEBUG
if /i "%choice%"=="9" goto PLAN_MODE
if /i "%choice%"=="p" goto PRINT_MODE
if /i "%choice%"=="i" goto IDE
if /i "%choice%"=="c" goto CONFIG
if /i "%choice%"=="m" goto MCP
if /i "%choice%"=="d" goto DOCTOR
if /i "%choice%"=="u" goto UPDATE
if /i "%choice%"=="v" goto VERSION
if /i "%choice%"=="h" goto HELP
if /i "%choice%"=="x" exit

echo.
echo [!] Invalid choice! Please try again...
timeout /t 2 >nul
goto MENU

:NORMAL
cls
echo ================================================================
echo  Starting Claude Code (normal mode)
echo ================================================================
echo.
echo  File access permissions will be requested
echo.
echo  Starting...
echo.
cd /d "C:\www.spa.com"
start cmd /k claude
pause
goto MENU

:SKIP_PERMISSIONS
cls
echo ================================================================
echo  Starting Claude Code (fast mode)
echo ================================================================
echo.
echo  WARNING: Skipping all permission checks
echo  Use only in trusted directories!
echo.
echo  Starting...
echo.
cd /d "C:\www.spa.com"
start cmd /k claude --dangerously-skip-permissions
pause
goto MENU

:CONTINUE
cls
echo ================================================================
echo  Continue last conversation
echo ================================================================
echo.
echo  Restoring last session...
echo.
cd /d "C:\www.spa.com"
start cmd /k claude -c
pause
goto MENU

:RESUME
cls
echo ================================================================
echo  Resume specific conversation
echo ================================================================
echo.
echo  Interactive session list will open
echo.
cd /d "C:\www.spa.com"
start cmd /k claude -r
pause
goto MENU

:SONNET
cls
echo ================================================================
echo  Claude Code with Sonnet model
echo ================================================================
echo.
echo  Balanced model (default)
echo  Optimal for most tasks
echo.
cd /d "C:\www.spa.com"
start cmd /k claude --model sonnet
pause
goto MENU

:OPUS
cls
echo ================================================================
echo  Claude Code with Opus model
echo ================================================================
echo.
echo  Most powerful model
echo  Best for complex architectural tasks
echo.
cd /d "C:\www.spa.com"
start cmd /k claude --model opus
pause
goto MENU

:HAIKU
cls
echo ================================================================
echo  Claude Code with Haiku model
echo ================================================================
echo.
echo  Fast model
echo  Good for simple tasks and quick edits
echo.
cd /d "C:\www.spa.com"
start cmd /k claude --model haiku
pause
goto MENU

:DEBUG
cls
echo ================================================================
echo  Debug mode
echo ================================================================
echo.
echo  Detailed debug information enabled
echo  Useful for troubleshooting
echo.
cd /d "C:\www.spa.com"
start cmd /k claude --debug
pause
goto MENU

:PLAN_MODE
cls
echo ================================================================
echo  Plan mode
echo ================================================================
echo.
echo  Claude will only plan actions
echo  without executing them automatically
echo.
cd /d "C:\www.spa.com"
start cmd /k claude --permission-mode plan
pause
goto MENU

:PRINT_MODE
cls
echo ================================================================
echo  Print mode for scripts
echo ================================================================
echo.
set /p prompt="Enter your prompt: "
echo.
echo Executing...
echo.
cd /d "C:\www.spa.com"
claude -p "%prompt%"
echo.
pause
goto MENU

:IDE
cls
echo ================================================================
echo  Connect to IDE
echo ================================================================
echo.
echo  Automatic connection to available IDE
echo.
cd /d "C:\www.spa.com"
start cmd /k claude --ide
pause
goto MENU

:CONFIG
cls
echo ================================================================
echo  Claude Code Configuration
echo ================================================================
echo.
echo  Available commands:
echo  - claude config set -g theme dark
echo  - claude config set -g autoSave true
echo  - claude config list
echo.
cd /d "C:\www.spa.com"
start cmd /k claude config
pause
goto MENU

:MCP
cls
echo ================================================================
echo  MCP Server Management
echo ================================================================
echo.
echo  Model Context Protocol servers for extended functionality
echo.
cd /d "C:\www.spa.com"
start cmd /k claude mcp
pause
goto MENU

:DOCTOR
cls
echo ================================================================
echo  Claude Code Diagnostics
echo ================================================================
echo.
echo  Checking status and configuration
echo.
cd /d "C:\www.spa.com"
claude doctor
echo.
pause
goto MENU

:UPDATE
cls
echo ================================================================
echo  Check for Updates
echo ================================================================
echo.
echo  Checking for new versions...
echo.
cd /d "C:\www.spa.com"
claude update
echo.
pause
goto MENU

:VERSION
cls
echo ================================================================
echo  Claude Code Version
echo ================================================================
echo.
cd /d "C:\www.spa.com"
claude --version
echo.
pause
goto MENU

:HELP
cls
echo ================================================================
echo  Claude Code Help
echo ================================================================
echo.
cd /d "C:\www.spa.com"
claude --help
echo.
pause
goto MENU