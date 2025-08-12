<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;

// Создаем тестовый черновик с is_starting_price = true
$testAd = Ad::create([
    'user_id' => 1,
    'title' => 'Test draft with is_starting_price',
    'is_starting_price' => true,
    'status' => 'draft'
]);

echo "Created test ad with ID: " . $testAd->id . "\n";
echo "is_starting_price after create: " . ($testAd->is_starting_price ? 'true' : 'false') . "\n";
echo "Type: " . gettype($testAd->is_starting_price) . "\n\n";

// Загружаем обратно
$loaded = Ad::find($testAd->id);
echo "is_starting_price after load: " . ($loaded->is_starting_price ? 'true' : 'false') . "\n";
echo "Type: " . gettype($loaded->is_starting_price) . "\n\n";

// Проверяем toArray
$array = $loaded->toArray();
echo "is_starting_price in toArray: " . ($array['is_starting_price'] ? 'true' : 'false') . "\n";
echo "Type: " . gettype($array['is_starting_price']) . "\n";

// Удаляем тестовую запись
$testAd->delete();
echo "\nTest ad deleted\n";