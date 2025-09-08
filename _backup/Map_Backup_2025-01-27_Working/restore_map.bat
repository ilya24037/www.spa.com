@echo off
chcp 65001 >nul
echo ========================================
echo    Восстановление карты из бекапа
echo ========================================
echo.

echo 📁 Копирование файлов...

REM Копирование основного компонента карты
copy "VueYandexMap.vue" "..\..\..\resources\js\src\shared\ui\molecules\VueYandexMap\VueYandexMap.vue"
if %errorlevel% neq 0 (
    echo ❌ Ошибка копирования VueYandexMap.vue
    pause
    exit /b 1
)
echo ✅ VueYandexMap.vue скопирован

REM Копирование интеграции в формы
copy "GeoSection.vue" "..\..\..\resources\js\src\features\AdSections\GeoSection\ui\GeoSection.vue"
if %errorlevel% neq 0 (
    echo ❌ Ошибка копирования GeoSection.vue
    pause
    exit /b 1
)
echo ✅ GeoSection.vue скопирован

REM Копирование TypeScript типов
copy "types_index.ts" "..\..\..\resources\js\src\features\map\types\index.ts"
if %errorlevel% neq 0 (
    echo ❌ Ошибка копирования types_index.ts
    pause
    exit /b 1
)
echo ✅ types_index.ts скопирован

echo.
echo 🔧 Восстановление настроек vue-yandex-maps в app.js...
echo.
echo ВАЖНО: Нужно вручную добавить в app.js:
echo.
echo 1. Импорт:
echo    import { createYmaps } from 'vue-yandex-maps';
echo.
echo 2. Настройка плагина:
echo    .use(createYmaps({
echo        apikey: '23ff8acc-835f-4e99-8b19-d33c5d346e18',
echo        lang: 'ru_RU'
echo    }))
echo.

echo 📦 Установка зависимостей...
call npm install vue-yandex-maps@^2.2.1
if %errorlevel% neq 0 (
    echo ❌ Ошибка установки зависимостей
    pause
    exit /b 1
)
echo ✅ Зависимости установлены

echo.
echo ========================================
echo ✅ Восстановление завершено!
echo ========================================
echo.
echo Следующие шаги:
echo 1. Проверьте настройки в app.js
echo 2. Перезапустите сервер: npm run dev
echo 3. Проверьте работу карты на http://spa.test/additem
echo.
echo 🗺️ Карта должна работать как на Avito!
echo.
pause
