@echo off
chcp 866 >nul
setlocal enabledelayedexpansion

:: Universal AI Context Tool for Claude Code & Cursor
title AI Context Universal - Claude Code + Cursor

:: Opredelyaem aktivnyy redaktor
set "ACTIVE_EDITOR=UNKNOWN"
set "CLAUDE_ACTIVE=NO"
set "CURSOR_ACTIVE=NO"

:: Proveryaem processy
tasklist /FI "IMAGENAME eq claude.exe" 2>NUL | find /I "claude.exe" >NUL
if not errorlevel 1 (
    set "CLAUDE_ACTIVE=YES"
    set "ACTIVE_EDITOR=CLAUDE"
)

tasklist /FI "IMAGENAME eq Cursor.exe" 2>NUL | find /I "Cursor.exe" >NUL
if not errorlevel 1 (
    set "CURSOR_ACTIVE=YES"
    if "%CLAUDE_ACTIVE%"=="YES" (
        set "ACTIVE_EDITOR=BOTH"
    ) else (
        set "ACTIVE_EDITOR=CURSOR"
    )
)

:: Proveryaem okna
powershell -Command "Get-Process | Where-Object {$_.MainWindowTitle -like '*Claude*'}" 2>nul | find "Claude" >nul
if not errorlevel 1 set "CLAUDE_ACTIVE=YES"

powershell -Command "Get-Process | Where-Object {$_.MainWindowTitle -like '*Cursor*'}" 2>nul | find "Cursor" >nul
if not errorlevel 1 set "CURSOR_ACTIVE=YES"

:MAIN_MENU
cls
echo ========================================================
echo      AI CONTEXT UNIVERSAL - Claude Code + Cursor v3.0
echo ========================================================
echo.
echo  Aktivnye redaktory / Active Editors:
echo  - Claude Code: %CLAUDE_ACTIVE%
echo  - Cursor:      %CURSOR_ACTIVE%
echo  - Rezhim:      %ACTIVE_EDITOR%
echo.
echo --------------------------------------------------------
echo  OSNOVNYE KOMANDY / MAIN COMMANDS
echo --------------------------------------------------------
echo  [1] Quick Context   - Bystryy kontekst dlya oboikh
echo  [2] Watch Mode      - Avtonablyudatel (30 sek)
echo  [3] Smart Context   - Umnyy kontekst s prioritetami
echo  [4] Module Context  - Kontekst konkretnogo modulya
echo  [5] Project State   - Polnoe sostoyanie proekta
echo --------------------------------------------------------
echo  SPETSIALNYE REZHIMY / SPECIAL MODES
echo --------------------------------------------------------
echo  [C] Claude Mode     - Optimizatsiya dlya Claude Code
echo  [U] Cursor Mode     - Optimizatsiya dlya Cursor
echo  [B] Both Mode       - Universalnyy format
echo --------------------------------------------------------
echo  UTILITY / UTILITIES
echo --------------------------------------------------------
echo  [6] Clean Sessions  - Ochistka starykh sessiy
echo  [7] Statistics      - Statistika ispolzovaniya
echo  [8] Settings        - Nastroyki
echo  [9] Help            - Spravka
echo  [0] Exit            - Vykhod
echo --------------------------------------------------------
echo.
set /p choice=Vyberite komandu / Choose command: 

if "%choice%"=="1" goto QUICK_CONTEXT
if "%choice%"=="2" goto WATCH_MODE
if "%choice%"=="3" goto SMART_CONTEXT
if "%choice%"=="4" goto MODULE_CONTEXT
if "%choice%"=="5" goto PROJECT_STATE
if /i "%choice%"=="C" goto CLAUDE_MODE
if /i "%choice%"=="U" goto CURSOR_MODE
if /i "%choice%"=="B" goto BOTH_MODE
if "%choice%"=="6" goto CLEAN_SESSIONS
if "%choice%"=="7" goto STATISTICS
if "%choice%"=="8" goto SETTINGS
if "%choice%"=="9" goto HELP
if "%choice%"=="0" goto END
goto MAIN_MENU

