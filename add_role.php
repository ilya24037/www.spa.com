<?php

// Добавление поля role в таблицу users

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Проверяем, есть ли уже поле role
    $hasRole = false;
    $columns = DB::select('SHOW COLUMNS FROM users');
    foreach ($columns as $column) {
        if ($column->Field === 'role') {
            $hasRole = true;
            break;
        }
    }

    if (!$hasRole) {
        echo "Добавляем поле role...\n";
        DB::statement("ALTER TABLE users ADD COLUMN role ENUM('client', 'master', 'admin', 'moderator') DEFAULT 'client' AFTER status");
        echo "✓ Поле role добавлено\n";
    } else {
        echo "Поле role уже существует\n";
    }

    // Делаем первого пользователя админом
    DB::update("UPDATE users SET role = 'admin' WHERE id = 1");
    echo "✓ Пользователь ID=1 теперь админ\n";

    // Проверяем
    $admin = DB::selectOne("SELECT id, email, role FROM users WHERE id = 1");
    echo "Админ: {$admin->email} (роль: {$admin->role})\n";

    echo "\n✓ Готово!\n";

} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}