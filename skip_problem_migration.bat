@echo off
chcp 65001 >nul
title Пропуск проблемной миграции
echo =====================================
echo     ПРОПУСК ПРОБЛЕМНОЙ МИГРАЦИИ
echo =====================================
echo.

echo 🔧 Выполняем только нужные миграции...
echo.

echo 📋 Миграция 1: Создание таблицы bookings...
php artisan migrate --path=database/migrations/2025_07_24_000000_create_bookings_table.php

echo.
echo 📋 Миграция 2: Создание таблицы schedules...
php artisan migrate --path=database/migrations/2025_07_24_000001_create_schedules_table.php

echo.
echo 📋 Миграция 3: Создание таблицы reviews...
php artisan migrate --path=database/migrations/2025_07_24_000002_create_reviews_table.php

echo.
echo 📊 Проверяем статус...
php artisan migrate:status

echo.
echo ✅ Готово! Критичные таблицы для бронирования созданы.
pause 