:QUICK_CONTEXT
cls
echo ========================================================
echo       BYSTRYY KONTEKST / QUICK CONTEXT
echo ========================================================
echo.
echo Sobirayu kontekst dlya: %ACTIVE_EDITOR%
echo.

:: Sozdaem papku esli net
if not exist "storage\ai-sessions" mkdir "storage\ai-sessions"

:: Generiruem imya fayla
for /f %%I in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set datetime=%%I
set "SESSION_FILE=storage\ai-sessions\context_%datetime%.md"

:: Sozdaem kontekst v zavisimosti ot redaktora
if "%ACTIVE_EDITOR%"=="CLAUDE" goto CREATE_CLAUDE_CONTEXT
if "%ACTIVE_EDITOR%"=="CURSOR" goto CREATE_CURSOR_CONTEXT
if "%ACTIVE_EDITOR%"=="BOTH" goto CREATE_BOTH_CONTEXT

:CREATE_CLAUDE_CONTEXT
echo # Context for Claude Code > "%SESSION_FILE%"
echo Generated: %date% %time% >> "%SESSION_FILE%"
echo Editor: Claude Code >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"
echo ## CRITICAL REMINDERS (pomni kontekstnuyu inzheneriyu iz CLAUDE.md): >> "%SESSION_FILE%"
echo - Project: SPA Platform (Laravel 12 + Vue 3 + TypeScript) >> "%SESSION_FILE%"
echo - Architecture: Backend DDD, Frontend FSD >> "%SESSION_FILE%"
echo - TypeScript obyazatelen vezde >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"
goto COMMON_CONTEXT

:CREATE_CURSOR_CONTEXT
echo # Context for Cursor > "%SESSION_FILE%"
echo Generated: %date% %time% >> "%SESSION_FILE%"
echo Editor: Cursor >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"
echo ## Project Configuration: >> "%SESSION_FILE%"
echo ```json >> "%SESSION_FILE%"
echo { >> "%SESSION_FILE%"
echo   "project": "SPA Platform", >> "%SESSION_FILE%"
echo   "stack": ["Laravel 12", "Vue 3", "TypeScript", "Inertia.js"], >> "%SESSION_FILE%"
echo   "architecture": { >> "%SESSION_FILE%"
echo     "backend": "Domain-Driven Design", >> "%SESSION_FILE%"
echo     "frontend": "Feature-Sliced Design" >> "%SESSION_FILE%"
echo   } >> "%SESSION_FILE%"
echo } >> "%SESSION_FILE%"
echo ``` >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"
goto COMMON_CONTEXT

:CREATE_BOTH_CONTEXT
echo # Universal Context (Claude Code + Cursor) > "%SESSION_FILE%"
echo Generated: %date% %time% >> "%SESSION_FILE%"
echo Editors: Both >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"
echo ## Quick Reference: >> "%SESSION_FILE%"
echo - **Claude Code**: Use "Ultrathink, remember CLAUDE.md" >> "%SESSION_FILE%"
echo - **Cursor**: Use @codebase for context >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"

:COMMON_CONTEXT
echo ## Current Directory: >> "%SESSION_FILE%"
echo %cd% >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"

echo ## Git Status: >> "%SESSION_FILE%"
git status --porcelain 2>nul >> "%SESSION_FILE%"
if errorlevel 1 echo No git repository >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"

echo ## Recent Changes (last 10 minutes): >> "%SESSION_FILE%"
powershell -Command "Get-ChildItem -Recurse -File -ErrorAction SilentlyContinue | Where-Object {$_.LastWriteTime -gt (Get-Date).AddMinutes(-10) -and $_.FullName -notlike '*node_modules*' -and $_.FullName -notlike '*.git*'} | Select-Object -First 10 FullName, LastWriteTime | Format-Table -AutoSize" >> "%SESSION_FILE%" 2>nul
echo. >> "%SESSION_FILE%"

