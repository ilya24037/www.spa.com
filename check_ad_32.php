<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$ad = DB::table('ads')->where('id', 32)->first();

echo "ОБЪЯВЛЕНИЕ ID 32:\n";
echo "==========================================\n";
echo "specialty: {$ad->specialty}\n";
echo "work_format: {$ad->work_format}\n";
echo "service_provider: {$ad->service_provider}\n";
echo "travel_area: {$ad->travel_area}\n";
echo "\nphotos (raw): {$ad->photos}\n";
echo "geo (raw): {$ad->geo}\n";

// Проверим декодирование
$photos = json_decode($ad->photos, true);
$geo = json_decode($ad->geo, true);

echo "\nДекодированные:\n";
echo "photos decoded: " . (is_array($photos) ? "массив с " . count($photos) . " элементами" : "не массив") . "\n";
echo "geo decoded: " . (is_array($geo) ? "массив с ключами: " . implode(", ", array_keys($geo)) : "не массив") . "\n";