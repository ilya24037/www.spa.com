<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$ads = App\Domain\Ad\Models\Ad::where('status', 'draft')
    ->select('id', 'title', 'address')
    ->limit(10)
    ->get();

echo 'Найдено черновиков: ' . $ads->count() . PHP_EOL . PHP_EOL;

foreach($ads as $ad) {
    echo 'ID: ' . $ad->id . PHP_EOL;
    echo 'Title: ' . $ad->title . PHP_EOL;
    echo 'Address: ' . $ad->address . PHP_EOL;
    echo '---' . PHP_EOL;
}