echo ## Active Development Servers: >> "%SESSION_FILE%"
netstat -an | findstr "3000 5173 8000 8080" >nul 2>&1
if not errorlevel 1 (
    echo Detected ports: >> "%SESSION_FILE%"
    netstat -an | findstr "3000 5173 8000 8080" >> "%SESSION_FILE%"
) else (
    echo No dev servers running >> "%SESSION_FILE%"
)
echo. >> "%SESSION_FILE%"

:: Spetsifichnye dlya redaktora sektsii
if "%ACTIVE_EDITOR%"=="CLAUDE" (
    echo ## CONTEXT INJECTION dlya dlinnykh dialogov: >> "%SESSION_FILE%"
    echo Pomni: Laravel 12, Vue 3, TypeScript obyazatelen, FSD/DDD arkhitektura >> "%SESSION_FILE%"
    echo. >> "%SESSION_FILE%"
    echo ## STRUCTURAL ANCHOR shablon komponenta: >> "%SESSION_FILE%"
    echo 1. TypeScript interfeysy dlya props >> "%SESSION_FILE%"
    echo 2. Composables dlya logiki >> "%SESSION_FILE%"
    echo 3. Computed dlya zashchity ot null >> "%SESSION_FILE%"
    echo 4. Template s v-if proverkami >> "%SESSION_FILE%"
    echo 5. Skeleton loader dlya zagruzki >> "%SESSION_FILE%"
    echo. >> "%SESSION_FILE%"
)

if "%ACTIVE_EDITOR%"=="CURSOR" (
    echo ## Cursor Commands: >> "%SESSION_FILE%"
    echo - @codebase - dlya konteksta vsego proekta >> "%SESSION_FILE%"
    echo - @docs - dlya dokumentatsii >> "%SESSION_FILE%"
    echo - @web - dlya poiska v internete >> "%SESSION_FILE%"
    echo. >> "%SESSION_FILE%"
)

:: Vazhnoe v kontse (Last Word hack)
echo ## CRITICAL (LAST WORD - samoe vazhnoe v kontse!): >> "%SESSION_FILE%"
echo 1. NO business logic in controllers - tolko v servisakh! >> "%SESSION_FILE%"
echo 2. ALWAYS use TypeScript s yavnoy tipizatsiey >> "%SESSION_FILE%"
echo 3. FOLLOW FSD structure dlya frontend >> "%SESSION_FILE%"
echo 4. FOLLOW DDD structure dlya backend >> "%SESSION_FILE%"
echo 5. PRESERVE backward compatibility >> "%SESSION_FILE%"
echo 6. TEST before commit >> "%SESSION_FILE%"

echo.
echo ========================================================
echo                    GOTOVO / DONE!
echo ========================================================
echo.
echo [+] Kontekst sokhranyon v: %SESSION_FILE%
echo.
echo Chtoby skopirovat v bufer obmena:
echo [K] - Kopirovat seychas
echo [Enter] - Propustit
set /p copy_choice=
if /i "%copy_choice%"=="K" (
    type "%SESSION_FILE%" | clip
    echo [+] Skopirovano v bufer obmena!
)
echo.
echo Chto delat dalshe:
if "%ACTIVE_EDITOR%"=="CLAUDE" (
    echo 1. Pereklyuchites na Claude Code
    echo 2. Vstavte kontekst (Ctrl+V)
    echo 3. Nachnite s: "Ultrathink, vspomni CLAUDE.md i etot kontekst"
)
if "%ACTIVE_EDITOR%"=="CURSOR" (
    echo 1. Pereklyuchites na Cursor
    echo 2. Vstavte kontekst (Ctrl+V) ili ispolzuyte @codebase
    echo 3. Opishite zadachu
)
if "%ACTIVE_EDITOR%"=="BOTH" (
    echo - Claude: nachnite s "Ultrathink, vspomni CLAUDE.md"
    echo - Cursor: ispolzuyte @codebase dlya konteksta
)
echo.
pause
goto MAIN_MENU

