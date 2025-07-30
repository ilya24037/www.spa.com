@echo off
chcp 65001 >nul
echo 🧪 ТЕСТИРОВАНИЕ ИСПРАВЛЕНИЯ SCHEDULE
echo.
echo 🧹 Очищаю кеш Laravel...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
echo.
echo ✅ Кеш очищен!
echo.
echo 📋 ИНСТРУКЦИИ ДЛЯ ТЕСТИРОВАНИЯ:
echo 1. Обновите страницу черновика (Ctrl+F5)
echo 2. Настройте график работы (например, "Будни 9:00-18:00")
echo 3. Добавьте заметки о графике
echo 4. Сохраните черновик
echo 5. Запустите: php check_ad_data.php
echo.
pause