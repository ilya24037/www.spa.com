<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ПРОВЕРКА СООТВЕТСТВИЯ ПРАВИЛАМ CLAUDE.MD ===\n\n";

// 1. Проверка правила: Контроллер → Сервис → Репозиторий → Модель
echo "1. ПРОВЕРКА ЦЕПОЧКИ: Контроллер → Сервис → Репозиторий → Модель\n";

try {
    // Проверяем MasterController
    $controllerFile = file_get_contents('app/Application/Http/Controllers/MasterController.php');
    
    if (strpos($controllerFile, 'MasterService') !== false) {
        echo "✅ MasterController использует MasterService\n";
    } else {
        echo "❌ MasterController НЕ использует MasterService\n";
    }
    
    // Проверяем UserService
    $serviceFile = file_get_contents('app/Domain/User/Services/UserService.php');
    
    if (strpos($serviceFile, 'UserRepository') !== false) {
        echo "✅ UserService использует UserRepository\n";
    } else {
        echo "❌ UserService НЕ использует UserRepository\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка при проверке цепочки: " . $e->getMessage() . "\n";
}

echo "\n2. ПРОВЕРКА ПРАВИЛА: Бизнес-логика ТОЛЬКО в сервисах\n";

try {
    // Проверяем контроллеры на наличие бизнес-логики
    $controllers = glob('app/Application/Http/Controllers/**/*.php');
    $violations = [];
    
    foreach ($controllers as $controller) {
        $content = file_get_contents($controller);
        
        // Ищем SQL запросы в контроллерах (запрещено)
        if (preg_match('/DB::|Eloquent::|->where\(|->create\(|->update\(/', $content)) {
            $violations[] = basename($controller);
        }
    }
    
    if (empty($violations)) {
        echo "✅ Контроллеры НЕ содержат прямых SQL запросов\n";
    } else {
        echo "❌ Контроллеры с нарушениями: " . implode(', ', $violations) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка при проверке контроллеров: " . $e->getMessage() . "\n";
}

echo "\n3. ПРОВЕРКА: \$fillable в моделях\n";

try {
    $models = [
        'app/Domain/User/Models/User.php',
        'app/Domain/Master/Models/MasterProfile.php',
        'app/Domain/Booking/Models/Booking.php'
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

echo "\n4. ПРОВЕРКА: Транзакции в сервисах\n";

try {
    $userService = file_get_contents('app/Domain/User/Services/UserService.php');
    
    if (strpos($userService, 'DB::transaction') !== false) {
        echo "✅ UserService использует транзакции\n";
    } else {
        echo "❌ UserService НЕ использует транзакции\n";
    }
    
    // Проверяем BaseService
    $baseService = file_get_contents('app/Support/Services/BaseService.php');
    
    if (strpos($baseService, 'DB::beginTransaction') !== false && 
        strpos($baseService, 'DB::commit') !== false && 
        strpos($baseService, 'DB::rollBack') !== false) {
        echo "✅ BaseService корректно использует транзакции\n";
    } else {
        echo "❌ BaseService НЕКОРРЕКТНО использует транзакции\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка при проверке транзакций: " . $e->getMessage() . "\n";
}

echo "\n5. ПРОВЕРКА: Логирование в сервисах\n";

try {
    $baseService = file_get_contents('app/Support/Services/BaseService.php');
    
    if (strpos($baseService, 'Log::info') !== false && 
        strpos($baseService, 'Log::error') !== false) {
        echo "✅ BaseService использует логирование\n";
    } else {
        echo "❌ BaseService НЕ использует логирование\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка при проверке логирования: " . $e->getMessage() . "\n";
}

echo "\n=== ИТОГИ ПРОВЕРКИ ===\n";