:WATCH_MODE
cls
echo ========================================================
echo       REZHIM NABLYUDATELYA / WATCH MODE
echo ========================================================
echo.
echo Vyberite rezhim nablyudatelya:
echo.
echo [1] Standard Watch - Kazhdye 30 sekund
echo [2] Fast Watch     - Kazhdye 10 sekund
echo [3] Slow Watch     - Kazhdye 60 sekund
echo [4] Custom Watch   - Svoy interval
echo.
set /p watch_choice=Vybor: 

set INTERVAL=30
if "%watch_choice%"=="1" set INTERVAL=30
if "%watch_choice%"=="2" set INTERVAL=10
if "%watch_choice%"=="3" set INTERVAL=60
if "%watch_choice%"=="4" (
    set /p INTERVAL=Vvedite interval v sekundakh: 
)

echo.
echo Zapuskayu nablyudatel s intervalom %INTERVAL% sekund...
echo.

:: Sozdaem vremennyy fayl dlya nablyudatelya
echo @echo off > "%TEMP%\watch_universal.bat"
echo chcp 866 ^>nul >> "%TEMP%\watch_universal.bat"
echo setlocal enabledelayedexpansion >> "%TEMP%\watch_universal.bat"
echo :LOOP >> "%TEMP%\watch_universal.bat"
echo cls >> "%TEMP%\watch_universal.bat"
echo echo ======================================================== >> "%TEMP%\watch_universal.bat"
echo echo              UNIVERSAL WATCHER ACTIVE >> "%TEMP%\watch_universal.bat"
echo echo ======================================================== >> "%TEMP%\watch_universal.bat"
echo echo. >> "%TEMP%\watch_universal.bat"
echo echo Interval: %INTERVAL% sekund >> "%TEMP%\watch_universal.bat"
echo echo Redaktory: %ACTIVE_EDITOR% >> "%TEMP%\watch_universal.bat"
echo echo. >> "%TEMP%\watch_universal.bat"
echo for /f %%%%I in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set datetime=%%%%I >> "%TEMP%\watch_universal.bat"
echo set "SESSION_FILE=storage\ai-sessions\watch_%%datetime%%.md" >> "%TEMP%\watch_universal.bat"
echo echo # Watch Context ^> "%%SESSION_FILE%%" >> "%TEMP%\watch_universal.bat"
echo echo Time: %%time%% ^>^> "%%SESSION_FILE%%" >> "%TEMP%\watch_universal.bat"
echo echo. ^>^> "%%SESSION_FILE%%" >> "%TEMP%\watch_universal.bat"
echo git status --porcelain 2^>nul ^>^> "%%SESSION_FILE%%" >> "%TEMP%\watch_universal.bat"
echo echo [%%time%%] Kontekst obnovlen v fayl: %%SESSION_FILE%% >> "%TEMP%\watch_universal.bat"
echo timeout /t %INTERVAL% /nobreak ^>nul >> "%TEMP%\watch_universal.bat"
echo goto LOOP >> "%TEMP%\watch_universal.bat"

start "Universal Watcher" cmd /c "%TEMP%\watch_universal.bat"

echo Nablyudatel zapushchen v otdelnom okne.
echo Zakroyte okno nablyudatelya dlya ostanovki.
echo.
pause
goto MAIN_MENU

:SMART_CONTEXT
cls
echo ========================================================
echo       UMNYY KONTEKST / SMART CONTEXT
echo ========================================================
echo.
echo Analiziruyu proekt dlya sozdaniya optimalnogo konteksta...
echo.

:: Sozdaem umnyy kontekst
for /f %%I in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set datetime=%%I
set "SESSION_FILE=storage\ai-sessions\smart_%datetime%.md"

echo # Smart Context with Priority > "%SESSION_FILE%"
echo Generated: %date% %time% >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"

echo ## PRIORITY 1 - CRITICAL (must remember): >> "%SESSION_FILE%"
echo - Architecture: FSD (frontend) + DDD (backend) >> "%SESSION_FILE%"
echo - TypeScript mandatory everywhere >> "%SESSION_FILE%"
echo - NO logic in controllers >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"

