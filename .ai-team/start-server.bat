@echo off
title AI Team Server - SPA Platform
color 0B

echo ========================================
echo      AI TEAM SERVER - SPA PLATFORM
echo ========================================
echo.
echo Starting Node.js server for AI Team Dashboard...
echo.

REM Change to AI Team directory
cd /d "C:\www.spa.com\.ai-team"

REM Check if Node.js is installed
where node >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Node.js is not installed or not in PATH
    echo Please install Node.js from https://nodejs.org/
    echo.
    pause
    exit /b 1
)

REM Check if server file exists
if not exist "ai-team-server.cjs" (
    echo ERROR: ai-team-server.cjs not found
    echo Make sure you are in the correct directory
    echo.
    pause
    exit /b 1
)

REM Install dependencies if needed
if not exist "node_modules" (
    echo Installing dependencies...
    npm install express cors
    echo.
)

echo Server starting on http://localhost:8082
echo Dashboard available at: ai-team-dashboard-channels.html
echo.
echo Press Ctrl+C to stop the server
echo.

REM Start the server
node ai-team-server.cjs

echo.
echo Server stopped.
pause