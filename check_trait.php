<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Domain\User\Models\User::where('email', 'anna@spa.test')->first();

echo "=== ФИНАЛЬНАЯ ПРОВЕРКА ===\n\n";

// Проверяем трейты
$reflection = new ReflectionClass($user);
$traits = $reflection->getTraitNames();
echo "Используемые трейты:\n";
foreach($traits as $trait) {
    echo " - $trait\n";
}

// Проверяем метод isAdmin
echo "\nМетод isAdmin():\n";
try {
    $method = $reflection->getMethod('isAdmin');
    echo " - Объявлен в: " . $method->getDeclaringClass()->getName() . "\n";
    echo " - Файл: " . $method->getFileName() . "\n";
    echo " - Строка: " . $method->getStartLine() . "\n";
} catch (Exception $e) {
    echo " - Ошибка: " . $e->getMessage() . "\n";
}

// Проверяем результат
echo "\nРезультаты проверок:\n";
echo " - Role value: " . $user->role->value . "\n";
echo " - isAdmin(): " . ($user->isAdmin() ? 'true' : 'false') . "\n";
echo " - isStaff(): " . ($user->isStaff() ? 'true' : 'false') . "\n";
echo " - hasPermission('moderate_ads'): " . ($user->hasPermission('moderate_ads') ? 'true' : 'false') . "\n";
echo " - hasPermission('manage_users'): " . ($user->hasPermission('manage_users') ? 'true' : 'false') . "\n";

// Проверяем, откуда берется метод hasPermission
try {
    $method = $reflection->getMethod('hasPermission');
    echo "\nМетод hasPermission():\n";
    echo " - Объявлен в: " . $method->getDeclaringClass()->getName() . "\n";
} catch (Exception $e) {
    echo "\nМетод hasPermission() не найден: " . $e->getMessage() . "\n";
}