<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$ad = DB::table('ads')->where('id', 32)->first();

echo "ПРОВЕРКА ДВОЙНОГО КОДИРОВАНИЯ:\n";
echo "==========================================\n";

// photos
echo "\nPHOTOS:\n";
echo "Raw: " . substr($ad->photos, 0, 100) . "...\n";

// Первое декодирование
$photos1 = json_decode($ad->photos);
echo "После 1 декодирования: " . (is_string($photos1) ? "строка" : gettype($photos1)) . "\n";

// Второе декодирование
if (is_string($photos1)) {
    $photos2 = json_decode($photos1, true);
    echo "После 2 декодирования: " . (is_array($photos2) ? "массив с " . count($photos2) . " фото" : gettype($photos2)) . "\n";
    if (is_array($photos2)) {
        echo "Фото: \n";
        foreach ($photos2 as $i => $photo) {
            echo "  " . ($i+1) . ". $photo\n";
        }
    }
}

// geo
echo "\nGEO:\n";
echo "Raw: " . substr($ad->geo, 0, 100) . "...\n";

// Первое декодирование
$geo1 = json_decode($ad->geo);
echo "После 1 декодирования: " . (is_string($geo1) ? "строка" : gettype($geo1)) . "\n";

// Второе декодирование
if (is_string($geo1)) {
    $geo2 = json_decode($geo1, true);
    echo "После 2 декодирования: " . (is_array($geo2) ? "массив с ключами: " . implode(", ", array_keys($geo2)) : gettype($geo2)) . "\n";
    if (is_array($geo2)) {
        echo "Адрес: " . ($geo2['address'] ?? 'нет') . "\n";
        echo "Координаты: lat=" . ($geo2['coordinates']['lat'] ?? '?') . ", lng=" . ($geo2['coordinates']['lng'] ?? '?') . "\n";
    }
}