echo ## PRIORITY 2 - IMPORTANT (context): >> "%SESSION_FILE%"
echo ### Current Working Directory: >> "%SESSION_FILE%"
echo %cd% >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"

echo ### Active Files (modified today): >> "%SESSION_FILE%"
powershell -Command "Get-ChildItem -Recurse -File -ErrorAction SilentlyContinue | Where-Object {$_.LastWriteTime -gt (Get-Date).Date -and $_.FullName -notlike '*node_modules*'} | Select-Object -First 5 FullName | ForEach-Object {$_.FullName}" >> "%SESSION_FILE%" 2>nul
echo. >> "%SESSION_FILE%"

echo ## PRIORITY 3 - REFERENCE (examples): >> "%SESSION_FILE%"
echo ### Component Structure Example: >> "%SESSION_FILE%"
echo ```typescript >> "%SESSION_FILE%"
echo interface Props { >> "%SESSION_FILE%"
echo   data: SomeType >> "%SESSION_FILE%"
echo   loading?: boolean >> "%SESSION_FILE%"
echo } >> "%SESSION_FILE%"
echo const props = withDefaults(defineProps^<Props^>(), { >> "%SESSION_FILE%"
echo   loading: false >> "%SESSION_FILE%"
echo }) >> "%SESSION_FILE%"
echo ``` >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"

:: Spetsifichno dlya redaktora
if "%ACTIVE_EDITOR%"=="CURSOR" (
    echo ## Cursor Tips: >> "%SESSION_FILE%"
    echo - Use Cmd+K for quick edits >> "%SESSION_FILE%"
    echo - Use @codebase for full context >> "%SESSION_FILE%"
    echo - Use @docs for documentation >> "%SESSION_FILE%"
    echo. >> "%SESSION_FILE%"
)

echo ## LAST WORD (kriticheski vazhno): >> "%SESSION_FILE%"
echo REMEMBER: TypeScript, FSD, DDD, no controller logic! >> "%SESSION_FILE%"

echo.
echo [+] Umnyy kontekst sozdan!
echo [+] Fayl: %SESSION_FILE%
echo.
echo [K] - Kopirovat v bufer obmena
echo [Enter] - Propustit
set /p copy_choice=
if /i "%copy_choice%"=="K" (
    type "%SESSION_FILE%" | clip
    echo [+] Skopirovano!
)
echo.
pause
goto MAIN_MENU

:MODULE_CONTEXT
cls
echo ========================================================
echo       KONTEKST MODULYA / MODULE CONTEXT
echo ========================================================
echo.
echo Vyberite modul:
echo.
echo [1] booking  - Bronirovanie
echo [2] masters  - Mastera
echo [3] ads      - Obyavleniya
echo [4] user     - Polzovateli
echo [5] payment  - Platezhi
echo [6] media    - Media fayly
echo.
set /p module=Nomer modulya: 

set MODULE_NAME=
if "%module%"=="1" set MODULE_NAME=booking
if "%module%"=="2" set MODULE_NAME=masters
if "%module%"=="3" set MODULE_NAME=ads
if "%module%"=="4" set MODULE_NAME=user
if "%module%"=="5" set MODULE_NAME=payment
if "%module%"=="6" set MODULE_NAME=media

if "%MODULE_NAME%"=="" (
    echo Nevernyy vybor!
    pause
    goto MODULE_CONTEXT
)

echo.
echo Sobirayu kontekst dlya modulya: %MODULE_NAME%
echo.

for /f %%I in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set datetime=%%I
set "SESSION_FILE=storage\ai-sessions\module_%MODULE_NAME%_%datetime%.md"

echo # Module Context: %MODULE_NAME% > "%SESSION_FILE%"
echo Generated: %date% %time% >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"

