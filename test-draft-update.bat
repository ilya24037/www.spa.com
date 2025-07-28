@echo off
chcp 65001 >nul
title SPA Platform - Тест обновления черновиков
color 0D
cls

echo =====================================
echo    ТЕСТ ОБНОВЛЕНИЯ ЧЕРНОВИКОВ
echo =====================================
echo.
echo 🔧 Что исправлено:
echo   ❌ Было: storeDraft всегда создавал новый черновик (Ad::create)
echo   ✅ Стало: Проверяет ID и обновляет существующий (Ad::update)
echo.
echo 📋 Изменения в коде:
echo.
echo 1. AdController@storeDraft:
echo    - Добавлена проверка request->has('id')
echo    - Если ID есть → обновляем существующий
echo    - Если ID нет → создаем новый
echo.
echo 2. AdForm.vue handleSaveDraft:
echo    - Добавлено: id: props.adId
echo    - Передает ID черновика для обновления
echo.
echo 3. AdForm.vue saveDraft:
echo    - Добавлено: id: props.adId  
echo    - Для fetch запросов тоже передается ID
echo.
echo 🎯 Как работает:
echo   1. Редактируете черновик ID=132
echo   2. Нажимаете "Сохранить черновик"
echo   3. В запросе: { id: 132, title: "новый заголовок", ... }
echo   4. Сервер: Ad::where('id', 132)->update(data)
echo   5. Черновик обновляется, новый НЕ создается
echo.
echo 📋 ТЕСТ:
echo   1. Откройте существующий черновик для редактирования
echo   2. Измените заголовок/описание
echo   3. Нажмите "Сохранить черновик" 
echo   4. Проверьте что НЕ создался новый черновик
echo   5. Проверьте что изменения сохранились в том же черновике
echo.
echo 🌐 Тестовая страница: spa.test/profile/items/draft/all
echo    → Откройте черновик → Редактировать → Измените → Сохранить
echo.
pause 