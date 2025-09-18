@echo off
echo ========================================
echo     TESTING AGENT ROLES
echo ========================================
echo.

:: Kill old agents
taskkill /F /FI "WINDOWTITLE eq *Agent*" >nul 2>&1
timeout /t 2 >nul

echo Testing each agent individually...
echo.

:: Test TeamLead
echo [1/5] Testing TeamLead...
start "TeamLead Test" cmd /k "echo I am TeamLead && timeout /t 5 && exit"
timeout /t 2 >nul

:: Test Backend
echo [2/5] Testing Backend...
start "Backend Test" cmd /k "echo I am Backend Developer && timeout /t 5 && exit"
timeout /t 2 >nul

:: Test Frontend
echo [3/5] Testing Frontend...
start "Frontend Test" cmd /k "echo I am Frontend Developer && timeout /t 5 && exit"
timeout /t 2 >nul

:: Test QA
echo [4/5] Testing QA...
start "QA Test" cmd /k "echo I am QA Engineer && timeout /t 5 && exit"
timeout /t 2 >nul

:: Test DevOps
echo [5/5] Testing DevOps...
start "DevOps Test" cmd /k "echo I am DevOps Engineer && timeout /t 5 && exit"
timeout /t 2 >nul

echo.
echo ========================================
echo All agents tested. Check windows for roles.
echo ========================================
pause