echo ## Backend Structure (Domain/%MODULE_NAME%/): >> "%SESSION_FILE%"
if exist "app\Domain\%MODULE_NAME%" (
    dir /b "app\Domain\%MODULE_NAME%" >> "%SESSION_FILE%" 2>nul
) else (
    echo Module not found in Domain layer >> "%SESSION_FILE%"
)
echo. >> "%SESSION_FILE%"

echo ## Frontend Structure (entities/%MODULE_NAME%/): >> "%SESSION_FILE%"
if exist "resources\js\src\entities\%MODULE_NAME%" (
    dir /b /s "resources\js\src\entities\%MODULE_NAME%" >> "%SESSION_FILE%" 2>nul
) else (
    echo Module not found in entities >> "%SESSION_FILE%"
)
echo. >> "%SESSION_FILE%"

echo ## Related Controllers: >> "%SESSION_FILE%"
dir /b "app\Application\Http\Controllers" 2>nul | findstr /i "%MODULE_NAME%" >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"

echo ## Module-specific reminders: >> "%SESSION_FILE%"
echo - Follow DDD for backend >> "%SESSION_FILE%"
echo - Follow FSD for frontend >> "%SESSION_FILE%"
echo - Use TypeScript >> "%SESSION_FILE%"
echo - Business logic in services only >> "%SESSION_FILE%"

echo.
echo [+] Kontekst modulya %MODULE_NAME% sozdan!
echo [+] Fayl: %SESSION_FILE%
echo.
echo [K] - Kopirovat v bufer obmena
echo [Enter] - Propustit
set /p copy_choice=
if /i "%copy_choice%"=="K" (
    type "%SESSION_FILE%" | clip
    echo [+] Skopirovano v bufer obmena!
)
echo.
pause
goto MAIN_MENU

:PROJECT_STATE
cls
echo ========================================================
echo       SOSTOYANIE PROEKTA / PROJECT STATE
echo ========================================================
echo.
echo Sobirayu polnuyu informatsiyu o proekte...
echo.

for /f %%I in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set datetime=%%I
set "SESSION_FILE=storage\ai-sessions\state_%datetime%.md"

echo # Full Project State > "%SESSION_FILE%"
echo Generated: %date% %time% >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"

echo ## Project Info: >> "%SESSION_FILE%"
echo Directory: %cd% >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"

echo ## Git Information: >> "%SESSION_FILE%"
git branch --show-current 2>nul >> "%SESSION_FILE%"
git log --oneline -5 2>nul >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"

echo ## NPM Status: >> "%SESSION_FILE%"
if exist "package.json" (
    npm list --depth=0 2>nul | findstr "vue typescript vite" >> "%SESSION_FILE%"
)
echo. >> "%SESSION_FILE%"

echo ## Laravel Status: >> "%SESSION_FILE%"
if exist "artisan" (
    php artisan --version 2>nul >> "%SESSION_FILE%"
)
echo. >> "%SESSION_FILE%"

echo ## Running Services: >> "%SESSION_FILE%"
netstat -an | findstr "LISTENING" | findstr "3000 5173 8000" >> "%SESSION_FILE%" 2>nul
echo. >> "%SESSION_FILE%"

echo ## Disk Usage: >> "%SESSION_FILE%"
dir /s /-c 2>nul | findstr "File(s)" | findstr /v "node_modules" >> "%SESSION_FILE%"

echo.
echo [+] Polnoe sostoyanie proekta sobrano!
echo [+] Fayl: %SESSION_FILE%
echo.
echo [K] - Kopirovat v bufer obmena
echo [Enter] - Propustit
set /p copy_choice=
if /i "%copy_choice%"=="K" (
    type "%SESSION_FILE%" | clip
    echo [+] Skopirovano v bufer obmena!
)
echo.
pause
goto MAIN_MENU

:CLAUDE_MODE
cls
echo ========================================================
echo       CLAUDE CODE OPTIMIZATION MODE
echo ========================================================
echo.
echo Nastraivayu optimalnyy format dlya Claude Code...
echo.

set "ACTIVE_EDITOR=CLAUDE"
goto QUICK_CONTEXT

