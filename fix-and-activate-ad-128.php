<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Дополнение и активация объявления ID 128...\n";

// Найдем объявление
$ad = \App\Domain\Ad\Models\Ad::find(128);

if (!$ad) {
    echo "❌ Объявление с ID 128 не найдено\n";
    exit(1);
}

echo "📋 Текущие данные объявления:\n";
echo "ID: {$ad->id}\n";
echo "Статус: {$ad->status->value}\n";
echo "Название: {$ad->title}\n";
echo "Цена: " . ($ad->price ?: 'НЕ УКАЗАНА') . "\n";
echo "Телефон: " . ($ad->phone ?: 'НЕ УКАЗАН') . "\n";

// Дополним недостающие поля
$updates = [];

if (empty($ad->price)) {
    $updates['price'] = 3000; // Стандартная цена
    echo "✏️ Добавляю цену: 3000 руб\n";
}

if (empty($ad->phone)) {
    $updates['phone'] = '+7 999 123 45 67'; // Тестовый телефон
    echo "✏️ Добавляю телефон: +7 999 123 45 67\n";
}

if (empty($ad->description)) {
    $updates['description'] = 'Профессиональный эротический массаж. Расслабляющие процедуры с индивидуальным подходом к каждому клиенту.';
    echo "✏️ Добавляю описание\n";
}

// Активируем объявление
$updates['status'] = \App\Domain\Ad\Enums\AdStatus::ACTIVE;

try {
    $ad->update($updates);
    
    echo "\n✅ Объявление успешно дополнено и активировано!\n";
    
    // Показываем обновленные данные
    $ad->refresh();
    echo "\n📋 Обновленные данные:\n";
    echo "Статус: {$ad->status->value}\n";
    echo "Цена: {$ad->price} руб\n";
    echo "Телефон: {$ad->phone}\n";
    echo "Описание: " . substr($ad->description, 0, 50) . "...\n";
    echo "\n🌐 URL: http://spa.test/ads/{$ad->id}\n";
    
} catch (\Exception $e) {
    echo "\n❌ Ошибка при обновлении объявления:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Готово! Объявление активно и доступно по ссылке.\n";