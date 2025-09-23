<?php

// Обновление учетных данных администратора

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\User\Models\User;

echo "=== ОБНОВЛЕНИЕ АДМИНИСТРАТОРА ===\n\n";

// Находим существующего админа
$admin = User::where('role', 'admin')->first();

if (!$admin) {
    // Если админа нет, находим anna@spa.test и делаем админом
    $admin = User::where('email', 'anna@spa.test')->first();
    if ($admin) {
        $admin->role = 'admin';
    } else {
        // Создаём нового админа
        $admin = new User();
        $admin->role = 'admin';
        echo "Создаём нового администратора...\n";
    }
} else {
    echo "Обновляем существующего администратора...\n";
}

// Обновляем данные
$admin->email = 'admin';
$admin->password = bcrypt('admin');
$admin->email_verified_at = now();

// Сохраняем
try {
    $admin->save();
    echo "\n✅ УСПЕШНО!\n";
    echo "━━━━━━━━━━━━━━━━━━━━\n";
    echo "Email: admin\n";
    echo "Пароль: admin\n";
    echo "━━━━━━━━━━━━━━━━━━━━\n";
    echo "\nТеперь можно войти в админ-панель:\n";
    echo "1. Перейдите на http://spa.test/login\n";
    echo "2. Введите email: admin\n";
    echo "3. Введите пароль: admin\n";
    echo "\nПосле входа в левой панели будет блок 'Администрирование'\n";
} catch (\Exception $e) {
    echo "\n❌ ОШИБКА: " . $e->getMessage() . "\n";
}