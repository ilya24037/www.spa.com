@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

:: Context Watcher - Avtomaticheskiy sbor konteksta
title Context Watcher - Nablyudatel

echo ========================================================
echo          CONTEXT WATCHER - NABLYUDATEL
echo ========================================================
echo.
echo Rezhim: Avtomaticheskiy sbor konteksta
echo Interval: 30 sekund
echo.
echo Nablyudayu izmeneniya v:
echo - Git status
echo - Izmeneniya faylov
echo - Novye fayly
echo.
echo Nazhmi Ctrl+C dlya ostanovki
echo ========================================================
echo.

:: Sozdaem papku dlya sessiy esli ee net
if not exist "storage\ai-sessions" mkdir "storage\ai-sessions"

:WATCH_LOOP
:: Generiruem imya fayla s datoy i vremenem (bez WMIC)
for /f %%I in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set datetime=%%I
set "SESSION_FILE=storage\ai-sessions\watch_%datetime%.md"

:: Sozdaem kontekst
echo # Watch Mode Context > "%SESSION_FILE%"
echo Generated: %date% %time% >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"

:: Osnovnaya informatsiya
echo ## Project State >> "%SESSION_FILE%"
echo Directory: %cd% >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"

:: Git status
echo ## Git Changes: >> "%SESSION_FILE%"
git status --porcelain 2>nul >> "%SESSION_FILE%"
if errorlevel 1 echo No git repository >> "%SESSION_FILE%"
echo. >> "%SESSION_FILE%"

:: Poslednie izmeneniya (5 minut)
echo ## Recent File Changes (5 min): >> "%SESSION_FILE%"
powershell -Command "Get-ChildItem -Recurse -File | Where-Object {$_.LastWriteTime -gt (Get-Date).AddMinutes(-5)} | Select-Object -First 10 FullName, LastWriteTime | Format-Table -AutoSize" >> "%SESSION_FILE%" 2>nul
echo. >> "%SESSION_FILE%"

:: Tekushchie processy razrabotki
echo ## Running Dev Servers: >> "%SESSION_FILE%"
netstat -an | findstr "3000 5173 8000" >nul 2>&1
if errorlevel 1 (
    echo No dev servers detected >> "%SESSION_FILE%"
) else (
    echo Detected active dev servers: >> "%SESSION_FILE%"
    netstat -an | findstr "3000 5173 8000" >> "%SESSION_FILE%"
)
echo. >> "%SESSION_FILE%"

:: Proverka oshibok v logah
echo ## Recent Errors: >> "%SESSION_FILE%"
if exist "storage\logs\laravel.log" (
    powershell -Command "Get-Content 'storage\logs\laravel.log' -Tail 20 | Select-String -Pattern 'ERROR|Exception|Fatal' -Context 0,2" >> "%SESSION_FILE%" 2>nul
    if errorlevel 1 echo No recent errors >> "%SESSION_FILE%"
) else (
    echo No Laravel log file >> "%SESSION_FILE%"
)

:: Kopiruem v bufer posledniy kontekst
type "%SESSION_FILE%" | clip

:: Otobrazhaem status
cls
echo ========================================================
echo          CONTEXT WATCHER - AKTIVNYY
echo ========================================================
echo.
echo [%time%] Kontekst obnovlen
echo.
echo Fayl: %SESSION_FILE%
echo.
echo Naydenye izmeneniya:
powershell -Command "Get-ChildItem -Recurse -File | Where-Object {$_.LastWriteTime -gt (Get-Date).AddMinutes(-5)} | Measure-Object | Select-Object -ExpandProperty Count" 2>nul
echo faylov izmeneno za poslednie 5 minut
echo.
echo Kontekst skopirovan v bufer obmena!
echo.
echo Sleduyushchee obnovlenie cherez 30 sekund...
echo Ctrl+C dlya ostanovki
echo ========================================================

:: Zhdem 30 sekund
timeout /t 30 /nobreak >nul

goto WATCH_LOOP

:END
echo.
echo Nablyudatel ostanovlen
pause
endlocal