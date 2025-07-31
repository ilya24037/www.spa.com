@echo off
title AI Context Generator
cd /d "C:\www.spa.com"

echo.
echo AI Context Generator
echo ===================
echo.
echo Generating context...
echo.

php artisan ai:context --quick

echo.
echo ===========================================
echo ✅ COMPLETED! 
echo ===========================================
echo.
echo The AI_CONTEXT.md file is ready!
echo You can now open it and copy the content.
echo.
echo Press any key to exit...
pause >nul