:CURSOR_MODE
cls
echo ========================================================
echo       CURSOR OPTIMIZATION MODE
echo ========================================================
echo.
echo Nastraivayu optimalnyy format dlya Cursor...
echo.

set "ACTIVE_EDITOR=CURSOR"
goto QUICK_CONTEXT

:BOTH_MODE
cls
echo ========================================================
echo       UNIVERSAL MODE - BOTH EDITORS
echo ========================================================
echo.
echo Sozdayu universalnyy kontekst dlya oboikh redaktorov...
echo.

set "ACTIVE_EDITOR=BOTH"
goto QUICK_CONTEXT

:CLEAN_SESSIONS
cls
echo ========================================================
echo       OCHISTKA SESSIY / CLEAN SESSIONS
echo ========================================================
echo.

for /f %%i in ('dir /b /s "storage\ai-sessions\*.md" 2^>nul ^| find /c /v ""') do set FILE_COUNT=%%i
echo Naydeno faylov: %FILE_COUNT%
echo.
echo [1] Udalit starshe 7 dney
echo [2] Udalit starshe 30 dney
echo [3] Udalit vse krome poslednikh 10
echo [4] Pokazat statistiku
echo [5] Otmena
echo.
set /p clean_choice=Vybor: 

if "%clean_choice%"=="1" (
    powershell -Command "Get-ChildItem -Path 'storage\ai-sessions' -Filter '*.md' -Recurse | Where-Object {$_.LastWriteTime -lt (Get-Date).AddDays(-7)} | Remove-Item -Force"
    echo [+] Udaleny fayly starshe 7 dney
)
if "%clean_choice%"=="2" (
    powershell -Command "Get-ChildItem -Path 'storage\ai-sessions' -Filter '*.md' -Recurse | Where-Object {$_.LastWriteTime -lt (Get-Date).AddDays(-30)} | Remove-Item -Force"
    echo [+] Udaleny fayly starshe 30 dney
)
if "%clean_choice%"=="3" (
    powershell -Command "$files = Get-ChildItem -Path 'storage\ai-sessions' -Filter '*.md' -Recurse | Sort-Object LastWriteTime -Descending | Select-Object -Skip 10; if($files) { $files | Remove-Item -Force }"
    echo [+] Ostavleny tolko poslednie 10 faylov
)
if "%clean_choice%"=="4" (
    echo.
    echo Statistika po dnyam:
    powershell -Command "Get-ChildItem -Path 'storage\ai-sessions' -Filter '*.md' -Recurse | Group-Object {$_.LastWriteTime.Date} | Sort-Object Name -Descending | Select-Object -First 10 @{Name='Date';Expression={$_.Name}}, Count | Format-Table -AutoSize"
)

echo.
pause
goto MAIN_MENU

:STATISTICS
cls
echo ========================================================
echo       STATISTIKA / STATISTICS
echo ========================================================
echo.

echo Analiziruyu ispolzovanie...
echo.

powershell -Command "$files = Get-ChildItem -Path 'storage\ai-sessions' -Filter '*.md' -Recurse -ErrorAction SilentlyContinue; if ($files) { $stats = @{}; $stats['Total Files'] = $files.Count; $stats['Total Size (MB)'] = [math]::Round(($files | Measure-Object Length -Sum).Sum/1MB,2); $stats['Oldest File'] = ($files | Sort-Object LastWriteTime | Select-Object -First 1).LastWriteTime; $stats['Newest File'] = ($files | Sort-Object LastWriteTime -Descending | Select-Object -First 1).LastWriteTime; $stats['Average Size (KB)'] = [math]::Round(($files | Measure-Object Length -Average).Average/1KB,2); Write-Host ''; $stats.GetEnumerator() | Sort-Object Name | ForEach-Object { Write-Host ('{0,-20} : {1}' -f $_.Key, $_.Value) }; Write-Host ''; Write-Host 'Fayly po tipu:'; $files | Group-Object { if($_.Name -match '^watch_') {'Watch'} elseif($_.Name -match '^smart_') {'Smart'} elseif($_.Name -match '^module_') {'Module'} else {'Other'} } | ForEach-Object { Write-Host ('{0,-20} : {1}' -f $_.Name, $_.Count) } } else { Write-Host 'Net faylov sessiy' }"

