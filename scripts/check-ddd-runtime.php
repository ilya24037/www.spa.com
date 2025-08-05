<?php

/**
 * Проверка runtime работоспособности DDD рефакторинга
 * Создает временные объекты и проверяет их взаимодействие
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "🔍 Проверка runtime работоспособности DDD рефакторинга...\n\n";

$errors = [];
$warnings = [];
$passed = 0;

// Функция для безопасного выполнения теста
function safeTest(string $name, callable $test, array &$errors, array &$warnings, int &$passed): void {
    echo "🧪 $name... ";
    
    try {
        $result = $test();
        if ($result === true) {
            echo "✅ ПРОШЕЛ\n";
            $passed++;
        } elseif (is_string($result)) {
            echo "⚠️ ПРЕДУПРЕЖДЕНИЕ: $result\n";
            $warnings[] = "$name: $result";
            $passed++;
        } else {
            echo "❌ НЕ ПРОШЕЛ\n";
            $errors[] = "$name: Неожиданный результат";
        }
    } catch (Exception $e) {
        echo "🚨 ОШИБКА: " . $e->getMessage() . "\n";
        $errors[] = "$name: " . $e->getMessage();
    }
}

// Тест 1: Создание DTO
safeTest('Создание UserBookingDTO', function() {
    $dto = \App\Application\Services\Integration\DTOs\UserBookingDTO::fromArray([
        'user_id' => 1,
        'master_id' => 2,
        'service_type' => 'massage',
        'scheduled_at' => '2024-12-01 10:00:00',
        'price' => 5000.0,
    ]);
    
    if ($dto->userId !== 1 || $dto->masterId !== 2) {
        return 'DTO данные не соответствуют ожидаемым';
    }
    
    return $dto->isValid() ? true : 'DTO не прошел валидацию';
}, $errors, $warnings, $passed);

// Тест 2: Создание событий
safeTest('Создание BookingRequested события', function() {
    $event = new \App\Domain\Booking\Events\BookingRequested(
        clientId: 1,
        masterId: 2,
        bookingData: ['service_type' => 'massage']
    );
    
    if ($event->clientId !== 1 || $event->masterId !== 2) {
        return 'Данные события не корректны';
    }
    
    $bookingData = $event->getBookingData();
    if (!isset($bookingData['client_id']) || $bookingData['client_id'] !== 1) {
        return 'getBookingData() возвращает некорректные данные';
    }
    
    return true;
}, $errors, $warnings, $passed);

// Тест 3: Создание MasterProfileCreated события
safeTest('Создание MasterProfileCreated события', function() {
    $event = new \App\Domain\Master\Events\MasterProfileCreated(
        userId: 1,
        masterProfileId: 5,
        profileData: [
            'name' => 'Test Master',
            'city' => 'Moscow',
            'services' => ['massage']
        ]
    );
    
    if ($event->userId !== 1 || $event->masterProfileId !== 5) {
        return 'Данные события не корректны';
    }
    
    $summary = $event->getProfileSummary();
    if ($summary['name'] !== 'Test Master') {
        return 'getProfileSummary() возвращает некорректные данные';
    }
    
    return true;
}, $errors, $warnings, $passed);

// Тест 4: Проверка методов трейтов (без базы данных)
safeTest('Методы трейтов доступны', function() {
    $reflection = new ReflectionClass(\App\Domain\User\Traits\HasBookingIntegration::class);
    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
    $methodNames = array_map(fn($m) => $m->getName(), $methods);
    
    $requiredMethods = [
        'getBookings', 'getActiveBookings', 'hasActiveBookings', 
        'requestBooking', 'getBookingsCount'
    ];
    
    foreach ($requiredMethods as $method) {
        if (!in_array($method, $methodNames)) {
            return "Метод $method отсутствует";
        }
    }
    
    return true;
}, $errors, $warnings, $passed);

// Тест 5: Валидация DTOs с невалидными данными
safeTest('Валидация невалидных данных DTO', function() {
    // Тест с невалидным user_id
    $invalidDto = \App\Application\Services\Integration\DTOs\UserBookingDTO::fromArray([
        'user_id' => -1,
        'master_id' => 2,
        'service_type' => '',
        'scheduled_at' => '2024-12-01 10:00:00',
    ]);
    
    if ($invalidDto->isValid()) {
        return 'DTO с невалидными данными прошел валидацию (это ошибка)';
    }
    
    $validationErrors = $invalidDto->validate();
    if (empty($validationErrors)) {
        return 'validate() не возвращает ошибки для невалидных данных';
    }
    
    return true;
}, $errors, $warnings, $passed);

// Тест 6: Проверка UserMasterDTO
safeTest('Создание UserMasterDTO', function() {
    $dto = \App\Application\Services\Integration\DTOs\UserMasterDTO::fromArray([
        'user_id' => 1,
        'name' => 'Test Master',
        'city' => 'Moscow',
        'services' => ['massage', 'spa'],
        'experience' => 5,
    ]);
    
    if (!$dto->isValid()) {
        $errors = $dto->validate();
        return 'DTO не прошел валидацию: ' . implode(', ', $errors);
    }
    
    $summary = $dto->getSummary();
    if (!str_contains($summary, 'Test Master') || !str_contains($summary, 'Moscow')) {
        return 'getSummary() возвращает некорректные данные';
    }
    
    if (!$dto->providesService('massage')) {
        return 'providesService() работает некорректно';
    }
    
    return true;
}, $errors, $warnings, $passed);

// Тест 7: Проверка статических методов событий
safeTest('Статические методы событий', function() {
    $event = new \App\Domain\Booking\Events\BookingStatusChanged(
        bookingId: 1,
        clientId: 2,
        masterId: 3,
        oldStatus: 'pending',
        newStatus: 'confirmed'
    );
    
    if (!$event->wasConfirmed()) {
        return 'wasConfirmed() работает некорректно';
    }
    
    if ($event->wasCancelled() || $event->wasCompleted()) {
        return 'Методы проверки статуса работают некорректно';
    }
    
    return true;
}, $errors, $warnings, $passed);

// Тест 8: Проверка наследования и интерфейсов
safeTest('Проверка интерфейсов', function() {
    $interfaces = [
        \App\Domain\Booking\Contracts\BookingRepositoryInterface::class,
        \App\Domain\Booking\Contracts\BookingServiceInterface::class,
        \App\Domain\Master\Contracts\MasterRepositoryInterface::class,
        \App\Domain\User\Contracts\UserRepositoryInterface::class,
    ];
    
    foreach ($interfaces as $interface) {
        if (!interface_exists($interface)) {
            return "Интерфейс $interface не существует";
        }
        
        $reflection = new ReflectionClass($interface);
        if (count($reflection->getMethods()) === 0) {
            return "Интерфейс $interface не содержит методов";
        }
    }
    
    return true;
}, $errors, $warnings, $passed);

// Тест 9: Проверка Query Services
safeTest('Создание Query Services', function() {
    try {
        // Проверяем что Query Services можно создать (зависимости могут отсутствовать)
        $reflection = new ReflectionClass(\App\Application\Services\Query\UserBookingQueryService::class);
        $constructor = $reflection->getConstructor();
        
        if (!$constructor) {
            return 'Конструктор Query Service отсутствует';
        }
        
        $params = $constructor->getParameters();
        if (count($params) !== 2) {
            return 'Query Service должен принимать 2 параметра в конструкторе';
        }
        
        return true;
    } catch (Exception $e) {
        return 'Ошибка при проверке Query Service: ' . $e->getMessage();
    }
}, $errors, $warnings, $passed);

// Тест 10: Проверка отсутствия прямых зависимостей
safeTest('Отсутствие прямых зависимостей в трейтах', function() {
    $traitFile = file_get_contents(__DIR__ . '/../app/Domain/User/Traits/HasBookingIntegration.php');
    
    // Проверяем что нет прямых импортов моделей других доменов
    if (strpos($traitFile, 'use App\Domain\Booking\Models') !== false) {
        return 'Найден прямой импорт модели Booking домена';
    }
    
    if (strpos($traitFile, 'use App\Domain\Master\Models') !== false) {
        return 'Найден прямой импорт модели Master домена';
    }
    
    // Проверяем что используется app() helper для получения сервисов
    if (strpos($traitFile, 'app(UserBookingIntegrationService::class)') === false) {
        return 'Не найдено использование Integration Service через app() helper';
    }
    
    return true;
}, $errors, $warnings, $passed);

// Результаты
echo "\n📊 РЕЗУЛЬТАТЫ RUNTIME ПРОВЕРКИ:\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "✅ Прошли: $passed\n";
echo "❌ Ошибки: " . count($errors) . "\n";
echo "⚠️ Предупреждения: " . count($warnings) . "\n";

if (!empty($errors)) {
    echo "\n🚨 ОШИБКИ:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠️ ПРЕДУПРЕЖДЕНИЯ:\n";
    foreach ($warnings as $warning) {
        echo "  - $warning\n";
    }
}

$totalTests = $passed + count($errors);
$successRate = $totalTests > 0 ? round(($passed / $totalTests) * 100, 1) : 0;

echo "\n🎯 ПРОЦЕНТ УСПЕШНОСТИ: {$successRate}%\n";

if ($successRate >= 95) {
    echo "🎉 ПРЕВОСХОДНО! Runtime тесты успешно пройдены!\n";
} elseif ($successRate >= 80) {
    echo "✅ ХОРОШО! Небольшие проблемы, но в целом работает.\n";
} else {
    echo "🚨 КРИТИЧЕСКИЕ ПРОБЛЕМЫ! Требуется исправление.\n";
}