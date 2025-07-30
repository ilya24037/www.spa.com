<?php

require_once 'vendor/autoload.php';

// Подключаем Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MasterProfile;
use App\Models\MasterPhoto;

try {
    // Найдем мастера Елену Сидорову
    $master = MasterProfile::where('display_name', 'Елена Сидорова')->first();
    
    if (!$master) {
        echo "❌ Мастер Елена Сидорова не найден!\n";
        exit(1);
    }

    echo "✅ Найден мастер: {$master->display_name} (ID: {$master->id})\n";

    // Удалим существующие фотографии
    $master->photos()->delete();
    echo "🗑️ Удалены старые фотографии\n";

    // Добавим тестовые фотографии (качественные изображения женщин)
    $photos = [
        [
            'path' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=400&h=600&fit=crop&crop=face',
            'is_main' => true,
            'order' => 1
        ],
        [
            'path' => 'https://images.unsplash.com/photo-1580618672591-eb180b1a973f?w=400&h=600&fit=crop&crop=face',
            'is_main' => false,
            'order' => 2
        ],
        [
            'path' => 'https://images.unsplash.com/photo-1594824388853-0d0e4a8a1b4c?w=400&h=600&fit=crop&crop=face',
            'is_main' => false,
            'order' => 3
        ],
        [
            'path' => 'https://images.unsplash.com/photo-1607990281513-2c110a25bd8c?w=400&h=600&fit=crop&crop=face',
            'is_main' => false,
            'order' => 4
        ],
        [
            'path' => 'https://images.unsplash.com/photo-1588516903720-8ceb67f9ef84?w=400&h=600&fit=crop&crop=face',
            'is_main' => false,
            'order' => 5
        ],
        [
            'path' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=400&h=600&fit=crop&crop=face',
            'is_main' => false,
            'order' => 6
        ]
    ];

    foreach ($photos as $index => $photo) {
        MasterPhoto::create([
            'master_profile_id' => $master->id,
            'path' => $photo['path'],
            'is_main' => $photo['is_main'],
            'order' => $photo['order']
        ]);
        echo "📸 Добавлено фото " . ($index + 1) . "\n";
    }

    // Обновим аватар мастера и статусы
    $master->update([
        'avatar' => $photos[0]['path'],
        'is_verified' => true,
        'is_premium' => true,
        'premium_until' => now()->addMonths(3),
        'rating' => 4.9
    ]);

    echo "✅ Успешно добавлено " . count($photos) . " фотографий для мастера: {$master->display_name}\n";
    echo "🎯 Теперь откройте: http://127.0.0.1:8000/masters/elena-sidorova-3\n";

} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
} 