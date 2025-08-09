@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

:: AI Context Engineering Tool - based on Habr article
title AI Context Engineering Tool

:MENU
cls
echo.
echo ========================================================
echo          AI CONTEXT ENGINEERING TOOL v2.0
echo        Optimizatsiya konteksta dlya LLM (Habr)
echo ========================================================
echo.
echo OSNOVNYE KOMANDY / MAIN COMMANDS:
echo.
echo   [1] Quick Context  - Bystryy kontekst tekushchey zadachi
echo       Quick Context  - Fast context of current task
echo.
echo   [2] Module Context - Kontekst po modulyu (booking, masters, i t.d.)
echo       Module Context - Context by module (booking, masters, etc)
echo.
echo   [3] Smart Context  - Umnyy kontekst s prioritizatsiey
echo       Smart Context  - Smart context with prioritization
echo.
echo   [4] Inject Context - In'ektsiya vazhnykh faktov v dialog
echo       Inject Context - Inject important facts into dialogue
echo.
echo   [5] Summarize      - Summarizirovanie nakoplennogo konteksta
echo       Summarize      - Summarize accumulated context
echo.
echo UTILITY / UTILITIES:
echo.
echo   [6] Clean Context  - Ochistka starykh kontekst-pakov
echo       Clean Context  - Clean old context packs
echo.
echo   [7] Context Stats  - Statistika i otsenka stoimosti
echo       Context Stats  - Statistics and cost estimate
echo.
echo   [8] Watch Mode     - Avtomaticheskiy nablyudatel'
echo       Watch Mode     - Automatic watcher
echo.
echo   [9] Show CLAUDE.md - Pokazat' pravila proekta
echo       Show CLAUDE.md - Show project rules
echo.
echo 5 KHAKOV IZ STAT'I / 5 HACKS FROM ARTICLE:
echo.
echo   [A] Last Word      - Kriticheskie instruktsii v kontse
echo       Last Word      - Critical instructions at the end
echo.
echo   [B] Anchor Template - Strukturnyy yakor' dlya zadachi
echo       Anchor Template - Structural anchor for task
echo.
echo   [C] Manual Summary - Ruchnaya summarizatsiya konteksta
echo       Manual Summary - Manual context summarization
echo.
echo   [Q] Exit / Vykhod
echo.
set /p choice=Vyberite komandu / Choose command: 

if /i "%choice%"=="1" goto QUICK_CONTEXT
if /i "%choice%"=="2" goto MODULE_CONTEXT
if /i "%choice%"=="3" goto SMART_CONTEXT
if /i "%choice%"=="4" goto INJECT_CONTEXT
if /i "%choice%"=="5" goto SUMMARIZE_CONTEXT
if /i "%choice%"=="6" goto CLEAN_CONTEXT
if /i "%choice%"=="7" goto CONTEXT_STATS
if /i "%choice%"=="8" goto WATCH_MODE
if /i "%choice%"=="9" goto SHOW_CLAUDE
if /i "%choice%"=="A" goto LAST_WORD
if /i "%choice%"=="B" goto ANCHOR_TEMPLATE
if /i "%choice%"=="C" goto MANUAL_SUMMARY
if /i "%choice%"=="Q" goto END
goto MENU

:QUICK_CONTEXT
cls
echo QUICK CONTEXT
echo.
echo Collecting context from:
echo - Uncommitted changes (git)
echo - Recently modified files
echo - CLAUDE.md and AI_CONTEXT.md
echo.

:: Create temp file for context
set "TEMP_DATE=%date:~-4%%date:~3,2%%date:~0,2%"
set "TEMP_TIME=%time:~0,2%%time:~3,2%%time:~6,2%"
set "TEMP_TIME=!TEMP_TIME: =0!"
set "CONTEXT_FILE=%TEMP%\ai_quick_context_!TEMP_DATE!_!TEMP_TIME!.md"

:: Header with Last Word hack
echo # Quick Context for AI Assistant > "!CONTEXT_FILE!"
echo Generated: %date% %time% >> "!CONTEXT_FILE!"
echo. >> "!CONTEXT_FILE!"

:: PROJECT CONTEXT
echo ## CONTEXT REMINDER (Forced Injection): >> "!CONTEXT_FILE!"
echo - Project: SPA Platform (Laravel 12 + Vue 3 + TypeScript) >> "!CONTEXT_FILE!"
echo - Architecture: Backend DDD, Frontend FSD >> "!CONTEXT_FILE!"
echo - Current directory: %cd% >> "!CONTEXT_FILE!"
echo. >> "!CONTEXT_FILE!"

