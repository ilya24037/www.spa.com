<?php

use App\Application\Http\Controllers\Ad\DraftController;
use App\Domain\User\Models\User;
use Illuminate\Http\Request;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🎯 ТЕСТ КОНТРОЛЛЕРА DraftController ЧЕРЕЗ DI КОНТЕЙНЕР\n";
echo "=====================================================\n\n";

try {
    // Получаем пользователя
    $user = User::first();
    if (!$user) {
        echo "❌ Нет пользователей в БД\n";
        exit;
    }

    echo "✅ Пользователь найден: {$user->email}\n\n";

    // Симулируем данные как они приходят из FormData
    $requestData = [
        'status' => 'draft',
        'specialty' => 'массаж',
        'work_format' => 'individual',
        'experience' => '',
        'description' => '',
        'title' => '',
        'category' => 'relax',
        
        // JSON поля как строки
        'prices' => '[]',
        'services' => '{"hygiene_amenities":{"shower_before":{"enabled":false,"price_comment":""}}}',
        'clients' => '["men"]',
        'service_provider' => '["women"]',
        'features' => '[]',
        'schedule' => '[]',
        'geo' => '[]',
        
        'phone' => '',
        'whatsapp' => '',
        'telegram' => '',
        'contact_method' => '',
        'vk' => '',
        'instagram' => '',
        'address' => '',
        'radius' => 0,
        'is_remote' => false,
        'age' => '',
        'height' => '',
        'weight' => '',
        'breast_size' => '',
        'hair_color' => '',
        'eye_color' => '',
        'nationality' => '',
        'bikini_zone' => '',
        'appearance' => '',
        'additional_features' => '',
        'discount' => 0,
        'new_client_discount' => 0,
        'min_duration' => 0,
        'contacts_per_hour' => 0,
        'gift' => '',
        'has_girlfriend' => false,
        'online_booking' => false,
        'is_starting_price' => false,
    ];

    // Создаем Request объект
    $request = new Request($requestData);
    
    // Авторизуем пользователя 
    auth()->login($user);
    
    echo "📋 Исходные типы данных в запросе:\n";
    foreach (['services', 'clients', 'prices', 'geo'] as $field) {
        $value = $request->input($field);
        echo "  $field: " . gettype($value) . " = '$value'\n";
    }
    echo "\n";

    echo "🔧 Создаем контроллер через DI контейнер...\n";
    $controller = app(DraftController::class);
    
    echo "🔧 Вызываем DraftController::store...\n";
    $response = $controller->store($request);

    echo "✅ Контроллер выполнен успешно!\n";
    echo "📋 Тип ответа: " . get_class($response) . "\n";

} catch (\Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Тип: " . get_class($e) . "\n";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    echo "\n🔍 Трассировка ошибки:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n📋 Проверьте логи Laravel для отладочных данных\n";