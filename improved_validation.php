<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== УЛУЧШЕННАЯ ПРОВЕРКА СООТВЕТСТВИЯ ПРАВИЛАМ CLAUDE.MD ===\n\n";

// 1. Проверка правила: Контроллер → Сервис → Репозиторий → Модель
echo "1. ПРОВЕРКА ЦЕПОЧКИ: Контроллер → Сервис → Репозиторий → Модель\n";

try {
    // Проверяем ключевые контроллеры
    $controllerChecks = [
        'CompareController' => 'AdProfileService',
        'FavoriteController' => 'AdProfileService', 
        'MyAdsController' => 'AdService',
        'SearchController' => 'MasterSearchService',
        'PaymentController' => 'PaymentService',
        'BookingController' => 'BookingService'
    ];
    
    foreach ($controllerChecks as $controller => $service) {
        $filePath = "app/Application/Http/Controllers/{$controller}.php";
        if (file_exists($filePath)) {
            $controllerFile = file_get_contents($filePath);
            
            if (strpos($controllerFile, $service) !== false) {
                echo "✅ {$controller} использует {$service}\n";
            } else {
                echo "❌ {$controller} НЕ использует {$service}\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка при проверке цепочки: " . $e->getMessage() . "\n";
}

echo "\n2. ПРОВЕРКА ПРАВИЛА: Бизнес-логика ТОЛЬКО в сервисах\n";

try {
    // Улучшенные паттерны для поиска РЕАЛЬНЫХ нарушений
    $controllers = glob('app/Application/Http/Controllers/**/*.php');
    $violations = [];
    
    foreach ($controllers as $controller) {
        $content = file_get_contents($controller);
        $controllerName = basename($controller);
        
        // Ищем КОНКРЕТНЫЕ нарушения (не ложные срабатывания)
        $violations_found = [];
        
        // 1. Прямые статические вызовы моделей
        if (preg_match('/(?:MasterProfile|Ad|User|Booking|Photo|Video)::\s*(?:where|find|create|orderBy|with)/', $content)) {
            $violations_found[] = "Прямые статические вызовы моделей";
        }
        
        // 2. Auth::user() вместо $request->user()
        if (preg_match('/Auth::user\(\)/', $content)) {
            $violations_found[] = "Auth::user() вместо \$request->user()";
        }
        
        // 3. DB:: запросы в контроллерах
        if (preg_match('/DB::\s*(?:table|select|insert|update|delete|raw)/', $content)) {
            $violations_found[] = "Прямые DB запросы";
        }
        
        // 4. Прямые create/update на моделях (исключая вызовы сервисов)
        if (preg_match('/\$\w+->(?:create|update|delete)\s*\([^)]+\)/', $content) && 
            !preg_match('/\$this->\w+Service->/', $content)) {
            $violations_found[] = "Прямые операции на моделях";
        }
        
        if (!empty($violations_found)) {
            $violations[$controllerName] = $violations_found;
        }
    }
    
    if (empty($violations)) {
        echo "✅ Контроллеры НЕ содержат бизнес-логику\n";
    } else {
        echo "❌ Контроллеры с нарушениями:\n";
        foreach ($violations as $controller => $issues) {
            echo "   • {$controller}: " . implode(', ', $issues) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка при проверке контроллеров: " . $e->getMessage() . "\n";
}

echo "\n3. ПРОВЕРКА: \$fillable в моделях\n";

try {
    $models = [
        'app/Domain/User/Models/User.php',
        'app/Domain/Master/Models/MasterProfile.php',
        'app/Domain/Booking/Models/Booking.php',
        'app/Domain/Ad/Models/Ad.php'
    ];
    
    foreach ($models as $modelPath) {
        if (file_exists($modelPath)) {
            $content = file_get_contents($modelPath);
            
            if (strpos($content, '$fillable') !== false) {
                echo "✅ " . basename($modelPath) . " имеет \$fillable\n";
            } else {
                echo "❌ " . basename($modelPath) . " НЕ имеет \$fillable\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка при проверке моделей: " . $e->getMessage() . "\n";
}

echo "\n4. ПРОВЕРКА: Сервисы используют репозитории\n";

try {
    $serviceChecks = [
        'UserService' => 'UserRepository',
        'MasterService' => 'MasterRepository', 
        'BookingService' => 'BookingRepository',
        'AdService' => 'AdRepository'
    ];
    
    foreach ($serviceChecks as $service => $repository) {
        $servicePath = "app/Domain/*/Services/{$service}.php";
        $serviceFiles = glob($servicePath);
        
        if (!empty($serviceFiles)) {
            $serviceFile = file_get_contents($serviceFiles[0]);
            
            if (strpos($serviceFile, $repository) !== false) {
                echo "✅ {$service} использует {$repository}\n";
            } else {
                echo "❌ {$service} НЕ использует {$repository}\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка при проверке сервисов: " . $e->getMessage() . "\n";
}

echo "\n5. ПРОВЕРКА: Транзакции в сервисах\n";

try {
    $userService = file_get_contents('app/Domain/User/Services/UserService.php');
    
    if (strpos($userService, 'DB::transaction') !== false) {
        echo "✅ UserService использует транзакции\n";
    } else {
        echo "❌ UserService НЕ использует транзакции\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка при проверке транзакций: " . $e->getMessage() . "\n";
}

echo "\n=== ИТОГИ УЛУЧШЕННОЙ ПРОВЕРКИ ===\n";
echo "Скрипт проверяет РЕАЛЬНЫЕ нарушения архитектуры.\n";
echo "Исключены ложные срабатывания на вызовы сервисов.\n";