@echo off
chcp 866 >nul
echo ========================================================
echo           NACHALO RABOTY S CLAUDE
echo ========================================================
echo.

:: Sozdaem aktualnyy kontekst
echo Generiruyu aktualnyy kontekst...
echo.

:: Sobirаem vse v odin fayl
set "CONTEXT_FILE=%TEMP%\full_context.md"

echo # POLNYY KONTEKST PROEKTA > "%CONTEXT_FILE%"
echo Generated: %date% %time% >> "%CONTEXT_FILE%"
echo. >> "%CONTEXT_FILE%"

:: AI_CONTEXT.md
if exist "AI_CONTEXT.md" (
    echo ## AI_CONTEXT.md: >> "%CONTEXT_FILE%"
    echo. >> "%CONTEXT_FILE%"
    type "AI_CONTEXT.md" >> "%CONTEXT_FILE%"
    echo. >> "%CONTEXT_FILE%"
    echo --- >> "%CONTEXT_FILE%"
    echo. >> "%CONTEXT_FILE%"
)

:: Posledniy watch fayl
echo ## Poslednie izmeneniya (Watch Mode): >> "%CONTEXT_FILE%"
echo. >> "%CONTEXT_FILE%"
for /f "delims=" %%i in ('dir /b /o-d "storage\ai-sessions\watch_*.md" 2^>nul') do (
    if exist "storage\ai-sessions\%%i" (
        type "storage\ai-sessions\%%i" >> "%CONTEXT_FILE%"
        goto :watch_done
    )
)
echo Net faylov Watch Mode >> "%CONTEXT_FILE%"
:watch_done
echo. >> "%CONTEXT_FILE%"

:: Git status
echo ## Git Status: >> "%CONTEXT_FILE%"
git status --short >> "%CONTEXT_FILE%" 2>nul
echo. >> "%CONTEXT_FILE%"

:: Kopiruem v bufer
type "%CONTEXT_FILE%" | clip

echo ========================================================
echo GOTOVO!
echo ========================================================
echo.
echo Kontekst skopirovan v bufer obmena!
echo.
echo DEYSTVIYA:
echo 1. Otkroyte Claude
echo 2. Vstavte kontekst (Ctrl+V)
echo 3. Nachnite s: "Prochitay etot kontekst i nachnem rabotu"
echo.
echo ========================================================
pause