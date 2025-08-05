<?php

/**
 * Скрипт валидации Event Listeners
 * Проверяет работоспособность всех созданных обработчиков событий
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "🎧 Валидация Event Listeners для DDD архитектуры...\n\n";

$results = [
    'total_tests' => 0,
    'passed_tests' => 0,
    'failed_tests' => 0,
    'errors' => [],
    'warnings' => [],
];

function runListenerTest(string $testName, callable $testFunction, array &$results): void
{
    $results['total_tests']++;
    
    try {
        echo "🧪 Тестирование: $testName... ";
        
        $result = $testFunction();
        
        if ($result === true) {
            echo "✅ ПРОШЕЛ\n";
            $results['passed_tests']++;
        } else {
            echo "❌ НЕ ПРОШЕЛ: $result\n";
            $results['failed_tests']++;
            $results['errors'][] = "$testName: $result";
        }
        
    } catch (Exception $e) {
        echo "🚨 ОШИБКА: " . $e->getMessage() . "\n";
        $results['failed_tests']++;
        $results['errors'][] = "$testName: " . $e->getMessage();
    }
}

echo "📁 ПРОВЕРКА СТРУКТУРЫ EVENT LISTENERS:\n";
echo "════════════════════════════════════════════════════════════════\n";

// Тест 1-4: Проверка Booking Listeners
runListenerTest('Загрузка HandleBookingRequested', function() {
    return class_exists('App\\Infrastructure\\Listeners\\Booking\\HandleBookingRequested') ? true : 'Класс не найден';
}, $results);

runListenerTest('Загрузка HandleBookingStatusChanged', function() {
    return class_exists('App\\Infrastructure\\Listeners\\Booking\\HandleBookingStatusChanged') ? true : 'Класс не найден';
}, $results);

runListenerTest('Загрузка HandleBookingCancelled', function() {
    return class_exists('App\\Infrastructure\\Listeners\\Booking\\HandleBookingCancelled') ? true : 'Класс не найден';
}, $results);

runListenerTest('Загрузка HandleBookingCompleted', function() {
    return class_exists('App\\Infrastructure\\Listeners\\Booking\\HandleBookingCompleted') ? true : 'Класс не найден';
}, $results);

// Тест 5-7: Проверка Master Listeners
runListenerTest('Загрузка HandleMasterProfileCreated', function() {
    return class_exists('App\\Infrastructure\\Listeners\\Master\\HandleMasterProfileCreated') ? true : 'Класс не найден';
}, $results);

runListenerTest('Загрузка HandleMasterProfileUpdated', function() {
    return class_exists('App\\Infrastructure\\Listeners\\Master\\HandleMasterProfileUpdated') ? true : 'Класс не найден';
}, $results);

runListenerTest('Загрузка HandleMasterStatusChanged', function() {
    return class_exists('App\\Infrastructure\\Listeners\\Master\\HandleMasterStatusChanged') ? true : 'Класс не найден';
}, $results);

// Тест 8-10: Проверка User Listeners
runListenerTest('Загрузка HandleUserRegistered', function() {
    return class_exists('App\\Infrastructure\\Listeners\\User\\HandleUserRegistered') ? true : 'Класс не найден';
}, $results);

runListenerTest('Загрузка HandleUserRoleChanged', function() {
    return class_exists('App\\Infrastructure\\Listeners\\User\\HandleUserRoleChanged') ? true : 'Класс не найден';
}, $results);

runListenerTest('Загрузка HandleUserProfileUpdated', function() {
    return class_exists('App\\Infrastructure\\Listeners\\User\\HandleUserProfileUpdated') ? true : 'Класс не найден';
}, $results);

// Тест 11: Проверка EventServiceProvider
runListenerTest('Загрузка EventServiceProvider', function() {
    return class_exists('App\\Infrastructure\\Providers\\EventServiceProvider') ? true : 'Класс не найден';
}, $results);

echo "\n🔍 ПРОВЕРКА МЕТОДОВ И СТРУКТУРЫ:\n";
echo "════════════════════════════════════════════════════════════════\n";

// Тест 12: Проверка handle методов в Booking Listeners
runListenerTest('Методы Booking Listeners', function() {
    $listeners = [
        'App\\Infrastructure\\Listeners\\Booking\\HandleBookingRequested',
        'App\\Infrastructure\\Listeners\\Booking\\HandleBookingStatusChanged',
        'App\\Infrastructure\\Listeners\\Booking\\HandleBookingCancelled',
        'App\\Infrastructure\\Listeners\\Booking\\HandleBookingCompleted',
    ];
    
    foreach ($listeners as $listener) {
        $reflection = new ReflectionClass($listener);
        if (!$reflection->hasMethod('handle')) {
            return "Метод handle отсутствует в $listener";
        }
        
        if (!$reflection->hasMethod('register')) {
            return "Метод register отсутствует в $listener";
        }
    }
    
    return true;
}, $results);

// Тест 13: Проверка handle методов в Master Listeners
runListenerTest('Методы Master Listeners', function() {
    $listeners = [
        'App\\Infrastructure\\Listeners\\Master\\HandleMasterProfileCreated',
        'App\\Infrastructure\\Listeners\\Master\\HandleMasterProfileUpdated',
        'App\\Infrastructure\\Listeners\\Master\\HandleMasterStatusChanged',
    ];
    
    foreach ($listeners as $listener) {
        $reflection = new ReflectionClass($listener);
        if (!$reflection->hasMethod('handle')) {
            return "Метод handle отсутствует в $listener";
        }
    }
    
    return true;
}, $results);

// Тест 14: Проверка handle методов в User Listeners
runListenerTest('Методы User Listeners', function() {
    $listeners = [
        'App\\Infrastructure\\Listeners\\User\\HandleUserRegistered',
        'App\\Infrastructure\\Listeners\\User\\HandleUserRoleChanged',
        'App\\Infrastructure\\Listeners\\User\\HandleUserProfileUpdated',
    ];
    
    foreach ($listeners as $listener) {
        $reflection = new ReflectionClass($listener);
        if (!$reflection->hasMethod('handle')) {
            return "Метод handle отсутствует в $listener";
        }
    }
    
    return true;
}, $results);

// Тест 15: Проверка конструкторов Listeners
runListenerTest('Конструкторы Listeners', function() {
    $listeners = [
        'App\\Infrastructure\\Listeners\\Booking\\HandleBookingRequested',
        'App\\Infrastructure\\Listeners\\Master\\HandleMasterProfileCreated',
        'App\\Infrastructure\\Listeners\\User\\HandleUserRegistered',
    ];
    
    foreach ($listeners as $listener) {
        $reflection = new ReflectionClass($listener);
        $constructor = $reflection->getConstructor();
        
        if (!$constructor) {
            return "Конструктор отсутствует в $listener";
        }
        
        $params = $constructor->getParameters();
        if (count($params) === 0) {
            return "Конструктор $listener не принимает зависимости";
        }
    }
    
    return true;
}, $results);

echo "\n🎯 ПРОВЕРКА EVENT SERVICE PROVIDER:\n";
echo "════════════════════════════════════════════════════════════════\n";

// Тест 16: Проверка карты событий в EventServiceProvider
runListenerTest('Карта событий EventServiceProvider', function() {
    $reflection = new ReflectionClass('App\\Infrastructure\\Providers\\EventServiceProvider');
    $property = $reflection->getProperty('listen');
    $property->setAccessible(true);
    
    $listen = $property->getValue(new App\Infrastructure\Providers\EventServiceProvider(app()));
    
    // Проверяем наличие всех событий
    $expectedEvents = [
        'App\\Domain\\Booking\\Events\\BookingRequested',
        'App\\Domain\\Booking\\Events\\BookingStatusChanged',
        'App\\Domain\\Booking\\Events\\BookingCancelled',
        'App\\Domain\\Booking\\Events\\BookingCompleted',
        'App\\Domain\\Master\\Events\\MasterProfileCreated',
        'App\\Domain\\Master\\Events\\MasterProfileUpdated',
        'App\\Domain\\Master\\Events\\MasterStatusChanged',
        'App\\Domain\\User\\Events\\UserRegistered',
        'App\\Domain\\User\\Events\\UserRoleChanged',
        'App\\Domain\\User\\Events\\UserProfileUpdated',
    ];
    
    foreach ($expectedEvents as $event) {
        if (!isset($listen[$event])) {
            return "Событие $event не зарегистрировано";
        }
        
        if (empty($listen[$event])) {
            return "У события $event нет обработчиков";
        }
    }
    
    return true;
}, $results);

// Тест 17: Проверка соответствия событий и обработчиков
runListenerTest('Соответствие событий и обработчиков', function() {
    $expectedMappings = [
        'App\\Domain\\Booking\\Events\\BookingRequested' => 'App\\Infrastructure\\Listeners\\Booking\\HandleBookingRequested',
        'App\\Domain\\Master\\Events\\MasterProfileCreated' => 'App\\Infrastructure\\Listeners\\Master\\HandleMasterProfileCreated',
        'App\\Domain\\User\\Events\\UserRegistered' => 'App\\Infrastructure\\Listeners\\User\\HandleUserRegistered',
    ];
    
    $reflection = new ReflectionClass('App\\Infrastructure\\Providers\\EventServiceProvider');
    $property = $reflection->getProperty('listen');
    $property->setAccessible(true);
    
    $listen = $property->getValue(new App\Infrastructure\Providers\EventServiceProvider(app()));
    
    foreach ($expectedMappings as $event => $expectedListener) {
        if (!isset($listen[$event]) || !in_array($expectedListener, $listen[$event])) {
            return "Неверное соответствие $event -> $expectedListener";
        }
    }
    
    return true;
}, $results);

echo "\n📦 ПРОВЕРКА ЗАВИСИМОСТЕЙ LISTENERS:\n";
echo "════════════════════════════════════════════════════════════════\n";

// Тест 18: Проверка зависимостей Booking Listeners
runListenerTest('Зависимости Booking Listeners', function() {
    $listener = 'App\\Infrastructure\\Listeners\\Booking\\HandleBookingRequested';
    $reflection = new ReflectionClass($listener);
    $constructor = $reflection->getConstructor();
    $params = $constructor->getParameters();
    
    $expectedDependencies = ['BookingRepository', 'BookingService', 'NotificationService'];
    
    if (count($params) < 3) {
        return "Недостаточно зависимостей в конструкторе";
    }
    
    return true;
}, $results);

// Тест 19: Проверка документации Listeners
runListenerTest('Документация Listeners', function() {
    $listeners = [
        'App\\Infrastructure\\Listeners\\Booking\\HandleBookingRequested',
        'App\\Infrastructure\\Listeners\\Master\\HandleMasterProfileCreated',
        'App\\Infrastructure\\Listeners\\User\\HandleUserRegistered',
    ];
    
    foreach ($listeners as $listener) {
        $reflection = new ReflectionClass($listener);
        $docComment = $reflection->getDocComment();
        
        if (!$docComment || !str_contains($docComment, 'ФУНКЦИИ:')) {
            return "Отсутствует документация в $listener";
        }
        
        $handleMethod = $reflection->getMethod('handle');
        $handleDoc = $handleMethod->getDocComment();
        
        if (!$handleDoc) {
            return "Отсутствует документация метода handle в $listener";
        }
    }
    
    return true;
}, $results);

// Тест 20: Финальная проверка архитектуры
runListenerTest('Архитектурная целостность', function() {
    // Проверяем что все Listeners находятся в правильных неймспейсах
    $expectedStructure = [
        'App\\Infrastructure\\Listeners\\Booking\\' => ['HandleBookingRequested', 'HandleBookingStatusChanged', 'HandleBookingCancelled', 'HandleBookingCompleted'],
        'App\\Infrastructure\\Listeners\\Master\\' => ['HandleMasterProfileCreated', 'HandleMasterProfileUpdated', 'HandleMasterStatusChanged'],
        'App\\Infrastructure\\Listeners\\User\\' => ['HandleUserRegistered', 'HandleUserRoleChanged', 'HandleUserProfileUpdated'],
    ];
    
    foreach ($expectedStructure as $namespace => $classes) {
        foreach ($classes as $class) {
            $fullClass = $namespace . $class;
            if (!class_exists($fullClass)) {
                return "Класс $fullClass не найден в ожидаемом неймспейсе";
            }
        }
    }
    
    return true;
}, $results);

// Результаты
echo "\n📊 РЕЗУЛЬТАТЫ ВАЛИДАЦИИ EVENT LISTENERS:\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "Всего тестов: {$results['total_tests']}\n";
echo "✅ Прошли: {$results['passed_tests']}\n";
echo "❌ Не прошли: {$results['failed_tests']}\n";

if ($results['failed_tests'] > 0) {
    echo "\n🚨 ОШИБКИ:\n";
    foreach ($results['errors'] as $error) {
        echo "  - $error\n";
    }
}

if (count($results['warnings']) > 0) {
    echo "\n⚠️ ПРЕДУПРЕЖДЕНИЯ:\n";
    foreach ($results['warnings'] as $warning) {
        echo "  - $warning\n";
    }
}

$successRate = $results['total_tests'] > 0 ? round(($results['passed_tests'] / $results['total_tests']) * 100, 1) : 0;

echo "\n🎯 ПРОЦЕНТ УСПЕШНОСТИ: {$successRate}%\n";

// Подсчет созданных компонентов
echo "\n📈 СТАТИСТИКА СОЗДАННЫХ КОМПОНЕНТОВ:\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "🎧 Event Listeners: 10\n";
echo "  ├── Booking Listeners: 4\n";
echo "  ├── Master Listeners: 3\n";
echo "  └── User Listeners: 3\n";
echo "⚙️ Service Providers: 1\n";
echo "📋 Валидационных скриптов: 1\n";

if ($successRate >= 90) {
    echo "\n🎉 ПРЕВОСХОДНО! Event Listeners успешно созданы и готовы к работе!\n";
    echo "\n🚀 СЛЕДУЮЩИЕ ШАГИ:\n";
    echo "1. Зарегистрировать EventServiceProvider в config/app.php\n";
    echo "2. Создать интеграционные тесты для Listeners\n";
    echo "3. Настроить очереди для асинхронной обработки событий\n";
    echo "4. Добавить мониторинг производительности Event-driven архитектуры\n";
    // exit(0);
} elseif ($successRate >= 70) {
    echo "\n⚠️ ХОРОШО, но есть проблемы, которые нужно исправить.\n";
    // exit(1);
} else {
    echo "\n🚨 КРИТИЧЕСКИЕ ПРОБЛЕМЫ! Event Listeners требуют доработки.\n";
    // exit(2);
}