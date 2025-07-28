@echo off
chcp 65001 >nul
title Проверка Dev сервера
echo =====================================
echo     ПРОВЕРКА DEV СЕРВЕРА
echo =====================================
echo.

cd /d %~dp0

echo 🔍 Проверяем Vite процессы...
netstat -aon | findstr :5173
echo.

echo 📦 Проверяем файлы сборки...
if exist "public\build\manifest.json" (
    echo ✅ Manifest найден
) else (
    echo ❌ Manifest НЕ найден - нужна сборка
)
echo.

echo 🔍 Проверяем hot file...
if exist "public\hot" (
    echo ✅ Hot reload активен
    type public\hot
) else (
    echo ❌ Hot reload НЕ активен
)
echo.

pause 