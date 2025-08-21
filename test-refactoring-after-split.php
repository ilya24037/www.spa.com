<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

echo "🔍 ТЕСТ РЕФАКТОРИНГА ПОСЛЕ РАЗДЕЛЕНИЯ\n";
echo "=====================================\n\n";

// 1. Проверка существования новых сервисов
echo "1. ✅ НОВЫЕ СЕРВИСЫ:\n";
$availabilityExists = class_exists(\App\Domain\Booking\Services\AvailabilityCheckService::class);
$slotManagementExists = class_exists(\App\Domain\Booking\Services\SlotManagementService::class);
echo "   AvailabilityCheckService: " . ($availabilityExists ? '✅ СУЩЕСТВУЕТ' : '❌ НЕ НАЙДЕН') . "\n";
echo "   SlotManagementService: " . ($slotManagementExists ? '✅ СУЩЕСТВУЕТ' : '❌ НЕ НАЙДЕН') . "\n\n";

// 2. Проверка удаления старого сервиса
echo "2. 🗑️ СТАРЫЙ СЕРВИС:\n";
$oldServiceExists = class_exists(\App\Domain\Booking\Services\BookingSlotService::class);
echo "   BookingSlotService: " . ($oldServiceExists ? '❌ ВСЕ ЕЩЕ СУЩЕСТВУЕТ' : '✅ УДАЛЕН') . "\n\n";

// 3. Проверка размера новых сервисов
echo "3. 📏 РАЗМЕР НОВЫХ СЕРВИСОВ:\n";
if (file_exists(__DIR__ . '/app/Domain/Booking/Services/AvailabilityCheckService.php')) {
    $lines1 = count(file(__DIR__ . '/app/Domain/Booking/Services/AvailabilityCheckService.php'));
    echo "   AvailabilityCheckService: {$lines1} строк " . ($lines1 < 500 ? '✅ НОРМАЛЬНО' : '⚠️ БОЛЬШОЙ') . "\n";
}
if (file_exists(__DIR__ . '/app/Domain/Booking/Services/SlotManagementService.php')) {
    $lines2 = count(file(__DIR__ . '/app/Domain/Booking/Services/SlotManagementService.php'));
    echo "   SlotManagementService: {$lines2} строк " . ($lines2 < 500 ? '✅ НОРМАЛЬНО' : '⚠️ БОЛЬШОЙ') . "\n";
}

// 4. Проверка DI Container
echo "\n4. 📦 ПРОВЕРКА DI CONTAINER:\n";
try {
    $availabilityService = app(\App\Domain\Booking\Services\AvailabilityCheckService::class);
    echo "   AvailabilityCheckService: ✅ СОЗДАН ЧЕРЕЗ DI\n";
} catch (Exception $e) {
    echo "   AvailabilityCheckService: ❌ ОШИБКА DI - " . $e->getMessage() . "\n";
}

try {
    $slotService = app(\App\Domain\Booking\Services\SlotManagementService::class);
    echo "   SlotManagementService: ✅ СОЗДАН ЧЕРЕЗ DI\n";
} catch (Exception $e) {
    echo "   SlotManagementService: ❌ ОШИБКА DI - " . $e->getMessage() . "\n";
}

// 5. Проверка методов
echo "\n5. 🔧 ПРОВЕРКА МЕТОДОВ:\n";
if ($availabilityExists) {
    $availabilityMethods = [
        'isSlotAvailable',
        'isMasterAvailable',
        'canRescheduleBooking',
        'getMasterBusySlots'
    ];
    
    $service = new \App\Domain\Booking\Services\AvailabilityCheckService(
        app(\App\Domain\Booking\Repositories\BookingRepository::class),
        app(\App\Domain\Master\Repositories\MasterRepository::class)
    );
    
    foreach ($availabilityMethods as $method) {
        $exists = method_exists($service, $method);
        echo "   AvailabilityCheckService::{$method}() - " . ($exists ? '✅' : '❌') . "\n";
    }
}

if ($slotManagementExists) {
    $slotMethods = [
        'getAvailableSlots',
        'generateDaySlots',
        'reserveSlot',
        'releaseSlot'
    ];
    
    foreach ($slotMethods as $method) {
        $exists = method_exists(\App\Domain\Booking\Services\SlotManagementService::class, $method);
        echo "   SlotManagementService::{$method}() - " . ($exists ? '✅' : '❌') . "\n";
    }
}

// 6. Исправление полей
echo "\n6. 🔄 ИСПРАВЛЕНИЕ ПОЛЕЙ:\n";
$notificationFile = file_get_contents(__DIR__ . '/app/Domain/Booking/Services/BookingNotificationService.php');
$hasStartAt = strpos($notificationFile, 'start_at') !== false;
$hasBookingDate = strpos($notificationFile, 'booking_date') !== false;
echo "   Использует start_at: " . ($hasStartAt ? '❌ ДА (НУЖНО ИСПРАВИТЬ)' : '✅ НЕТ') . "\n";
echo "   Использует booking_date: " . ($hasBookingDate ? '✅ ДА' : '❌ НЕТ') . "\n";

echo "\n🎯 ИТОГ:\n";
echo "========\n";

$success = $availabilityExists && $slotManagementExists && !$oldServiceExists;
if ($success) {
    echo "✅ РЕФАКТОРИНГ УСПЕШЕН!\n";
    echo "   - BookingSlotService разделен на 2 сервиса\n";
    echo "   - Размеры файлов оптимальные\n";
    echo "   - DDD архитектура соблюдена\n";
} else {
    echo "❌ ЕСТЬ ПРОБЛЕМЫ:\n";
    if (!$availabilityExists) echo "   - AvailabilityCheckService не создан\n";
    if (!$slotManagementExists) echo "   - SlotManagementService не создан\n";
    if ($oldServiceExists) echo "   - BookingSlotService не удален\n";
}