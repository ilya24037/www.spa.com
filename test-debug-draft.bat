@echo off
chcp 65001 >nul
title SPA Platform - Диагностика дублирования черновиков
color 0E
cls

echo =====================================
echo   ДИАГНОСТИКА ДУБЛИРОВАНИЯ ЧЕРНОВИКОВ
echo =====================================
echo.
echo 🔍 Добавлена отладка:
echo   ✓ AdController@storeDraft - логи в storage/logs/laravel.log
echo   ✓ AdForm.vue handleSaveDraft - логи в консоли браузера
echo.
echo 📋 ЧТО ПРОВЕРИТЬ:
echo.
echo 1. В КОНСОЛИ БРАУЗЕРА (F12):
echo    === SAVE DRAFT DEBUG ===
echo    props.adId: должен быть ID черновика
echo    ID в данных: должен быть тот же ID
echo.
echo 2. В ЛОГАХ LARAVEL:
echo    === STORE DRAFT DEBUG ===  
echo    Request ID: должен быть ID черновика
echo    Request has ID: должно быть true
echo    Updating existing draft / Creating new draft
echo.
echo 🎯 ТЕСТ:
echo   1. Откройте черновик для редактирования
echo   2. Измените что-то (заголовок)
echo   3. Нажмите "Сохранить черновик"
echo   4. Проверьте консоль браузера
echo   5. Проверьте логи Laravel (показаны ниже)
echo.
echo 📂 Просмотр логов Laravel:
echo.

echo Последние записи лога:
tail -20 storage/logs/laravel.log 2>nul || echo "Лог файл пуст или недоступен"

echo.
echo 🌐 Тестовая страница: spa.test/profile/items/draft/all
echo.
echo ⚠️  ВАЖНО: Сначала попробуйте сохранить черновик, 
echo    потом запустите этот bat снова для просмотра логов!
echo.
pause 