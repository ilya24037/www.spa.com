@echo off
chcp 65001 >nul
echo === ТЕСТИРОВАНИЕ ЗАГРУЗКИ ВИДЕО ===
echo.
echo 1. Очищаем кеш Laravel...
php artisan cache:clear
echo.
echo 2. Готово! Теперь:
echo   - Обновите страницу в браузере (Ctrl+F5)
echo   - Попробуйте загрузить видео в форме редактирования
echo   - Поддерживаются форматы: MP4, AVI, WebM
echo   - Максимальный размер: 50MB
echo.
pause