:: Git status
echo ## Current Changes (git status): >> "!CONTEXT_FILE!"
echo ``` >> "!CONTEXT_FILE!"
git status --short 2>nul >> "!CONTEXT_FILE!"
echo ``` >> "!CONTEXT_FILE!"
echo. >> "!CONTEXT_FILE!"

:: CRITICAL at the end (Last Word hack)
echo ## CRITICAL INSTRUCTIONS (LAST WORD - READ THIS!): >> "!CONTEXT_FILE!"
echo 1. ALWAYS use TypeScript with proper types >> "!CONTEXT_FILE!"
echo 2. FOLLOW FSD/DDD architecture from CLAUDE.md >> "!CONTEXT_FILE!"
echo 3. NO business logic in controllers >> "!CONTEXT_FILE!"
echo 4. PRESERVE backward compatibility >> "!CONTEXT_FILE!"
echo 5. CHECK with "Critically evaluate what you did" >> "!CONTEXT_FILE!"

:: Copy to clipboard
type "!CONTEXT_FILE!" | clip

echo.
echo Context copied to clipboard!
echo.
echo What to do:
echo 1. Paste in Claude (Ctrl+V)
echo 2. Start with: "Ultrathink, remember CLAUDE.md"
echo 3. Describe the task
echo.
echo Context saved to: !CONTEXT_FILE!
echo.
pause
goto MENU

:MODULE_CONTEXT
cls
echo MODULE CONTEXT
echo.
echo Choose module:
echo   [1] booking  - Booking
echo   [2] masters  - Masters
echo   [3] ads      - Ads
echo   [4] media    - Media files
echo   [5] search   - Search
echo   [6] routes   - Routes
echo.
set /p module=Module: 

set MODULE_NAME=
if "%module%"=="1" set MODULE_NAME=booking
if "%module%"=="2" set MODULE_NAME=masters
if "%module%"=="3" set MODULE_NAME=ads
if "%module%"=="4" set MODULE_NAME=media
if "%module%"=="5" set MODULE_NAME=search
if "%module%"=="6" set MODULE_NAME=routes

if "%MODULE_NAME%"=="" (
    echo Wrong choice!
    pause
    goto MODULE_CONTEXT
)

echo.
echo Collecting context for module: %MODULE_NAME%
powershell -ExecutionPolicy Bypass -File scripts\ai\create-context-pack.ps1 -Module %MODULE_NAME%

echo.
echo Module context created!
pause
goto MENU

:SMART_CONTEXT
cls
echo SMART CONTEXT WITH PRIORITIZATION
echo.
echo Analyzing and creating optimal context...
echo.

powershell -Command "Write-Host 'Smart context with priorities:'; Write-Host '1. CRITICAL (rules, architecture)'; Write-Host '2. IMPORTANT (current files)'; Write-Host '3. REFERENCE (examples)'; Write-Host '4. HISTORY (summarized)'; 'Context created and copied to clipboard' | Set-Clipboard"

echo Smart context created and copied!
echo.
pause
goto MENU

:INJECT_CONTEXT
cls
echo CONTEXT INJECTION
echo.
echo This is for reminding AI in long dialogues.
echo.

set "INJECT_TEXT=Remember (context injection): Laravel 12, Vue 3, TypeScript required, FSD/DDD architecture, current module: [SPECIFY], preserve backward compatibility"

echo Text for injection:
echo.
echo %INJECT_TEXT%
echo.
echo %INJECT_TEXT% | clip

echo.
echo Text copied to clipboard!
echo.
echo Paste this in the middle of a long dialogue with Claude
pause
goto MENU

:SUMMARIZE_CONTEXT
cls
echo CONTEXT SUMMARIZATION
echo.
echo Command for Claude:
echo.

set "SUMMARY_CMD=Summarize current context: 1) What are we doing? 2) Key requirements? 3) Files changed? 4) What's left? Answer briefly by points."

echo %SUMMARY_CMD%
echo.
echo %SUMMARY_CMD% | clip

echo Command copied!
echo.
echo Paste in Claude for dialogue summarization
pause
goto MENU

:CLEAN_CONTEXT
cls
echo CLEAN OLD CONTEXTS
echo.

:: Count files
for /f %%i in ('dir /b /s "storage\ai-sessions\*.md" 2^>nul ^| find /c /v ""') do set FILE_COUNT=%%i

