<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\Schema;
use App\Domain\Ad\Models\Ad;

// Проверяем структуру таблицы ads
echo "=== ПРОВЕРКА ТАБЛИЦЫ ADS ===\n";
$columns = Schema::getColumnListing('ads');
echo "Всего колонок: " . count($columns) . "\n";

// Ищем колонку photos
$hasPhotos = in_array('photos', $columns);
echo "Колонка 'photos' " . ($hasPhotos ? "СУЩЕСТВУЕТ" : "НЕ СУЩЕСТВУЕТ") . "\n";

if ($hasPhotos) {
    // Проверяем тип колонки
    $columnType = Schema::getColumnType('ads', 'photos');
    echo "Тип колонки 'photos': " . $columnType . "\n";
}

// Проверяем объявление 166
echo "\n=== ПРОВЕРКА ОБЪЯВЛЕНИЯ 166 ===\n";
$ad = Ad::find(166);

if ($ad) {
    echo "Объявление найдено\n";
    echo "Значение photos: " . var_export($ad->photos, true) . "\n";
    echo "Тип photos: " . gettype($ad->photos) . "\n";
    
    // Пробуем сохранить тестовое фото
    echo "\n=== ТЕСТ СОХРАНЕНИЯ ФОТО ===\n";
    $testPhoto = [
        'id' => 'test-' . time(),
        'preview' => 'data:image/jpeg;base64,test',
        'name' => 'test.jpg',
        'size' => 1024
    ];
    
    $ad->photos = [$testPhoto];
    $ad->save();
    
    // Перезагружаем и проверяем
    $ad->refresh();
    echo "После сохранения photos: " . var_export($ad->photos, true) . "\n";
} else {
    echo "Объявление не найдено\n";
}

// Проверяем все JSON поля
echo "\n=== ПРОВЕРКА ВСЕХ JSON ПОЛЕЙ ===\n";
$jsonColumns = ['clients', 'service_location', 'outcall_locations', 'service_provider', 
                'features', 'services', 'schedule', 'geo', 'photos', 'video'];

foreach ($jsonColumns as $column) {
    $exists = in_array($column, $columns);
    echo "- $column: " . ($exists ? "существует" : "НЕ существует") . "\n";
}