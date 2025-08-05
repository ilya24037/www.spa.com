<?php

/**
 * Скрипт для валидации DDD рефакторинга
 * Проверяет работоспособность новых Integration Services и трейтов
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Создаем Laravel приложение для тестирования
$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

// Результаты валидации
$results = [
    'total_tests' => 0,
    'passed_tests' => 0,
    'failed_tests' => 0,
    'errors' => [],
    'warnings' => [],
];

/**
 * Функция для выполнения теста
 */
function runTest(string $testName, callable $testFunction, array &$results): void
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

echo "🚀 Запуск валидации DDD рефакторинга...\n\n";

// Тест 1: Проверка загрузки новых трейтов
runTest('Загрузка HasBookingIntegration трейта', function() {
    return trait_exists('App\Domain\User\Traits\HasBookingIntegration') ? true : 'Трейт не найден';
}, $results);

runTest('Загрузка HasMasterIntegration трейта', function() {
    return trait_exists('App\Domain\User\Traits\HasMasterIntegration') ? true : 'Трейт не найден';
}, $results);

// Тест 2: Проверка загрузки Integration Services
runTest('Загрузка UserBookingIntegrationService', function() {
    return class_exists('App\Application\Services\Integration\UserBookingIntegrationService') ? true : 'Сервис не найден';
}, $results);

runTest('Загрузка UserMasterIntegrationService', function() {
    return class_exists('App\Application\Services\Integration\UserMasterIntegrationService') ? true : 'Сервис не найден';
}, $results);

// Тест 3: Проверка загрузки Events
runTest('Загрузка BookingRequested события', function() {
    return class_exists('App\Domain\Booking\Events\BookingRequested') ? true : 'Событие не найдено';
}, $results);

runTest('Загрузка MasterProfileCreated события', function() {
    return class_exists('App\Domain\Master\Events\MasterProfileCreated') ? true : 'Событие не найдено';
}, $results);

// Тест 4: Проверка загрузки DTOs
runTest('Загрузка UserBookingDTO', function() {
    return class_exists('App\Application\Services\Integration\DTOs\UserBookingDTO') ? true : 'DTO не найден';
}, $results);

runTest('Загрузка UserMasterDTO', function() {
    return class_exists('App\Application\Services\Integration\DTOs\UserMasterDTO') ? true : 'DTO не найден';
}, $results);

// Тест 5: Проверка загрузки Interfaces
runTest('Загрузка BookingRepositoryInterface', function() {
    return interface_exists('App\Domain\Booking\Contracts\BookingRepositoryInterface') ? true : 'Интерфейс не найден';
}, $results);

runTest('Загрузка MasterRepositoryInterface', function() {
    return interface_exists('App\Domain\Master\Contracts\MasterRepositoryInterface') ? true : 'Интерфейс не найден';
}, $results);

// Тест 6: Проверка User модели
runTest('Загрузка User модели', function() {
    if (!class_exists('App\Domain\User\Models\User')) {
        return 'User модель не найдена';
    }
    
    // Проверяем использование новых трейтов
    $reflection = new ReflectionClass('App\Domain\User\Models\User');
    $traits = $reflection->getTraitNames();
    
    if (!in_array('App\Domain\User\Traits\HasBookingIntegration', $traits)) {
        return 'HasBookingIntegration трейт не подключен к User модели';
    }
    
    if (!in_array('App\Domain\User\Traits\HasMasterIntegration', $traits)) {
        return 'HasMasterIntegration трейт не подключен к User модели';
    }
    
    return true;
}, $results);

// Тест 7: Проверка методов в трейтах
runTest('Методы HasBookingIntegration трейта', function() {
    $reflection = new ReflectionClass('App\Domain\User\Traits\HasBookingIntegration');
    $methods = $reflection->getMethods();
    $methodNames = array_map(fn($m) => $m->getName(), $methods);
    
    $requiredMethods = ['getBookings', 'getActiveBookings', 'hasActiveBookings', 'requestBooking'];
    
    foreach ($requiredMethods as $method) {
        if (!in_array($method, $methodNames)) {
            return "Метод $method отсутствует в трейте";
        }
    }
    
    return true;
}, $results);

