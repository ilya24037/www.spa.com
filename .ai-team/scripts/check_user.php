<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domain\User\Models\User;

$user = User::where('email', 'anna@spa.test')->first();

if ($user) {
    echo "✅ Пользователь найден:\n";
    echo "Email: " . $user->email . "\n";
    echo "Имя: " . $user->name . "\n";
    echo "ID: " . $user->id . "\n";
    echo "\n📋 Учетные данные для входа:\n";
    echo "Email: anna@spa.test\n";
    echo "Пароль: password\n";
} else {
    echo "❌ Пользователь anna@spa.test не найден\n";
}