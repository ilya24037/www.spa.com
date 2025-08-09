@echo off
chcp 866 >nul
setlocal enabledelayedexpansion

:: AI Context Engineering Tool
title AI Context Tool

:MENU
cls
echo.
echo ========================================================
echo          AI CONTEXT ENGINEERING TOOL v2.0
echo ========================================================
echo.
echo OSNOVNYE KOMANDY / MAIN COMMANDS:
echo.
echo   [1] Quick Context  - Bystryy kontekst
echo   [2] Module Context - Kontekst po modulyu
echo   [3] Smart Context  - Umnyy kontekst
echo   [4] Inject Context - Inektsiya konteksta
echo   [5] Summarize      - Summarizatsiya
echo.
echo UTILITY / UTILITIES:
echo.
echo   [6] Clean Context  - Ochistka starykh faylov
echo   [7] Context Stats  - Statistika
echo   [8] Watch Mode     - Nablyudatel
echo   [9] Show CLAUDE.md - Pravila proekta
echo.
echo 5 HAKOV / 5 HACKS:
echo.
echo   [A] Last Word      - Poslednee slovo
echo   [B] Anchor Template - Strukturnyy yakor
echo   [C] Manual Summary - Ruchnaya summarizatsiya
echo.
echo   [0] Help / Spravka
echo   [Q] Exit / Vykhod
echo.
set /p choice=Vyberite / Choose: 

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
if /i "%choice%"=="0" goto HELP
if /i "%choice%"=="Q" goto END
goto MENU

:HELP
cls
echo ========================================================
echo                  SPRAVKA / HELP
echo ========================================================
echo.
echo CHTO ETO:
echo Instrument optimizatsii konteksta dlya raboty s AI
echo (Claude, ChatGPT, Cursor)
echo.
echo ZACHEM:
echo - Ekonomit tokeny (dengi na API)
echo - Uluchshaet fokus AI na vazhnom
echo - Avtomatiziruet rutinu
echo.
echo KAK RABOTAT:
echo 1. Vyberite komandu (1-9, A-C)
echo 2. Kontekst kopiruetsya v bufer
echo 3. Vstavte v Claude (Ctrl+V)
echo 4. Nachnite s: "Ultrathink, remember CLAUDE.md"
echo.
echo GLAVNYY SEKRET:
echo Vazhnoe VSEGDA razmeshchayte V KONTSE prompta!
echo (LLM luchshe zapominaet poslednyuyu informatsiyu)
echo.
pause
goto MENU

:QUICK_CONTEXT
cls
echo BYSTRYY KONTEKST / QUICK CONTEXT
echo ========================================================
echo.
echo Sobirayu informatsiyu / Collecting...
echo.

:: Create temp file
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set "CONTEXT_FILE=%TEMP%\ai_context_%datetime:~0,8%_%datetime:~8,6%.md"

:: Create context
echo # Quick Context > "%CONTEXT_FILE%"
echo Generated: %date% %time% >> "%CONTEXT_FILE%"
echo. >> "%CONTEXT_FILE%"
echo ## CONTEXT REMINDER: >> "%CONTEXT_FILE%"
echo - Project: SPA Platform (Laravel 12 + Vue 3 + TypeScript) >> "%CONTEXT_FILE%"
echo - Architecture: Backend DDD, Frontend FSD >> "%CONTEXT_FILE%"
echo - Directory: %cd% >> "%CONTEXT_FILE%"
echo. >> "%CONTEXT_FILE%"
echo ## Git Status: >> "%CONTEXT_FILE%"
git status --short 2>nul >> "%CONTEXT_FILE%"
echo. >> "%CONTEXT_FILE%"
echo ## CRITICAL (LAST WORD): >> "%CONTEXT_FILE%"
echo 1. ALWAYS use TypeScript >> "%CONTEXT_FILE%"
echo 2. FOLLOW FSD/DDD architecture >> "%CONTEXT_FILE%"
echo 3. NO logic in controllers >> "%CONTEXT_FILE%"
echo 4. PRESERVE compatibility >> "%CONTEXT_FILE%"

:: Copy to clipboard
type "%CONTEXT_FILE%" | clip

