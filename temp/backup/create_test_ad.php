<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ad;

try {
    $ad = Ad::create([
        'user_id' => 1,
        'category' => 'massage',
        'title' => 'Тестовое объявление с фото',
        'specialty' => 'Расслабляющий массаж',
        'clients' => ['women', 'men'],
        'service_location' => ['my_place', 'client_home'],
        'work_format' => 'individual',
        'experience' => '5_years',
        'description' => 'Качественный массаж с использованием натуральных масел',
        'price' => 3000,
        'price_unit' => 'hour',
        'photos' => [
            ['id' => 1, 'preview' => '/images/masters/demo-1.jpg', 'name' => 'photo1.jpg'],
            ['id' => 2, 'preview' => '/images/masters/demo-2.jpg', 'name' => 'photo2.jpg']
        ],
        'address' => 'Москва, ул. Тверская 10',
        'phone' => '+7 (900) 123-45-67',
        'contact_method' => 'any',
        'status' => 'draft'
    ]);
    
    echo "✅ Создано объявление ID: {$ad->id}\n";
    echo "📸 Фотографий: " . count($ad->photos) . "\n";
    echo "🔗 Ссылка: http://localhost:8000/ads/{$ad->id}\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
} 