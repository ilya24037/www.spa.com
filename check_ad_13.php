<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domain\Ad\Models\Ad;

$ad = Ad::find(13);
if ($ad) {
    echo "Ad ID 13 details:\n";
    echo "Title: " . $ad->title . "\n";
    echo "Status: " . $ad->status->value . "\n";
    echo "Is Published: " . ($ad->is_published ? 'Yes' : 'No') . "\n";
    echo "Created at: " . $ad->created_at . "\n";
    echo "User ID: " . $ad->user_id . "\n";
} else {
    echo "Ad 13 not found\n";
}
