<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domain\Ad\Models\Ad;

echo "=== Latest Ads ===\n";
$ads = Ad::orderBy('id', 'desc')->limit(5)->get();
foreach($ads as $ad) {
    echo "ID: {$ad->id} | Title: {$ad->title} | Status: {$ad->status->value} | Published: " . ($ad->is_published ? 'Yes' : 'No') . "\n";
}