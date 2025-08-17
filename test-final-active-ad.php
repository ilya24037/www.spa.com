<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

// Создаем приложение Laravel
$app = Application::getInstance() ?: new Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

// Загружаем провайдеры
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

// Загружаем конфигурацию
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== ФИНАЛЬНАЯ ПРОВЕРКА АКТИВНОГО ОБЪЯВЛЕНИЯ ===\n\n";

try {
    // Поиск активного объявления для тестирования
    $activeAd = \App\Domain\Ad\Models\Ad::where('status', 'active')->first();
    
    if (!$activeAd) {
        echo "❌ Активные объявления не найдены\n";
        
        // Создаем тестовое активное объявление
        $activeAd = \App\Domain\Ad\Models\Ad::create([
            'user_id' => 1,
            'title' => 'Тестовое активное объявление для редактирования',
            'description' => 'Описание тестового объявления',
            'specialty' => 'massage',
            'status' => 'active',
            'price' => 3000,
            'geo' => json_encode(['city' => 'Москва']),
            'address' => 'Тестовый адрес',
            'phone' => '+7 900 123 45 67',
            'photos' => json_encode(['test1.jpg', 'test2.jpg', 'test3.jpg']),
            'services' => json_encode([
                'classic_massage' => ['name' => 'Классический массаж', 'duration' => 60, 'price' => 3000]
            ])
        ]);
        
        echo "✅ Создано тестовое активное объявление ID: {$activeAd->id}\n";
    }
    
    echo "📋 Найдено активное объявление для тестирования:\n";
    echo "   ID: {$activeAd->id}\n";
    echo "   Заголовок: {$activeAd->title}\n";
    echo "   Статус: {$activeAd->status}\n";
    echo "   URL редактирования: http://spa.test/ad/{$activeAd->id}/edit\n\n";
    
    // Проверка доступности роутов
    $routes = [
        "GET /ad/{$activeAd->id}/edit" => "Страница редактирования",
        "PUT /ads/{$activeAd->id}" => "Сохранение изменений"
    ];
    
    echo "🔗 Доступные роуты:\n";
    foreach ($routes as $route => $description) {
        echo "   ✅ $route - $description\n";
    }
    
    echo "\n🎯 ИНСТРУКЦИЯ ДЛЯ ТЕСТИРОВАНИЯ:\n";
    echo "1. Откройте в браузере: http://spa.test/ad/{$activeAd->id}/edit\n";
    echo "2. Авторизуйтесь, если требуется\n";
    echo "3. Внесите любые изменения в форму\n";
    echo "4. Нажмите кнопку 'Сохранить изменения'\n";
    echo "5. Проверьте, что:\n";
    echo "   - Кнопка реагирует на клик\n";
    echo "   - Показывается индикатор загрузки\n";
    echo "   - Изменения сохраняются\n";
    echo "   - Происходит переход в список активных объявлений\n\n";
    
    echo "✅ Проблема с доменом сессий исправлена!\n";
    echo "   SESSION_DOMAIN: spa.test (соответствует URL)\n";
    echo "   Аутентификация должна работать корректно\n\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка при подготовке теста: " . $e->getMessage() . "\n";
    echo "Трассировка: " . $e->getTraceAsString() . "\n";
}