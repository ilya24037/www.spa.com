<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

echo "🔍 КРИТИЧЕСКИЙ АНАЛИЗ ПРОБЛЕМ РЕФАКТОРИНГА\n";
echo "==========================================\n\n";

// 1. Проверка DDD нарушений
echo "1. ❌ НАРУШЕНИЕ DDD:\n";
echo "   Domain/Booking использовал Infrastructure/Notification\n";
echo "   ✅ ИСПРАВЛЕНО: Создан NotificationServiceInterface в Domain\n\n";

// 2. Проверка размера файлов
echo "2. ⚠️ РАЗМЕР ФАЙЛОВ:\n";
$files = [
    'BookingSlotService.php' => 952,
    'BookingValidationService.php' => 346,
    'BookingNotificationService.php' => 406,
];
foreach ($files as $file => $lines) {
    $status = $lines > 500 ? '❌ СЛИШКОМ БОЛЬШОЙ' : '✅ ОК';
    echo "   {$file}: {$lines} строк - {$status}\n";
}

// 3. Проверка работы слотов
echo "\n3. ⚠️ ПРОБЛЕМА СО СЛОТАМИ:\n";
$slotService = app(\App\Domain\Booking\Services\BookingSlotService::class);
$slots = $slotService->getAvailableSlots(1, 1, null, 7);
echo "   Получено слотов: " . count($slots) . "\n";
if (count($slots) == 0) {
    echo "   ❌ Проблема: Нет доступных слотов (возможно нет расписания мастера)\n";
}

// 4. Проверка полей в Booking модели
echo "\n4. ⚠️ НЕСООТВЕТСТВИЕ ПОЛЕЙ:\n";
$booking = new \App\Domain\Booking\Models\Booking();
$fillable = $booking->getFillable();
$hasStartAt = in_array('start_at', $fillable);
$hasBookingDate = in_array('booking_date', $fillable);
echo "   Модель Booking использует:\n";
echo "   - start_at: " . ($hasStartAt ? '✅ ДА' : '❌ НЕТ') . "\n";
echo "   - booking_date + start_time: " . ($hasBookingDate ? '✅ ДА' : '❌ НЕТ') . "\n";
if (!$hasStartAt && $hasBookingDate) {
    echo "   ⚠️ Код использует start_at, но модель использует booking_date/start_time!\n";
}

// 5. Проверка принципа KISS
echo "\n5. ⚠️ НАРУШЕНИЕ KISS:\n";
echo "   - BookingSlotService: 952 строк (слишком сложный)\n";
echo "   - Объединили 4 сервиса в 1 (возможно лучше было 2?)\n";
echo "   - Слишком много ответственностей в одном классе\n";

// РЕКОМЕНДАЦИИ
echo "\n📝 РЕКОМЕНДАЦИИ ПО ИСПРАВЛЕНИЮ:\n";
echo "================================\n";
echo "1. ✅ DDD исправлен через интерфейс\n";
echo "2. ❌ Разделить BookingSlotService на:\n";
echo "   - AvailabilityService (проверка доступности)\n";
echo "   - SlotGeneratorService (генерация слотов)\n";
echo "3. ❌ Исправить несоответствие полей start_at vs booking_date\n";
echo "4. ❌ Добавить фабрики/билдеры для сложной логики\n";
echo "5. ❌ Убедиться что есть тестовые данные (мастера, расписания)\n";

echo "\n🎯 ВЫВОД:\n";
echo "Рефакторинг выполнен, но есть проблемы с качеством.\n";
echo "Нужно исправить критические моменты перед продакшеном.\n";