<?php

// Проверка структуры БД

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Структура таблицы users ===\n";
$columns = DB::select('SHOW COLUMNS FROM users');
foreach ($columns as $column) {
    echo $column->Field . " - " . $column->Type . "\n";
    if (strpos($column->Field, 'role') !== false || strpos($column->Field, 'type') !== false) {
        echo "  ^ Найдено поле для роли!\n";
    }
}

echo "\n=== Пример пользователей ===\n";
$users = DB::select('SELECT id, email FROM users LIMIT 3');
foreach ($users as $user) {
    echo "ID: {$user->id}, Email: {$user->email}\n";
}