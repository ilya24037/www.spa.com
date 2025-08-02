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
    echo "Объявление найдено!\n";
    echo "ID: " . $ad->id . "\n";
    echo "Заголовок: " . $ad->title . "\n";
    echo "Фото в базе данных:\n";
    
    $photos = $ad->photos;
    
    if (is_array($photos)) {
        echo "Количество фото: " . count($photos) . "\n";
        foreach ($photos as $index => $photo) {
            echo "\nФото #" . ($index + 1) . ":\n";
            print_r($photo);
        }
    } else {
        echo "Фото: " . json_encode($photos) . "\n";
    }
} else {
    echo "Объявление не найдено\n";
}