runTest('Методы HasMasterIntegration трейта', function() {
    $reflection = new ReflectionClass('App\Domain\User\Traits\HasMasterIntegration');
    $methods = $reflection->getMethods();
    $methodNames = array_map(fn($m) => $m->getName(), $methods);
    
    $requiredMethods = ['getMasterProfile', 'getMasterProfiles', 'hasActiveMasterProfile', 'createMasterProfile'];
    
    foreach ($requiredMethods as $method) {
        if (!in_array($method, $methodNames)) {
            return "Метод $method отсутствует в трейте";
        }
    }
    
    return true;
}, $results);

// Тест 8: Проверка DTO валидации
runTest('Валидация UserBookingDTO', function() {
    $dtoClass = 'App\Application\Services\Integration\DTOs\UserBookingDTO';
    
    // Создаем валидный DTO
    $validData = [
        'user_id' => 1,
        'master_id' => 2,
        'service_type' => 'massage',
        'scheduled_at' => '2024-12-01 10:00:00',
        'price' => 5000.0,
    ];
    
    $dto = $dtoClass::fromArray($validData);
    
    if (!$dto->isValid()) {
        return 'DTO с валидными данными должен пройти валидацию';
    }
    
    // Создаем невалидный DTO
    $invalidData = [
        'user_id' => -1, // невалидный ID
        'master_id' => 2,
        'service_type' => '', // пустой тип
        'scheduled_at' => '2024-12-01 10:00:00',
    ];
    
    $invalidDto = $dtoClass::fromArray($invalidData);
    
    if ($invalidDto->isValid()) {
        return 'DTO с невалидными данными не должен проходить валидацию';
    }
    
    return true;
}, $results);

// Тест 9: Проверка Event структуры
runTest('Структура BookingRequested события', function() {
    $eventClass = 'App\Domain\Booking\Events\BookingRequested';
    $reflection = new ReflectionClass($eventClass);
    
    // Проверяем конструктор
    $constructor = $reflection->getConstructor();
    if (!$constructor) {
        return 'Конструктор отсутствует';
    }
    
    $parameters = $constructor->getParameters();
    $paramNames = array_map(fn($p) => $p->getName(), $parameters);
    
    $requiredParams = ['clientId', 'masterId', 'bookingData'];
    
    foreach ($requiredParams as $param) {
        if (!in_array($param, $paramNames)) {
            return "Параметр $param отсутствует в конструкторе";
        }
    }
    
    return true;
}, $results);

// Тест 10: Проверка отсутствия старых трейтов в файлах
runTest('Отсутствие прямых импортов HasBookings', function() {
    $userModelFile = file_get_contents(__DIR__ . '/../app/Domain/User/Models/User.php');
    
    if (strpos($userModelFile, 'use App\Domain\User\Traits\HasBookings;') !== false) {
        return 'Найден импорт старого HasBookings трейта';
    }
    
    if (strpos($userModelFile, 'HasBookings,') !== false && strpos($userModelFile, 'HasBookingIntegration') === false) {
        return 'Найдено использование старого HasBookings трейта';
    }
    
    return true;
}, $results);

echo "\n📊 РЕЗУЛЬТАТЫ ВАЛИДАЦИИ:\n";
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

if ($successRate >= 90) {
    echo "🎉 ОТЛИЧНО! DDD рефакторинг успешно завершен!\n";
    // exit(0);
} elseif ($successRate >= 70) {
    echo "⚠️ ХОРОШО, но есть проблемы, которые нужно исправить.\n";
    // exit(1);
} else {
    echo "🚨 КРИТИЧЕСКИЕ ПРОБЛЕМЫ! Рефакторинг требует доработки.\n";
    // exit(2);
}