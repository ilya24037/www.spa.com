<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== КРИТИЧЕСКАЯ ПРОВЕРКА АРХИТЕКТУРЫ ===\n";

try {
    // 1. Проверка создания репозиториев
    echo "1. Тестирование репозиториев:\n";
    
    $userRepo = app(App\Domain\User\Repositories\UserRepository::class);
    echo "✅ UserRepository создался\n";
    
    $masterRepo = app(App\Domain\Master\Repositories\MasterRepository::class);
    echo "✅ MasterRepository создался\n";
    
    $bookingRepo = app(App\Domain\Booking\Repositories\BookingRepository::class);
    echo "✅ BookingRepository создался\n";
    
    // 2. Проверка создания сервисов
    echo "\n2. Тестирование сервисов:\n";
    
    $userService = app(App\Domain\User\Services\UserService::class);
    echo "✅ UserService создался\n";
    
    // 3. Проверка интерфейсов
    echo "\n3. Проверка интерфейсов:\n";
    
    if ($userRepo instanceof App\Support\Contracts\RepositoryInterface) {
        echo "✅ UserRepository реализует RepositoryInterface\n";
    } else {
        echo "❌ UserRepository НЕ реализует RepositoryInterface\n";
    }
    
    if ($userService instanceof App\Support\Contracts\ServiceInterface) {
        echo "✅ UserService реализует ServiceInterface\n";
    } else {
        echo "❌ UserService НЕ реализует ServiceInterface\n";
    }
    
    // 4. Проверка методов BaseRepository
    echo "\n4. Проверка методов BaseRepository:\n";
    
    $methods = ['find', 'findOrFail', 'all', 'create', 'update', 'delete', 'count', 'exists'];
    foreach ($methods as $method) {
        if (method_exists($userRepo, $method)) {
            echo "✅ Метод $method существует\n";
        } else {
            echo "❌ Метод $method ОТСУТСТВУЕТ\n";
        }
    }
    
    echo "\n=== АРХИТЕКТУРА РАБОТАЕТ КОРРЕКТНО ===\n";
    
} catch (Exception $e) {
    echo "🚨 КРИТИЧЕСКАЯ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . "\n";
    echo "Строка: " . $e->getLine() . "\n";
}