echo.
echo ========================================================
echo GOTOVO! / DONE!
echo Kontekst skopirovan / Context copied to clipboard
echo ========================================================
echo.
echo Chto delat / What to do:
echo 1. Open Claude/ChatGPT/Cursor
echo 2. Paste (Ctrl+V)
echo 3. Start: "Ultrathink, remember CLAUDE.md"
echo.
pause
goto MENU

:MODULE_CONTEXT
cls
echo KONTEKST MODULYA / MODULE CONTEXT
echo ========================================================
echo.
echo Vyberite modul / Choose module:
echo.
echo   [1] booking  - Bronirovanie
echo   [2] masters  - Mastera
echo   [3] ads      - Obyavleniya
echo   [4] media    - Media fayly
echo   [5] search   - Poisk
echo   [6] routes   - Marshruty
echo.
set /p module=Nomer / Number: 

set MODULE_NAME=
if "%module%"=="1" set MODULE_NAME=booking
if "%module%"=="2" set MODULE_NAME=masters
if "%module%"=="3" set MODULE_NAME=ads
if "%module%"=="4" set MODULE_NAME=media
if "%module%"=="5" set MODULE_NAME=search
if "%module%"=="6" set MODULE_NAME=routes

if "%MODULE_NAME%"=="" (
    echo Nevernyy vybor / Wrong choice!
    pause
    goto MODULE_CONTEXT
)

echo.
echo Sobirayu kontekst dlya / Collecting context for: %MODULE_NAME%
powershell -ExecutionPolicy Bypass -File scripts\ai\create-context-pack.ps1 -Module %MODULE_NAME%
echo.
echo Gotovo / Done!
pause
goto MENU

:SMART_CONTEXT
cls
echo UMNYY KONTEKST / SMART CONTEXT
echo ========================================================
echo.
echo Sozdayu optimizirovannyy kontekst...
echo Creating optimized context...
echo.

echo # Smart Context with Prioritization | clip
echo. | clip
echo ## 1. CRITICAL (highest priority): | clip
echo - CLAUDE.md rules | clip
echo - Current task requirements | clip
echo. | clip
echo ## 2. IMPORTANT (context): | clip
echo - Related files | clip
echo - Recent changes | clip
echo. | clip
echo ## 3. REFERENCE (examples): | clip
echo - Similar implementations | clip
echo. | clip
echo ## CRITICAL REMINDERS (LAST WORD): | clip
echo - Architecture: FSD + DDD | clip
echo - TypeScript required | clip
echo - Test before commit | clip

echo.
echo Kontekst sozdan i skopirovan!
echo Context created and copied!
echo.
pause
goto MENU

:INJECT_CONTEXT
cls
echo INEKTSIYA KONTEKSTA / CONTEXT INJECTION
echo ========================================================
echo.
echo Dlya dlinnykh dialogov / For long dialogues
echo.

echo Remember: Laravel 12, Vue 3, TypeScript required, FSD/DDD architecture, preserve compatibility | clip

echo.
echo Tekst skopirovan / Text copied!
echo.
echo Vstavte v dialog / Paste in dialogue
echo.
pause
goto MENU

:SUMMARIZE_CONTEXT
cls
echo SUMMARIZATSIYA / SUMMARIZATION
echo ========================================================
echo.
echo Komandy / Commands:
echo.
echo [1] Progress / Progress
echo [2] Trebovaniya / Requirements
echo [3] Ochistka / Cleanup
echo.
set /p sum_choice=Vybor (1-3) / Choose (1-3): 

if "%sum_choice%"=="1" echo Summarize what is done in 3-5 points | clip
if "%sum_choice%"=="2" echo Highlight 3 key requirements to remember | clip
if "%sum_choice%"=="3" echo Briefly summarize key decisions, remove details | clip

echo.
echo Komanda skopirovana / Command copied!
pause
goto MENU

:CLEAN_CONTEXT
cls
echo OCHISTKA KONTEKSTA / CLEAN CONTEXT
echo ========================================================
echo.

for /f %%i in ('dir /b /s "storage\ai-sessions\*.md" 2^>nul ^| find /c /v ""') do set FILE_COUNT=%%i
echo Naydeno faylov / Found files: %FILE_COUNT%
echo.
echo [1] Udalit starshe 7 dney / Delete older 7 days
echo [2] Udalit starshe 30 dney / Delete older 30 days
echo [3] Statistika / Statistics
echo [4] Otmena / Cancel
echo.
set /p clean_choice=Vybor / Choice: 

