#!/bin/bash

echo "========================================"
echo "   Восстановление карты из бекапа"
echo "========================================"
echo

echo "📁 Копирование файлов..."

# Копирование основного компонента карты
cp "VueYandexMap.vue" "../../../resources/js/src/shared/ui/molecules/VueYandexMap/VueYandexMap.vue"
if [ $? -ne 0 ]; then
    echo "❌ Ошибка копирования VueYandexMap.vue"
    exit 1
fi
echo "✅ VueYandexMap.vue скопирован"

# Копирование интеграции в формы
cp "GeoSection.vue" "../../../resources/js/src/features/AdSections/GeoSection/ui/GeoSection.vue"
if [ $? -ne 0 ]; then
    echo "❌ Ошибка копирования GeoSection.vue"
    exit 1
fi
echo "✅ GeoSection.vue скопирован"

# Копирование TypeScript типов
cp "types_index.ts" "../../../resources/js/src/features/map/types/index.ts"
if [ $? -ne 0 ]; then
    echo "❌ Ошибка копирования types_index.ts"
    exit 1
fi
echo "✅ types_index.ts скопирован"

echo
echo "🔧 Восстановление настроек vue-yandex-maps в app.js..."
echo
echo "ВАЖНО: Нужно вручную добавить в app.js:"
echo
echo "1. Импорт:"
echo "   import { createYmaps } from 'vue-yandex-maps';"
echo
echo "2. Настройка плагина:"
echo "   .use(createYmaps({"
echo "       apikey: '23ff8acc-835f-4e99-8b19-d33c5d346e18',"
echo "       lang: 'ru_RU'"
echo "   }))"
echo

echo "📦 Установка зависимостей..."
npm install vue-yandex-maps@^2.2.1
if [ $? -ne 0 ]; then
    echo "❌ Ошибка установки зависимостей"
    exit 1
fi
echo "✅ Зависимости установлены"

echo
echo "========================================"
echo "✅ Восстановление завершено!"
echo "========================================"
echo
echo "Следующие шаги:"
echo "1. Проверьте настройки в app.js"
echo "2. Перезапустите сервер: npm run dev"
echo "3. Проверьте работу карты на http://spa.test/additem"
echo
echo "🗺️ Карта должна работать как на Avito!"
echo
