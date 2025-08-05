<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Domain\User\Models\User;
use App\Application\Services\Integration\UserFavoritesIntegrationService;

// Создаем экземпляр приложения Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';

// Загружаем ядро
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ТЕСТИРОВАНИЕ DDD ИНТЕГРАЦИИ ===\n\n";

// Проверяем загрузку классов
echo "1. Проверка загрузки классов:\n";
echo "   User model: " . (class_exists(User::class) ? "✅ Загружен" : "❌ Не найден") . "\n";
echo "   UserFavoritesIntegrationService: " . (class_exists(UserFavoritesIntegrationService::class) ? "✅ Загружен" : "❌ Не найден") . "\n\n";

// Проверяем трейты
echo "2. Проверка интеграционных трейтов:\n";
$user = new User();
echo "   HasFavoritesIntegration: " . (method_exists($user, 'getFavorites') ? "✅ Подключен" : "❌ Не подключен") . "\n";
echo "   HasReviewsIntegration: " . (method_exists($user, 'getReceivedReviews') ? "✅ Подключен" : "❌ Не подключен") . "\n";
echo "   HasAdsIntegration: " . (method_exists($user, 'getAds') ? "✅ Подключен" : "❌ Не подключен") . "\n\n";

// Проверяем методы
echo "3. Проверка доступности методов:\n";
$methods = [
    'getFavorites',
    'getFavoritesCount',
    'addToFavorites',
    'removeFromFavorites',
    'toggleFavorite',
    'getReceivedReviews',
    'getReceivedReviewsCount',
    'getAverageRating',
    'writeReview',
    'getAds',
    'getActiveAds',
    'getAdsCount',
    'createAd',
];

foreach ($methods as $method) {
    echo "   $method(): " . (method_exists($user, $method) ? "✅ Доступен" : "❌ Не найден") . "\n";
}

echo "\n4. Проверка deprecated методов (для обратной совместимости):\n";
$deprecatedMethods = ['favorites', 'reviews', 'ads'];
foreach ($deprecatedMethods as $method) {
    echo "   $method(): " . (method_exists($user, $method) ? "✅ Доступен" : "❌ Не найден") . "\n";
}

echo "\n=== РЕЗУЛЬТАТ ===\n";
$allPassed = true;
foreach ($methods as $method) {
    if (!method_exists($user, $method)) {
        $allPassed = false;
        break;
    }
}

if ($allPassed) {
    echo "✅ ВСЕ ТЕСТЫ ПРОЙДЕНЫ! DDD интеграция работает корректно.\n";
} else {
    echo "❌ ЕСТЬ ОШИБКИ! Не все методы доступны.\n";
}