<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Создание черновика с видео для тестирования...\n";

// Создаем тестовый черновик с видео
$ad = \App\Domain\Ad\Models\Ad::create([
    'user_id' => 1, // Предполагаем что пользователь с ID 1 существует
    'category' => 'massage',
    'title' => 'Тестовый черновик с видео',
    'specialty' => 'Расслабляющий массаж',
    'description' => 'Тестовое описание для черновика',
    'clients' => json_encode(['men', 'women']),
    'service_location' => json_encode(['outcall']),
    'work_format' => 'individual',
    'service_provider' => json_encode(['master']),
    'experience' => '1-2 years',
    'services' => json_encode([]),
    'features' => json_encode([]),
    'schedule' => json_encode([]),
    'price' => 3000,
    'price_unit' => 'service',
    'photos' => json_encode(['/storage/uploads/photos/test1.jpg', '/storage/uploads/photos/test2.jpg']),
    'video' => json_encode(['/storage/uploads/videos/test-video.mp4']),
    'geo' => json_encode([]),
    'address' => 'Тестовый адрес',
    'phone' => '+7 999 123 45 67',
    'contact_method' => 'messages',
    'status' => 'draft'
]);

echo "Создан черновик с ID: {$ad->id}\n";
echo "Название: {$ad->title}\n";
echo "Clients: {$ad->clients}\n";
echo "Video: {$ad->video}\n";

echo "\nТеперь можно протестировать:\n";
echo "http://localhost:8000/draft/{$ad->id}\n";

echo "\nДля загрузки тестового видео откройте:\n";
echo "http://localhost:8000/create-test-video.html\n";