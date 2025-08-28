<?php
/**
 * Диагностика проблемы с картами после рефакторинга
 */

echo "🗺️ ДИАГНОСТИКА ПРОБЛЕМЫ С КАРТАМИ ПОСЛЕ РЕФАКТОРИНГА\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// 1. Проверяем файлы до и после рефакторинга
echo "📂 СРАВНЕНИЕ ФАЙЛОВ ДО И ПОСЛЕ РЕФАКТОРИНГА:\n";

$oldFiles = [
    'resources/js/src/shared/ui/molecules/YandexMapPicker/YandexMap.vue.old' => 'Старая рабочая версия',
    'resources/js/src/shared/ui/molecules/YandexMapPicker/YandexMap.vue' => 'Новая версия после рефакторинга'
];

foreach ($oldFiles as $file => $description) {
    $exists = file_exists($file);
    $size = $exists ? filesize($file) : 0;
    echo "  " . ($exists ? "✅" : "❌") . " {$description}\n";
    echo "     Файл: {$file}\n";
    echo "     Размер: " . number_format($size) . " байт\n\n";
}

// 2. Проверяем новые компоненты
echo "🆕 НОВЫЕ КОМПОНЕНТЫ ПОСЛЕ РЕФАКТОРИНГА:\n";

$newComponents = [
    'resources/js/src/features/map/ui/YandexMapBase/YandexMapBase.vue',
    'resources/js/src/features/map/lib/yandexMapsLoader.ts',
    'resources/js/src/features/map/composables/useMapInitializer.ts',
    'resources/js/src/features/map/composables/useMapMobileOptimization.ts',
    'resources/js/src/features/map/composables/useMapMethods.ts',
    'resources/js/src/features/map/lib/mapConstants.ts',
];

foreach ($newComponents as $file) {
    $exists = file_exists($file);
    $size = $exists ? filesize($file) : 0;
    $lines = $exists ? count(file($file)) : 0;
    echo "  " . ($exists ? "✅" : "❌") . " {$file}\n";
    if ($exists) {
        echo "     Размер: " . number_format($size) . " байт, Строк: {$lines}\n";
    }
    echo "\n";
}

// 3. Проверяем где используется карта
echo "🔍 ГДЕ ИСПОЛЬЗУЕТСЯ КАРТА:\n";

$mapUsageFiles = [
    'resources/js/src/features/AdSections/GeoSection/ui/GeoSection.vue',
    'resources/js/src/pages/masters/MastersMap.vue'
];

foreach ($mapUsageFiles as $file) {
    if (file_exists($file)) {
        echo "✅ {$file}\n";
        $content = file_get_contents($file);
        
        // Ищем импорт карты
        if (preg_match('/import.*YandexMap.*from.*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            echo "   📦 Импорт: {$matches[1]}\n";
        }
        
        // Ищем использование компонента
        if (preg_match('/<YandexMap[^>]*>/', $content, $matches)) {
            echo "   🏷️  Использование: " . trim($matches[0]) . "\n";
        }
    } else {
        echo "❌ {$file} - НЕ НАЙДЕН\n";
    }
    echo "\n";
}

// 4. Анализируем проблему
echo "🔍 АНАЛИЗ ПРОБЛЕМЫ:\n";

echo "📋 ЧТО ИЗМЕНИЛОСЬ В РЕФАКТОРИНГЕ:\n";
echo "  1. Монолитный YandexMap.vue (411 строк) → Модульная архитектура\n";
echo "  2. Простая логика loadYandexMaps → Сложная логика с Promise кэшированием\n";
echo "  3. Прямая инициализация карты → Композиция через composables\n";
echo "  4. Одна точка отказа → Множество точек отказа\n\n";

echo "⚠️ ВОЗМОЖНЫЕ ПРИЧИНЫ ПОЛОМКИ:\n";
echo "  1. ❌ Нарушение Promise цепочки в loadYandexMaps\n";
echo "  2. ❌ Неправильная композиция composables\n";
echo "  3. ❌ Потеря простой логики инициализации\n";
echo "  4. ❌ Асинхронные проблемы между компонентами\n\n";

echo "💡 РЕШЕНИЕ (Принцип KISS):\n";
echo "  1. ✅ Упростить loadYandexMaps до рабочей версии\n";
echo "  2. ✅ Упростить useMapInitializer\n";
echo "  3. ✅ Убрать избыточные composables\n";
echo "  4. ✅ Вернуть прямую инициализацию карты\n\n";

// 5. План действий
echo "📋 ПЛАН ИСПРАВЛЕНИЯ:\n";
echo "  Шаг 1: ✅ Упростили loadYandexMaps (ВЫПОЛНЕНО)\n";
echo "  Шаг 2: ✅ Упростили useMapInitializer (ВЫПОЛНЕНО)\n";
echo "  Шаг 3: 🔄 Тестируем на простой HTML странице\n";
echo "  Шаг 4: ⏳ Если работает - проверяем Vue компонент\n";
echo "  Шаг 5: ⏳ Если не работает - возвращаем старую логику\n\n";

echo "🎯 ТЕСТИРОВАНИЕ:\n";
echo "  1. Откройте: http://spa.test/test-map.html\n";
echo "  2. Проверьте работает ли простая карта\n";
echo "  3. Если ДА → проблема в Vue компонентах\n";
echo "  4. Если НЕТ → проблема в API ключе или сети\n\n";

// 6. Проверяем доступность тестовой страницы
$testPage = 'public/test-map.html';
if (file_exists($testPage)) {
    echo "✅ Тестовая страница создана: {$testPage}\n";
    echo "🌐 URL: http://spa.test/test-map.html\n\n";
} else {
    echo "❌ Тестовая страница НЕ найдена: {$testPage}\n\n";
}

echo "🎯 СЛЕДУЮЩИЕ ШАГИ:\n";
echo "  1. Откройте http://spa.test/test-map.html\n";
echo "  2. Посмотрите на результат теста\n";
echo "  3. Проверьте консоль браузера\n";
echo "  4. Сообщите результат\n\n";

echo "✅ ДИАГНОСТИКА ЗАВЕРШЕНА!\n";
?>