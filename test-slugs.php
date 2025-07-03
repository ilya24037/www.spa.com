<?php

use App\Models\User;
use App\Models\MasterProfile;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 Тестируем генерацию уникальных slug...\n\n";

// Тест 1: Создаём трёх мастеров с именем "Анна"
echo "📝 Тест 1: Создаём трёх мастеров с именем 'Анна'\n";
echo str_repeat('-', 50) . "\n";

for ($i = 1; $i <= 3; $i++) {
    try {
        // Создаём пользователя
        $user = User::create([
            'name' => 'Анна',
            'email' => "anna.test{$i}@example.com",
            'password' => bcrypt('password'),
            'role' => 'master',
        ]);
        
        // Создаём профиль мастера
        $profile = MasterProfile::create([
            'user_id' => $user->id,
            'display_name' => 'Анна',
            'city' => 'Москва',
            'status' => 'active',
        ]);
        
        echo "✅ Создан профиль #{$i}: display_name = '{$profile->display_name}', slug = '{$profile->slug}'\n";
        
    } catch (\Exception $e) {
        echo "❌ Ошибка при создании профиля #{$i}: " . $e->getMessage() . "\n";
    }
}

// Тест 2: Создаём мастеров с разными именами
echo "\n📝 Тест 2: Создаём мастеров с разными именами\n";
echo str_repeat('-', 50) . "\n";

$names = ['Мария Иванова', 'Елена', 'Ольга Петрова', 'Наталья'];

foreach ($names as $index => $name) {
    try {
        $user = User::create([
            'name' => $name,
            'email' => Str::slug($name) . "@example.com",
            'password' => bcrypt('password'),
            'role' => 'master',
        ]);
        
        $profile = MasterProfile::create([
            'user_id' => $user->id,
            'display_name' => $name,
            'city' => 'Москва',
            'status' => 'active',
        ]);
        
        echo "✅ Создан профиль: display_name = '{$profile->display_name}', slug = '{$profile->slug}'\n";
        
    } catch (\Exception $e) {
        echo "❌ Ошибка: " . $e->getMessage() . "\n";
    }
}

// Показываем итоговую таблицу
echo "\n📊 Итоговая таблица всех slug:\n";
echo str_repeat('-', 70) . "\n";
echo sprintf("%-30s | %-30s\n", "Display Name", "Slug");
echo str_repeat('-', 70) . "\n";

$profiles = MasterProfile::orderBy('created_at', 'desc')->limit(10)->get();
foreach ($profiles as $profile) {
    echo sprintf("%-30s | %-30s\n", $profile->display_name, $profile->slug);
}

echo "\n✅ Тестирование завершено!\n";