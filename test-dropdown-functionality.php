<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Тестирование нового dropdown функционала для активных объявлений...\n\n";

// Проверяем что есть активные объявления для тестирования
$activeAds = \App\Domain\Ad\Models\Ad::where('status', 'active')->limit(3)->get();

echo "📋 АКТИВНЫЕ ОБЪЯВЛЕНИЯ ДЛЯ ТЕСТИРОВАНИЯ:\n";
echo "====================================\n";

if ($activeAds->count() > 0) {
    foreach ($activeAds as $ad) {
        echo "ID: {$ad->id} | Название: {$ad->title} | Статус: {$ad->status->value}\n";
    }
    
    echo "\n✅ Найдено " . $activeAds->count() . " активных объявлений\n";
    echo "🌐 Ссылки для тестирования:\n";
    echo "Dashboard: http://spa.test/dashboard\n";
    echo "Demo ItemCard: http://spa.test/demo/item-card\n";
    
    echo "\n🎯 ЧТО ТЕСТИРОВАТЬ:\n";
    echo "==================\n";
    echo "1. Открыть Dashboard или Demo страницу\n";
    echo "2. Найти активное объявление\n";
    echo "3. Проверить что вместо кнопок 'Продвинуть', 'Редактировать', 'Снять'\n";
    echo "   появилась кнопка с тремя точками (⋯)\n";
    echo "4. Кликнуть на кнопку с тремя точками\n";
    echo "5. Убедиться что открывается dropdown меню с пунктами:\n";
    echo "   - ✅ Поднять просмотры\n";
    echo "   - ✅ Редактировать\n";
    echo "   - ✅ Забронировать\n";
    echo "   - ✅ Снять с публикации (красным цветом)\n";
    echo "6. Протестировать каждый пункт меню\n";
    
    echo "\n💡 ОЖИДАЕМОЕ ПОВЕДЕНИЕ:\n";
    echo "=====================\n";
    echo "- Поднять просмотры → Переход на страницу продвижения\n";
    echo "- Редактировать → Переход к редактированию объявления\n";
    echo "- Забронировать → Переход к странице с параметром ?booking=true\n";
    echo "- Снять с публикации → Деактивация объявления\n";
    
} else {
    echo "❌ Активные объявления не найдены\n";
    echo "💡 Для тестирования можно активировать объявление ID 128:\n";
    echo "Команда: php fix-and-activate-ad-128.php\n";
}

echo "\n🔧 ИЗМЕНЕНИЯ В КОДЕ:\n";
echo "===================\n";
echo "✅ ItemActions.vue - заменены кнопки на dropdown для active статуса\n";
echo "✅ ItemCard.vue - добавлен обработчик @book='bookItem'\n";
echo "✅ ItemCard.types.ts - добавлен emit 'book'\n";
echo "✅ Dashboard.vue - добавлены все обработчики событий\n";

echo "\n🎉 Тестирование готово к запуску!\n";