echo.
pause
goto MAIN_MENU

:SETTINGS
cls
echo ========================================================
echo       NASTROYKI / SETTINGS
echo ========================================================
echo.

:: Proveryaem nalichie fayla nastroek
if not exist "storage\ai-sessions\settings.json" (
    echo Sozdayu fayl nastroek...
    echo { > "storage\ai-sessions\settings.json"
    echo   "default_editor": "BOTH", >> "storage\ai-sessions\settings.json"
    echo   "watch_interval": 30, >> "storage\ai-sessions\settings.json"
    echo   "auto_copy": true, >> "storage\ai-sessions\settings.json"
    echo   "save_to_file": true, >> "storage\ai-sessions\settings.json"
    echo   "max_context_size": 4000, >> "storage\ai-sessions\settings.json"
    echo   "include_git_status": true, >> "storage\ai-sessions\settings.json"
    echo   "include_recent_files": true, >> "storage\ai-sessions\settings.json"
    echo   "claude_format": true, >> "storage\ai-sessions\settings.json"
    echo   "cursor_format": true >> "storage\ai-sessions\settings.json"
    echo } >> "storage\ai-sessions\settings.json"
)

echo Tekushchie nastroyki:
echo.
type "storage\ai-sessions\settings.json"
echo.
echo [1] Izmenit redaktor po umolchaniyu
echo [2] Izmenit interval nablyudatelya
echo [3] Sbrosit nastroyki
echo [4] Nazad
echo.
set /p settings_choice=Vybor: 

if "%settings_choice%"=="1" (
    echo.
    echo Vyberite redaktor po umolchaniyu:
    echo [1] Claude Code
    echo [2] Cursor
    echo [3] Both
    set /p editor_choice=Vybor: 
    echo Nastroyka sokhranena!
)
if "%settings_choice%"=="2" (
    echo.
    set /p new_interval=Vvedite interval v sekundakh (10-300): 
    echo Interval izmenen na %new_interval% sekund!
)
if "%settings_choice%"=="3" (
    del "storage\ai-sessions\settings.json" 2>nul
    echo Nastroyki sbrosheny!
)

pause
goto MAIN_MENU

:HELP
cls
echo ========================================================
echo       SPRAVKA / HELP
echo ========================================================
echo.
echo CHTO ETO:
echo Universalnyy instrument dlya raboty s AI-redaktorami
echo Podderzhivaet Claude Code i Cursor odnovremenno
echo.
echo OSNOVNYE FUNKTSII:
echo - Quick Context - bystryy sbor konteksta proekta
echo - Watch Mode - avtomaticheskoe otslezhivanie izmeneniy
echo - Smart Context - prioritizirovannyy kontekst
echo - Module Context - kontekst konkretnogo modulya
echo.
echo OSOBENNOSTI DLYA CLAUDE CODE:
echo - Ispolzuet kontekstnuyu inzheneriyu iz CLAUDE.md
echo - Last Word hack - vazhnoe v kontse
echo - Structural Anchor - shablony
echo - Context Injection - napominaniya
echo.
echo OSOBENNOSTI DLYA CURSOR:
echo - Optimizirovannyy JSON format
echo - Podskazki po komandam (@codebase, @docs)
echo - Sovmestimost s Cursor AI
echo.
echo FAYLY SOKHRANYAYUTSYA V:
echo storage\ai-sessions\*.md
echo.
echo GORYACHIE KLAVISHI:
echo - Ctrl+C - ostanovit nablyudatel
echo - Ctrl+V - vstavit kontekst iz bufera
echo.
pause
goto MAIN_MENU

:END
echo.
echo ========================================================
echo                    Do svidaniya!
echo ========================================================
timeout /t 2 >nul
endlocal
exit /b