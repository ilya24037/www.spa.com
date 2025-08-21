<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FULL DATA CHECK FOR KRAKOZYABRY ===\n\n";

// 1. Check all masters
echo "1. CHECKING ALL MASTERS:\n";
$masters = \App\Domain\Master\Models\MasterProfile::all();
foreach ($masters as $master) {
    echo "Master {$master->id}: {$master->display_name}\n";
}
echo "\n";

// 2. Check ads
echo "2. CHECKING ADS:\n";
$ads = \App\Domain\Ad\Models\Ad::all();
foreach ($ads as $ad) {
    echo "Ad {$ad->id}: {$ad->title}\n";
}
echo "\n";

// 3. Check users
echo "3. CHECKING USERS:\n";
$users = \App\Models\User::all();
foreach ($users as $user) {
    echo "User {$user->id}: {$user->name} ({$user->email})\n";
}
echo "\n";

echo "=== CHECK COMPLETED ===\n";
