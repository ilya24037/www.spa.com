@echo off
color 0A
echo ╔════════════════════════════════════╗
echo ║    🚀 УМНЫЙ АВТО-КОММИТ v2.0      ║
echo ╚════════════════════════════════════╝
echo.
echo Выберите действие:
echo [1] Быстрый коммит (автосообщение)
echo [2] Коммит с вашим сообщением
echo [3] Только AI контекст
echo [4] Показать статус
echo [0] Выход
echo.

set /p choice="Ваш выбор (0-4): "

cd /d D:\www.spa.com

if "%choice%"=="1" goto quick
if "%choice%"=="2" goto custom
if "%choice%"=="3" goto context
if "%choice%"=="4" goto status
if "%choice%"=="0" exit

:quick
echo.
echo 🔄 Выполняю быстрый коммит...
php artisan git:smart --auto
git push origin main
echo ✅ Готово!
pause
exit

:custom
echo.
set /p msg="Введите сообщение коммита: "
git add .
git commit -m "%msg%"
git push origin main
php artisan ai:context --quick
echo ✅ Готово!
pause
exit

:context
echo.
echo 📝 Генерирую AI контекст...
php artisan ai:context
echo ✅ Готово!
pause
exit

:status
echo.
echo 📊 Текущий статус:
git status
echo.
pause
exit