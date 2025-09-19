<?php
// Простой тест с минимальным кодом

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

$user = \App\Domain\User\Models\User::first();
Auth::login($user);

// Создаем объявление с заполненными проблемными полями
$data = [
    'user_id' => $user->id,
    'title' => 'ТЕСТ SPECIALTY ' . time(),
    'category' => 'girls',
    'specialty' => 'massage_classic',  // ГЛАВНОЕ ПОЛЕ!
    'work_format' => 'duo',            // ГЛАВНОЕ ПОЛЕ!
    'service_provider' => ['women', 'men'], // ГЛАВНОЕ ПОЛЕ!
    'status' => 'active',
    'is_published' => false,
    'phone' => '+79001234567',
    'address' => 'Москва'
];

echo "СОЗДАЕМ ОБЪЯВЛЕНИЕ:\n";
echo "specialty: {$data['specialty']}\n";
echo "work_format: {$data['work_format']}\n";
echo "service_provider: " . json_encode($data['service_provider']) . "\n\n";

$service = app(\App\Domain\Ad\Services\DraftService::class);
$ad = $service->saveOrUpdate($data, $user);

echo "Создано объявление ID: {$ad->id}\n\n";

// Проверяем в БД
$result = DB::table('ads')->where('id', $ad->id)->first();

echo "РЕЗУЛЬТАТ В БД:\n";
echo "specialty: " . ($result->specialty ?: 'NULL') . "\n";
echo "work_format: " . ($result->work_format ?: 'NULL') . "\n";
echo "service_provider: " . $result->service_provider . "\n\n";

// Проверка
if ($result->specialty === 'massage_classic' &&
    $result->work_format === 'duo' &&
    strpos($result->service_provider, 'women') !== false) {
    echo "✅ ВСЕ ПОЛЯ СОХРАНЕНЫ!\n";
} else {
    echo "❌ НЕ ВСЕ ПОЛЯ СОХРАНЕНЫ!\n";
}