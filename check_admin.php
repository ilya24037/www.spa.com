<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\User\Models\User;

// Проверяем администратора
$admin = User::where('email', 'admin@spa.com')->first();

if ($admin) {
    echo "✅ Admin user found!\n";
    echo "Email: " . $admin->email . "\n";
    echo "Name: " . $admin->name . "\n";
    echo "Role: " . $admin->role . "\n";
    echo "Role type: " . gettype($admin->role) . "\n";

    if ($admin->role instanceof \App\Enums\UserRole) {
        echo "Role enum value: " . $admin->role->value . "\n";
        echo "Is admin: " . ($admin->role === \App\Enums\UserRole::ADMIN ? 'YES' : 'NO') . "\n";
    }

    // Проверяем canAccessPanel
    $panel = \Filament\Facades\Filament::getDefaultPanel();
    $canAccess = $admin->canAccessPanel($panel);
    echo "Can access panel: " . ($canAccess ? 'YES' : 'NO') . "\n";
} else {
    echo "❌ Admin user not found!\n";
    echo "Please run: php artisan db:seed --class=AdminSeeder\n";
}

// Проверяем всех пользователей с ролью admin
echo "\n📊 All users with admin role:\n";
$admins = User::all()->filter(function($user) {
    return $user->role === \App\Enums\UserRole::ADMIN ||
           $user->role === 'admin';
});

foreach ($admins as $admin) {
    echo "- " . $admin->email . " (role: " . $admin->role . ")\n";
}