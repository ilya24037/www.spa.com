<?php
echo "🔍 ДИАГНОСТИКА ЗАГРУЗКИ КАРТЫ\n";
echo "==============================\n\n";

echo "📋 1. ПРОВЕРКА ФАЙЛОВ МОДУЛЬНОЙ АРХИТЕКТУРЫ:\n\n";

$files_to_check = [
    'resources/js/src/features/map/core/MapLoader.ts',
    'resources/js/src/features/map/core/MapCore.vue',
    'resources/js/src/features/map/components/MapContainer.vue',
    'resources/js/src/shared/ui/molecules/YandexMapPicker/YandexMap.vue',
    'resources/js/src/features/map/utils/mapConstants.ts',
    'resources/js/src/features/map/utils/mapHelpers.ts',
    'resources/js/src/features/map/core/MapStore.ts'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ {$file}\n";
    } else {
        echo "❌ {$file} - НЕ НАЙДЕН!\n";
    }
}

echo "\n📋 2. ПРОВЕРКА ПЛАГИНОВ:\n\n";

$plugin_files = [
    'resources/js/src/features/map/plugins/MarkersPlugin.ts',
    'resources/js/src/features/map/plugins/ClusterPlugin.ts',
    'resources/js/src/features/map/plugins/GeolocationPlugin.ts',
    'resources/js/src/features/map/plugins/SearchPlugin.ts'
];

foreach ($plugin_files as $file) {
    if (file_exists($file)) {
        echo "✅ {$file}\n";
    } else {
        echo "❌ {$file} - НЕ НАЙДЕН!\n";
    }
}

echo "\n📋 3. ПРОВЕРКА HELPERS:\n\n";

$helper_files = [
    'resources/js/src/features/map/utils/mapHelpers.ts'
];

foreach ($helper_files as $file) {
    if (file_exists($file)) {
        echo "✅ {$file}\n";
        echo "   Размер: " . round(filesize($file) / 1024, 2) . " KB\n";
    } else {
        echo "❌ {$file} - НЕ НАЙДЕН!\n";
    }
}

echo "\n🎯 РЕКОМЕНДАЦИИ:\n";
echo "1. Если есть отсутствующие файлы - создать их\n";
echo "2. Проверить browser console на ошибки импорта\n";
echo "3. Убедиться что API ключ правильный\n";