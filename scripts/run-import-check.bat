@echo off
chcp 65001 >nul
cd /d "C:\www.spa.com\scripts"
powershell -ExecutionPolicy Bypass -File ".\check-import-status-fixed.ps1"
pause