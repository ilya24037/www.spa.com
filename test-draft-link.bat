@echo off
chcp 65001 >nul
title SPA Platform - Диагностика черновика
color 0E
cls

echo =====================================
echo   ДИАГНОСТИКА СТРАНИЦЫ ЧЕРНОВИКА
echo =====================================
echo.
echo 🔍 Анализ проблемы:
echo   ✓ Консоль показывает: "Navigating to: /draft/132"
echo   ✓ Роут существует: GET draft/{ad} → AdController@showDraft
echo   ✓ Черновик 132 существует в БД
echo   ✓ Файл Draft/Show.vue существует
echo   ❌ НО переход НЕ происходит
echo.
echo 🎯 Возможные причины:
echo   1. Inertia.js не обрабатывает Link переход
echo   2. JavaScript ошибка блокирует навигацию
echo   3. CSRF или права доступа
echo   4. Route model binding не работает
echo.
echo 📋 ТЕСТЫ ДЛЯ ДИАГНОСТИКИ:
echo.
echo 1. Прямой переход в адресной строке:
echo    → http://spa.test/draft/132
echo.
echo 2. Проверьте консоль браузера на ОШИБКИ
echo    → F12 → Console → ищите красные ошибки
echo.
echo 3. Проверьте Network вкладку:
echo    → F12 → Network → попробуйте кликнуть → смотрите запросы
echo.
echo 4. Попробуйте разные черновики:
echo    → Может проблема с конкретным ID
echo.
echo 🌐 Прямая ссылка для теста:
echo    spa.test/draft/132
echo.
echo ⚠️  ВАЖНО: Проверьте консоль на ошибки!
echo.
pause 