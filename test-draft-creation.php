<?php

use App\Domain\Ad\Services\DraftService;
use App\Domain\User\Models\User;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🎯 ТЕСТ СОЗДАНИЯ ЧЕРНОВИКА\n";
echo "==========================\n\n";

try {
    // Получаем пользователя
    $user = User::first();
    if (!$user) {
        echo "❌ Нет пользователей в БД\n";
        exit;
    }

    echo "✅ Пользователь найден: {$user->email}\n";

    // Имитируем данные как они приходят из формы (после декодирования)
    $data = [
        'status' => 'draft',
        'specialty' => 'массаж',
        'work_format' => 'individual',
        'experience' => '',
        'description' => '',
        'title' => '',
        'category' => 'relax',
        
        // JSON поля как массивы (после декодирования в контроллере)
        'prices' => [],
        'services' => [
            'hygiene_amenities' => [
                'shower_before' => ['enabled' => false, 'price_comment' => '']
            ]
        ],
        'clients' => ['men'],
        'service_provider' => ['women'],
        'features' => [],
        'schedule' => [],
        'geo' => [],
        
        // Простые поля
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

    echo "📋 Проверим типы данных:\n";
    foreach (['services', 'clients', 'prices', 'geo'] as $field) {
        echo "  $field: " . gettype($data[$field]) . "\n";
    }
    echo "\n";

    echo "🔧 Создаем черновик...\n";
    $draftService = new DraftService();
    $ad = $draftService->saveOrUpdate($data, $user);

    echo "✅ Черновик создан! ID: {$ad->id}\n";

} catch (\Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Тип: " . get_class($e) . "\n";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
}