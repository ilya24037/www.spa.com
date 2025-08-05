@echo off
echo Fixing remaining import issues...
cd /d "C:\www.spa.com\scripts"
powershell -ExecutionPolicy Bypass -File ".\fix-remaining-imports.ps1"
pause