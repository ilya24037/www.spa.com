<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domain\Ad\Models\Ad;

echo "=== Ads Statistics ===\n";
echo "Total ads: " . Ad::count() . "\n";
echo "Active: " . Ad::where('status', 'active')->count() . "\n";
echo "Pending moderation: " . Ad::where('status', 'pending_moderation')->count() . "\n";
echo "Draft: " . Ad::where('status', 'draft')->count() . "\n\n";

echo "=== Ads Details ===\n";
Ad::all()->each(function($ad) {
    echo "ID: {$ad->id} | Title: {$ad->title}\n";
    echo "  Status: {$ad->status->value} | Published: " . ($ad->is_published ? 'Yes' : 'No') . "\n";
    echo "  Created: {$ad->created_at}\n\n";
});
