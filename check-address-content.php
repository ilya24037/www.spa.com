<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$ads = App\Domain\Ad\Models\Ad::whereIn('id', [168, 199])
    ->select('id', 'title', 'address')
    ->get();

foreach($ads as $ad) {
    echo 'ID: ' . $ad->id . PHP_EOL;
    echo 'Title: ' . $ad->title . PHP_EOL;
    echo 'Address: ' . $ad->address . PHP_EOL;
    echo '---' . PHP_EOL;
}

