@echo off
cd /d "C:\www.spa.com"
echo Testing file opening...
echo.

REM Test 1: Direct notepad
echo Test 1: notepad AI_CONTEXT.md
notepad AI_CONTEXT.md
echo.

REM Test 2: Start with notepad
echo Test 2: start notepad AI_CONTEXT.md  
start notepad AI_CONTEXT.md
echo.

REM Test 3: Start with full path
echo Test 3: start notepad.exe AI_CONTEXT.md
start "" notepad.exe "AI_CONTEXT.md"
echo.

pause