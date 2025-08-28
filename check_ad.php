<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Ищем объявление
$ad = App\Domain\Ad\Models\Ad::find(52);
if($ad) {
    echo 'Found ad ID: ' . $ad->id . PHP_EOL;
    echo 'Slug: ' . $ad->slug . PHP_EOL; 
    echo 'Title: ' . $ad->title . PHP_EOL;
    echo 'Name: ' . $ad->name . PHP_EOL;
    echo 'Description (50 chars): ' . mb_substr($ad->description ?? '', 0, 50) . PHP_EOL;
} else {
    echo 'Ad ID 52 not found' . PHP_EOL;
    // Пробуем найти по части slug
    $ads = App\Domain\Ad\Models\Ad::where('slug', 'LIKE', '%liusia%')->get();
    echo 'Found ads with liusia in slug: ' . $ads->count() . PHP_EOL;
    foreach($ads as $a) {
        echo 'ID: ' . $a->id . ', Slug: ' . $a->slug . PHP_EOL;
    }
}
