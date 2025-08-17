<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$ad = App\Domain\Ad\Models\Ad::where('status', 'draft')->first();
if($ad) {
    echo 'Draft ID: ' . $ad->id . PHP_EOL;
    echo 'Title: ' . $ad->title . PHP_EOL;
    echo 'Address: ' . $ad->address . PHP_EOL;
    echo 'City: ' . $ad->city . PHP_EOL;
    echo 'Geo: ' . $ad->geo . PHP_EOL;
    echo PHP_EOL;
    
    // Проверяем что передается во фронтенд
    $data = $ad->toArray();
    echo 'Data for frontend:' . PHP_EOL;
    echo 'address: ' . ($data['address'] ?? 'null') . PHP_EOL;
    echo 'city: ' . ($data['city'] ?? 'null') . PHP_EOL;
} else {
    echo 'No drafts found' . PHP_EOL;
}