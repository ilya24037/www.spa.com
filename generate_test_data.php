<?php

// Скрипт для генерации тестовых данных

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;

echo "=== ГЕНЕРАЦИЯ ТЕСТОВЫХ ДАННЫХ ===\n\n";

// Получаем или создаём пользователя
$user = User::first();
if (!$user) {
    echo "Создаём тестового пользователя...\n";
    $user = User::factory()->create([
        'email' => 'test@spa.com',
        'role' => 'client'
    ]);
}

echo "Используем пользователя: {$user->email}\n\n";

// Создаём объявления с разными статусами
echo "Создаём 30 объявлений на паузе (для модерации)...\n";
Ad::factory()->count(30)->create([
    'user_id' => $user->id,
    'status' => 'paused'
]);

echo "Создаём 10 активных объявлений...\n";
Ad::factory()->count(10)->create([
    'user_id' => $user->id,
    'status' => 'active'
]);

echo "Создаём 10 черновиков...\n";
Ad::factory()->count(10)->create([
    'user_id' => $user->id,
    'status' => 'draft'
]);

// Выводим статистику
$stats = Ad::selectRaw('status, COUNT(*) as count')
    ->groupBy('status')
    ->pluck('count', 'status');

echo "\n=== СТАТИСТИКА ===\n";
foreach ($stats as $status => $count) {
    echo "  $status: $count\n";
}
echo "  Всего: " . Ad::count() . "\n";

// Создаём админа если его нет
$admin = User::where('role', 'admin')->first();
if (!$admin) {
    echo "\nСоздаём администратора...\n";
    $admin = User::where('email', 'anna@spa.test')->first();
    if ($admin) {
        $admin->role = 'admin';
        $admin->save();
        echo "Пользователь anna@spa.test теперь администратор\n";
    } else {
        $admin = User::factory()->create([
            'email' => 'admin@spa.com',
            'role' => 'admin',
            'password' => bcrypt('password')
        ]);
        echo "Создан администратор: admin@spa.com (пароль: password)\n";
    }
}

echo "\n=== ГОТОВО ===\n";
echo "Тестовые данные успешно созданы!\n";
echo "Теперь можно зайти под админом и проверить модерацию.\n";