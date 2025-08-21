<?php

require_once "vendor/autoload.php";

$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo " ПОЛНАЯ ПРОВЕРКА ВСЕХ ДАННЫХ НА KRAKOZYABRY...\n\n";

// 1. Проверяем всех мастеров
echo "1?? ПРОВЕРКА ВСЕХ МАСТЕРОВ:\n";
$masters = \App\Domain\Master\Models\MasterProfile::all();
foreach ($masters as $master) {
    echo "Мастер {$master->id}: {$master->display_name}\n";
}
echo "\n";

// 2. Проверяем объявления
echo "2?? ПРОВЕРКА ОБЪЯВЛЕНИЙ:\n";
$ads = \App\Domain\Ad\Models\Ad::all();
foreach ($ads as $ad) {
    echo "Объявление {$ad->id}: {$ad->title}\n";
}
echo "\n";

// 3. Проверяем пользователей
echo "3?? ПРОВЕРКА ПОЛЬЗОВАТЕЛЕЙ:\n";
$users = \App\Models\User::all();
foreach ($users as $user) {
    echo "Пользователь {$user->id}: {$user->name} ({$user->email})\n";
}
echo "\n";

echo "? Проверка завершена!\n";