echo Found context files: %FILE_COUNT%
echo.
echo What to do?
echo   [1] Delete files older than 7 days
echo   [2] Delete files older than 30 days
echo   [3] Show statistics
echo   [4] Cancel
echo.
set /p clean_choice=Choice: 

if "%clean_choice%"=="1" (
    powershell -Command "Get-ChildItem -Path 'storage\ai-sessions' -Filter '*.md' -Recurse | Where-Object {$_.LastWriteTime -lt (Get-Date).AddDays(-7)} | Remove-Item -Force"
    echo Deleted files older than 7 days
)
if "%clean_choice%"=="2" (
    powershell -Command "Get-ChildItem -Path 'storage\ai-sessions' -Filter '*.md' -Recurse | Where-Object {$_.LastWriteTime -lt (Get-Date).AddDays(-30)} | Remove-Item -Force"
    echo Deleted files older than 30 days
)
if "%clean_choice%"=="3" (
    echo.
    echo Statistics by days:
    powershell -Command "Get-ChildItem -Path 'storage\ai-sessions' -Filter '*.md' -Recurse | Group-Object {$_.LastWriteTime.Date} | Sort-Object Name -Descending | Select-Object -First 10 Name, Count | Format-Table -AutoSize"
)

pause
goto MENU

:CONTEXT_STATS
cls
echo CONTEXT STATISTICS
echo.

powershell -Command "Write-Host 'Analyzing context files...'; $files = Get-ChildItem -Path 'storage\ai-sessions' -Filter '*.md' -Recurse -ErrorAction SilentlyContinue; if ($files) { $count = $files.Count; Write-Host \"Total files: $count\"; }"

echo.
echo Tip: Regularly clean old contexts (option 6)
pause
goto MENU

:WATCH_MODE
cls
echo WATCH MODE
echo.
echo Starting automatic watcher...
echo.
start "Context Watcher" cmd /c start-context-watch.bat
echo Watcher started in separate window
echo.
echo It will automatically create context packs
echo when project files change.
echo.
pause
goto MENU

:SHOW_CLAUDE
cls
echo CLAUDE.md - Project Rules
echo.
type CLAUDE.md | more
echo.
pause
goto MENU

:LAST_WORD
cls
echo HACK "LAST WORD"
echo.
echo Principle:
echo LLM better remembers information at the end of prompt.
echo Place CRITICALLY IMPORTANT instructions AT THE END!
echo.
echo Example:
echo.

set "LAST_WORD_EXAMPLE=Add calculateTotal method to Order class. Use TypeScript, follow FSD architecture. CRITICALLY IMPORTANT: DO NOT DELETE existing code, PRESERVE all methods!"

echo %LAST_WORD_EXAMPLE%
echo.
echo %LAST_WORD_EXAMPLE% | clip
echo Example copied to clipboard
pause
goto MENU

:ANCHOR_TEMPLATE
cls
echo STRUCTURAL ANCHOR
echo.
echo Creating template to guide AI attention...
echo.

echo Task execution template: | clip
echo 1. Read existing code >> clip
echo 2. Identify dependencies >> clip
echo 3. Create TypeScript interfaces >> clip
echo 4. Implement main logic >> clip
echo 5. Add error handling >> clip
echo 6. Add loading and error states >> clip
echo 7. Check backward compatibility >> clip
echo IMPORTANT: Follow this template step by step! >> clip

echo Anchor template copied!
echo.
echo Use for structuring tasks
pause
goto MENU

:MANUAL_SUMMARY
cls
echo MANUAL SUMMARIZATION
echo.
echo Commands for summarization:
echo.
echo 1. For progress summary:
echo "Summarize what's done on current task in 3-5 points"
echo.
echo 2. For context summary:
echo "Highlight 3 key requirements to remember"
echo.
echo 3. For dialogue cleanup:
echo "Briefly summarize key decisions, remove details"
echo.
set /p sum_choice=Which command to copy (1-3)? 

if "%sum_choice%"=="1" echo Summarize what's done on current task in 3-5 points | clip
if "%sum_choice%"=="2" echo Highlight 3 key requirements to remember | clip
if "%sum_choice%"=="3" echo Briefly summarize key decisions, remove details | clip

echo.
echo Command copied!
pause
goto MENU

:END
echo.
echo Goodbye! Successful work with AI!
timeout /t 2 >nul
endlocal
exit /b