@echo off
echo Starting AI Team Chat in Terminal...
echo.
python "%~dp0team-chat.py"
if errorlevel 1 (
    echo.
    echo Python not found or error occurred!
    echo Please ensure Python 3 is installed.
    echo.
    echo Alternative: Use PowerShell directly:
    echo python .aidd\team-chat.py
    echo.
    pause
)