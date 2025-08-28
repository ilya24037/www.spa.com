<?php

echo "=== ДИАГНОСТИКА ЦИКЛИЧНЫХ ЗАВИСИМОСТЕЙ В DI КОНТЕЙНЕРЕ ===" . PHP_EOL . PHP_EOL;

// Временно включаем отображение всех ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Пробуем загрузить Laravel без лимитов памяти
    ini_set('memory_limit', '1G');
    
    require __DIR__.'/vendor/autoload.php';
    
    echo "✅ Autoload загружен успешно" . PHP_EOL;
    
    $app = require_once __DIR__.'/bootstrap/app.php';
    
    echo "✅ Laravel app создан успешно" . PHP_EOL;
    
    // Пробуем получить ключевые сервисы один за другим
    $services = [
        'App\Domain\Booking\Services\BookingService',
        'App\Domain\Booking\Services\BookingValidationService',  
        'App\Domain\Booking\Services\BookingStatusManager',
        'App\Domain\Booking\Services\BookingNotificationService',
        'App\Domain\Ad\Services\AdService',
        'App\Domain\Ad\Services\AdValidationService',
        'App\Domain\Master\Services\MasterApiService',
        'App\Domain\User\Services\UserService',
        'App\Application\Services\Integration\UserReviewsIntegrationService',
        'App\Application\Services\Integration\ReviewValidator',
        'App\Application\Services\Integration\UserReviewsReader',
        'App\Application\Services\Integration\UserReviewsWriter',
    ];
    
    echo PHP_EOL . "🔍 ТЕСТИРОВАНИЕ РАЗРЕШЕНИЯ СЕРВИСОВ:" . PHP_EOL;
    
    foreach ($services as $service) {
        try {
            echo "  📋 Проверяем: {$service}... ";
            
            // Пробуем создать сервис через DI контейнер
            $instance = $app->make($service);
            
            echo "✅ OK" . PHP_EOL;
            
            // Очищаем память
            unset($instance);
            
        } catch (\Exception $e) {
            echo "❌ ОШИБКА: " . $e->getMessage() . PHP_EOL;
            echo "   Файл: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
            
            // Если это циклическая зависимость
            if (strpos($e->getMessage(), 'Circular dependency') !== false) {
                echo "   🔄 ЦИКЛИЧЕСКАЯ ЗАВИСИМОСТЬ НАЙДЕНА!" . PHP_EOL;
            }
        }
    }
    
    echo PHP_EOL . "🔍 ПРОВЕРКА ПРОВАЙДЕРОВ СЕРВИСОВ:" . PHP_EOL;
    
    // Проверяем провайдеры сервисов
    $providers = config('app.providers', []);
    foreach ($providers as $provider) {
        if (strpos($provider, 'App\\') === 0) {
            echo "  📋 Провайдер: {$provider}" . PHP_EOL;
        }
    }
    
} catch (\Exception $e) {
    echo "❌ КРИТИЧЕСКАЯ ОШИБКА ПРИ ЗАГРУЗКЕ LARAVEL:" . PHP_EOL;
    echo "   Сообщение: " . $e->getMessage() . PHP_EOL;
    echo "   Файл: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    echo "   Trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "=== ДИАГНОСТИКА ЗАВЕРШЕНА ===" . PHP_EOL;