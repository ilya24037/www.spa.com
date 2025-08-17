<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Обновление адреса объявления ID 128...\n";

// Найдем объявление
$ad = \App\Domain\Ad\Models\Ad::find(128);

if (!$ad) {
    echo "❌ Объявление с ID 128 не найдено\n";
    exit(1);
}

echo "📋 Текущие данные объявления:\n";
echo "ID: {$ad->id}\n";
echo "Название: {$ad->title}\n";
echo "Текущий адрес: " . ($ad->address ?: 'НЕ УКАЗАН') . "\n";

// Новый адрес
$newAddress = "Пермь, ул. Адмирала Ушакова, 10";

try {
    $ad->update([
        'address' => $newAddress
    ]);
    
    echo "\n✅ Адрес успешно обновлен!\n";
    echo "Новый адрес: {$newAddress}\n";
    echo "\n🌐 URL редактирования: http://spa.test/ads/{$ad->id}/edit\n";
    echo "🌐 URL объявления: http://spa.test/ads/{$ad->id}\n";
    
} catch (\Exception $e) {
    echo "\n❌ Ошибка при обновлении адреса:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Готово! Адрес обновлен.\n";