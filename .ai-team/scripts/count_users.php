<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domain\User\Models\User;

$count = User::count();
$users = User::all(['id', 'email', 'name']);

echo "📊 Статистика пользователей в базе данных\n";
echo "==========================================\n\n";
echo "Всего пользователей: " . $count . "\n\n";
echo "Список пользователей:\n";
echo "--------------------\n";

foreach ($users as $user) {
    echo "ID: " . $user->id . " | Email: " . $user->email . " | Имя: " . $user->name . "\n";
}