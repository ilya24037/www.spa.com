@echo off
chcp 65001 >nul
title Проверка сборки
echo =====================================
echo      ПРОВЕРКА СБОРКИ ПРОЕКТА
echo =====================================
echo.

cd /d %~dp0

echo 🔍 Проверяем npm...
npm --version
echo.

echo 📦 Запускаем сборку...
npm run build
echo.

echo ✅ Готово!
pause 