<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;
use Illuminate\Support\Str;

echo "=== ТЕСТ PROFILECONTROLLER НАПРЯМУЮ ===\n\n";

// Имитируем пользователя
$user = \App\Domain\User\Models\User::first();
if (!$user) {
    echo "Пользователь не найден\n";
    exit;
}

// Получаем черновики точно так же как в ProfileController
$ads = Ad::where('user_id', $user->id)
    ->where('status', 'draft')
    ->select([
        'id', 'title', 'status', 'price', 'prices', 'address', 'travel_area',
        'specialty', 'description', 'phone', 'contact_method',
        'photos', 'service_location', 'views_count',
        'created_at', 'updated_at'
    ])
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get();

echo "Найдено черновиков: " . $ads->count() . "\n\n";

// Применяем ту же логику что в ProfileController
$profiles = $ads->map(function ($ad) {
    // Берем цену за час из нового поля prices (ТОЧНО КАК В КОНТРОЛЛЕРЕ!)
    $prices = $ad->prices;
    if (is_string($prices)) {
        $prices = json_decode($prices, true) ?? [];
    }
    $prices = is_array($prices) ? $prices : [];
    
    // Ищем минимальную цену из всех доступных вариантов
    $hourPrice = null;
    if (!empty($prices)) {
        // Берем все возможные цены за час и приводим к числам
        $possiblePrices = array_filter([
            isset($prices['apartments_1h']) ? (float)$prices['apartments_1h'] : null,
            isset($prices['outcall_1h']) ? (float)$prices['outcall_1h'] : null,
            // Можно также учесть экспресс, если он дешевле
            isset($prices['apartments_express']) ? (float)$prices['apartments_express'] : null
        ]);
        
        // Берем минимальную из доступных
        if (!empty($possiblePrices)) {
            $hourPrice = min($possiblePrices);
        }
    }
    
    // Если нет цены за час, используем старую
    $finalPrice = $hourPrice ?? $ad->price ?? 0;
    
    echo "--- Черновик #{$ad->id} ---\n";
    echo "Raw prices: " . $ad->prices . "\n";
    echo "Parsed prices: " . json_encode($prices) . "\n";
    echo "Possible prices: " . json_encode($possiblePrices ?? []) . "\n";
    echo "Final price: {$finalPrice}\n\n";
    
    return [
        'id' => $ad->id,
        'title' => $ad->title,
        'price' => $finalPrice,
        'price_from' => $finalPrice,
        'prices' => $prices,
    ];
})->toArray();

echo "=== РЕЗУЛЬТАТ ДЛЯ FRONTEND ===\n";
foreach ($profiles as $profile) {
    echo "ID: {$profile['id']}, Title: {$profile['title']}, Price: {$profile['price']}\n";
}
