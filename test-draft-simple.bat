@echo off
chcp 65001 >nul
title SPA Platform - Простой тест черновиков
color 0B
cls

echo =====================================
echo     ПРОСТОЙ ТЕСТ ЧЕРНОВИКОВ
echo =====================================
echo.
echo 🔧 Что исправлено:
echo   ✅ Убрано проблемное логирование (Internal Server Error)
echo   ✅ Упрощена логика обновления черновиков
echo   ✅ Graceful fallback если черновик не найден
echo.
echo 📋 Новая логика:
echo   1. Если ID передан и корректный → обновляем существующий
echo   2. Если черновик не найден → создаем новый
echo   3. Если ID некорректный → создаем новый
echo   4. Если ID не передан → создаем новый
echo.
echo 🎯 ТЕСТ:
echo   1. Откройте spa.test/profile/items/draft/all
echo   2. Откройте черновик для редактирования  
echo   3. Измените заголовок
echo   4. Нажмите "Сохранить черновик"
echo   5. Проверьте что НЕ создался новый черновик
echo.
echo 🔍 Отладка в консоли браузера (F12):
echo   === SAVE DRAFT DEBUG ===
echo   props.adId: должен показать ID черновика
echo   ID в данных: должен быть тот же ID
echo.
echo 🌐 Тестовая страница: spa.test/profile/items/draft/all
echo.
echo ⚠️  ВАЖНО: Ctrl+F5 для обновления страницы!
echo.
pause 