if "%clean_choice%"=="1" (
    powershell -Command "Get-ChildItem -Path 'storage\ai-sessions' -Filter '*.md' -Recurse | Where-Object {$_.LastWriteTime -lt (Get-Date).AddDays(-7)} | Remove-Item -Force"
    echo Udaleno / Deleted!
)
if "%clean_choice%"=="2" (
    powershell -Command "Get-ChildItem -Path 'storage\ai-sessions' -Filter '*.md' -Recurse | Where-Object {$_.LastWriteTime -lt (Get-Date).AddDays(-30)} | Remove-Item -Force"
    echo Udaleno / Deleted!
)
if "%clean_choice%"=="3" (
    echo.
    powershell -Command "Get-ChildItem -Path 'storage\ai-sessions' -Filter '*.md' -Recurse | Group-Object {$_.LastWriteTime.Date} | Sort-Object Name -Descending | Select-Object -First 10 Name, Count"
)

pause
goto MENU

:CONTEXT_STATS
cls
echo STATISTIKA KONTEKSTA / CONTEXT STATISTICS
echo ========================================================
echo.

powershell -Command "$files = Get-ChildItem -Path 'storage\ai-sessions' -Filter '*.md' -Recurse -ErrorAction SilentlyContinue; if ($files) { $count = $files.Count; $size = ($files | Measure-Object Length -Sum).Sum/1MB; Write-Host \"Files/Faylov: $count\"; Write-Host \"Size/Razmer: $([math]::Round($size,2)) MB\" }"

echo.
pause
goto MENU

:WATCH_MODE
cls
echo REZHIM NABLYUDENIYA / WATCH MODE
echo ========================================================
echo.
echo Zapuskayu nablyudatel / Starting watcher...
echo.
start "Context Watcher" cmd /c start-context-watch.bat
echo.
echo Nablyudatel zapushchen / Watcher started
echo.
pause
goto MENU

:SHOW_CLAUDE
cls
echo CLAUDE.md - PRAVILA PROEKTA / PROJECT RULES
echo ========================================================
echo.
if exist CLAUDE.md (
    type CLAUDE.md | more
) else (
    echo Fayl ne nayden / File not found: CLAUDE.md
)
echo.
pause
goto MENU

:LAST_WORD
cls
echo HAK POSLEDNEE SLOVO / LAST WORD HACK
echo ========================================================
echo.
echo PRINTSIP / PRINCIPLE:
echo LLM luchshe zapominaet v KONTSE prompta
echo LLM remembers better at END of prompt
echo.
echo PRIMER / EXAMPLE:
echo.
echo Add method to class. CRITICAL: DO NOT delete existing code! | clip
echo.
echo Primer skopirovan / Example copied!
echo.
pause
goto MENU

:ANCHOR_TEMPLATE
cls
echo STRUKTURNYY YAKOR / STRUCTURAL ANCHOR
echo ========================================================
echo.
echo Shablon / Template:
echo.
echo 1. Read existing code | clip
echo 2. Identify dependencies | clip
echo 3. Create TypeScript interfaces | clip
echo 4. Implement logic | clip
echo 5. Add error handling | clip
echo 6. Check compatibility | clip
echo IMPORTANT: Follow template! | clip
echo.
echo Shablon skopirovan / Template copied!
echo.
pause
goto MENU

:MANUAL_SUMMARY
cls
echo RUCHNAYA SUMMARIZATSIYA / MANUAL SUMMARIZATION
echo ========================================================
echo.
echo [1] Summarizatsiya progressa / Progress summary
echo [2] Klyuchevye trebovaniya / Key requirements
echo [3] Ochistka dialoga / Cleanup dialogue
echo.
set /p sum_choice=Vybor (1-3) / Choose (1-3): 

if "%sum_choice%"=="1" echo Summarize progress in 3-5 points | clip
if "%sum_choice%"=="2" echo List 3 key requirements | clip
if "%sum_choice%"=="3" echo Briefly summarize, remove details | clip

echo.
echo Skopirovano / Copied!
pause
goto MENU

:END
echo.
echo ========================================================
echo Do svidaniya! / Goodbye!
echo ========================================================
timeout /t 2 >nul
endlocal
exit /b