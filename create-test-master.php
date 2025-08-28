<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Models\User;

// Инициализируем Laravel
$app = new Application(dirname(__FILE__));
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Application\Http\Kernel::class
);
$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Application\Console\Kernel::class
);
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "🔧 Создание тестового мастера ID=3...\n";

try {
    // Проверяем, есть ли мастер с ID 3
    $existingMaster = MasterProfile::find(3);
    if ($existingMaster) {
        echo "✅ Мастер с ID 3 уже существует: " . $existingMaster->name . "\n";
        echo "Slug: " . ($existingMaster->slug ?? 'НЕТ SLUG') . "\n";
        echo "URL: /masters/" . ($existingMaster->slug ?? 'master') . "-3\n";
        exit(0);
    }

    // Создаём тестового пользователя если нет
    $user = User::firstOrCreate([
        'email' => 'elena@spa.test'
    ], [
        'name' => 'Елена',
        'password' => bcrypt('password'),
        'email_verified_at' => now()
    ]);

    echo "📝 Пользователь создан/найден: " . $user->name . " (ID: " . $user->id . ")\n";

    // Создаём мастера с ID 3
    $master = new MasterProfile();
    $master->id = 3;
    $master->user_id = $user->id;
    $master->name = 'Елена';
    $master->slug = 'sportivnyj-massaz-ot-eleny';
    $master->display_name = 'Елена - Спортивный массаж';
    $master->specialty = 'Массаж';
    $master->description = 'Профессиональный спортивный массаж. Опыт более 5 лет.';
    $master->rating = 4.8;
    $master->reviews_count = 27;
    $master->completion_rate = '98%';
    $master->experience = '5+ лет';
    $master->location = 'Москва';
    $master->city = 'Москва';
    $master->phone = '+7 (999) 123-45-67';
    $master->price_from = 2000;
    $master->price_to = 5000;
    $master->status = 'active';
    $master->is_verified = true;
    $master->is_premium = true;
    
    // Создаём запись напрямую в БД (чтобы установить конкретный ID)
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    $master->save();
    DB::statement('SET FOREIGN_KEY_CHECKS=1');

    echo "✅ Тестовый мастер создан!\n";
    echo "ID: " . $master->id . "\n";
    echo "Имя: " . $master->name . "\n"; 
    echo "Slug: " . $master->slug . "\n";
    echo "URL: /masters/" . $master->slug . "-" . $master->id . "\n";

    echo "\n🎯 Теперь можно открыть: http://spa.test/masters/" . $master->slug . "-" . $master->id . "\n";

} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
}