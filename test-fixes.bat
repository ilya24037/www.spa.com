@echo off
chcp 65001 >nul
title SPA Platform - Тест исправлений
color 0B
cls

echo =====================================
echo    ТЕСТ ИСПРАВЛЕНИЙ УДАЛЕНИЯ
echo =====================================
echo.
echo 🔧 Что исправлено:
echo   1. Удаление черновиков: /draft/{id} вместо /my-ads/{id}
echo   2. Навигация черновиков: переход на редактирование вместо alert
echo   3. Добавлена подробная отладочная информация
echo.
echo 📋 Что нужно протестировать:
echo   1. Перейти на: spa.test/profile/items/draft/all
echo   2. Попробовать удалить черновик
echo   3. Попробовать кликнуть на фото/название черновика
echo   4. Проверить консоль браузера (F12)
echo.
echo 🚀 Запускаем серверы для тестирования...
echo.

echo 📌 Очищаем кеш Laravel...
php artisan cache:clear >nul 2>&1
php artisan config:clear >nul 2>&1
php artisan route:clear >nul 2>&1
php artisan view:clear >nul 2>&1

echo 📌 Проверяем роуты...
echo.
echo === РОУТЫ УДАЛЕНИЯ ===
php artisan route:list --name=destroy | findstr destroy
echo.
echo === РОУТЫ ЧЕРНОВИКОВ ===
php artisan route:list --name=draft | findstr draft
echo.

echo ✅ Всё готово для тестирования!
echo.
echo 🌐 Откройте: spa.test/profile/items/draft/all
echo 🔧 Проверьте консоль браузера для отладки
echo.
pause 