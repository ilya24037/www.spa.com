<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Domain\User\Models\User::where('email', 'anna@spa.test')->first();
if ($user) {
    echo "Email: {$user->email}\n";
    echo "Role raw value: ";
    var_dump($user->role);
    echo "Role type: " . gettype($user->role) . "\n";

    // Если это Enum
    if ($user->role instanceof \App\Enums\UserRole) {
        echo "Role enum value: " . $user->role->value . "\n";
        echo "Role label: " . $user->role->getLabel() . "\n";
    }

    // Обновляем роль на админа
    echo "\nОбновляю роль на admin...\n";
    $user->role = \App\Enums\UserRole::ADMIN;
    $user->save();

    echo "Роль обновлена!\n";

    // Проверяем
    $user->refresh();
    echo "Новая роль: " . ($user->role instanceof \App\Enums\UserRole ? $user->role->value : $user->role) . "\n";
    echo "isAdmin(): " . ($user->isAdmin() ? 'true' : 'false') . "\n";
} else {
    echo "Пользователь anna@spa.test не найден!\n";
}