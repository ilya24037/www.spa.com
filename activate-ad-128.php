<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Активация объявления ID 128...\n";

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
echo "Пользователь: {$ad->user_id}\n";
echo "Создано: {$ad->created_at}\n";
echo "Обновлено: {$ad->updated_at}\n";

// Проверим обязательные поля для активации
$requiredFields = [
    'title' => $ad->title,
    'description' => $ad->description,
    'price' => $ad->price,
    'phone' => $ad->phone,
    'category' => $ad->category,
    'specialty' => $ad->specialty
];

$missingFields = [];
foreach ($requiredFields as $field => $value) {
    if (empty($value)) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    echo "\n❌ Не хватает обязательных полей для активации:\n";
    foreach ($missingFields as $field) {
        echo "- {$field}\n";
    }
    echo "\nДля активации объявления заполните эти поля.\n";
    exit(1);
}

// Если статус уже active
if ($ad->status->value === 'active') {
    echo "\n✅ Объявление уже активно!\n";
    echo "URL: http://spa.test/ads/{$ad->id}\n";
    exit(0);
}

// Активируем объявление
try {
    $ad->update([
        'status' => \App\Domain\Ad\Enums\AdStatus::ACTIVE
    ]);
    
    // Обновляем время последнего изменения
    $ad->touch();
    
    echo "\n✅ Объявление успешно активировано!\n";
    echo "Новый статус: {$ad->fresh()->status->value}\n";
    echo "URL: http://spa.test/ads/{$ad->id}\n";
    
} catch (\Exception $e) {
    echo "\n❌ Ошибка при активации объявления:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Готово! Объявление теперь доступно по ссылке выше.\n";