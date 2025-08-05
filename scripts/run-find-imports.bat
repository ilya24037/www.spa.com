@echo off
echo Searching for specific import patterns...
cd /d "C:\www.spa.com\scripts"
powershell -ExecutionPolicy Bypass -File ".\find-specific-imports.ps1"
pause