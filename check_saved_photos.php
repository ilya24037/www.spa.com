<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Domain\Ad\Models\Ad;

$ad = Ad::find(166);

if ($ad) {
    echo "=== ОБЪЯВЛЕНИЕ 166 ===\n";
    echo "Заголовок: " . ($ad->title ?: '(пусто)') . "\n";
    echo "Статус: " . $ad->status->value . "\n";
    
    $photos = $ad->photos;
    
    if (is_array($photos)) {
        echo "\n=== ФОТОГРАФИИ (" . count($photos) . " шт) ===\n";
        foreach ($photos as $index => $photo) {
            echo "\nФото #" . ($index + 1) . ":\n";
            echo "- ID: " . ($photo['id'] ?? 'нет') . "\n";
            echo "- Имя: " . ($photo['name'] ?? 'нет') . "\n";
            echo "- Размер: " . ($photo['size'] ?? 'нет') . " байт\n";
            echo "- Поворот: " . ($photo['rotation'] ?? 0) . "°\n";
            echo "- Новое: " . (isset($photo['isNew']) && $photo['isNew'] ? 'да' : 'нет') . "\n";
            echo "- Есть preview: " . (isset($photo['preview']) ? 'да (' . strlen($photo['preview']) . ' символов)' : 'нет') . "\n";
        }
    } else {
        echo "\nФото: " . json_encode($photos) . "\n";
    }
    
    // Проверяем JSON в БД
    echo "\n=== RAW JSON ИЗ БД ===\n";
    $rawAd = \DB::table('ads')->where('id', 166)->first();
    if ($rawAd && $rawAd->photos) {
        $decoded = json_decode($rawAd->photos, true);
        echo "Декодировано успешно: " . (is_array($decoded) ? 'да' : 'нет') . "\n";
        echo "Количество элементов: " . (is_array($decoded) ? count($decoded) : 0) . "\n";
    }
} else {
    echo "Объявление не найдено\n";
}