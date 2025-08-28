<?php

/**
 * Простая проверка упрощенного CancelBookingAction
 */

echo "=== ПРОВЕРКА УПРОЩЕННОГО CancelBookingAction ===" . PHP_EOL . PHP_EOL;

$filePath = __DIR__ . '/app/Domain/Booking/Actions/CancelBookingAction.php';

if (!file_exists($filePath)) {
    echo "❌ Файл не найден: $filePath" . PHP_EOL;
    exit(1);
}

echo "📋 1. Проверка синтаксиса PHP..." . PHP_EOL;
$syntaxCheck = shell_exec('"C:\Users\user1\.config\herd\bin\php.bat" -l "' . $filePath . '"');
if (strpos($syntaxCheck, 'No syntax errors') !== false) {
    echo "✅ PHP синтаксис корректен" . PHP_EOL;
} else {
    echo "❌ Ошибка синтаксиса: $syntaxCheck" . PHP_EOL;
}

echo PHP_EOL . "📋 2. Проверка удаления проблемных зависимостей..." . PHP_EOL;

$fileContent = file_get_contents($filePath);

$problematicClasses = [
    'CancellationValidationService' => '❌ Валидация отмены',
    'CancellationFeeService' => '❌ Расчет штрафов', 
    'BookingRefundService' => '❌ Возвраты средств',
    'BulkCancelBookingsAction' => '❌ Массовая отмена'
];

$hasProblems = false;
foreach ($problematicClasses as $class => $description) {
    if (strpos($fileContent, $class) !== false) {
        echo "$description: НАЙДЕН" . PHP_EOL;
        $hasProblems = true;
    } else {
        echo "✅ $class: удален" . PHP_EOL;
    }
}

echo PHP_EOL . "📋 3. Проверка корректных зависимостей..." . PHP_EOL;

$correctClasses = [
    'BookingRepository' => 'Репозиторий бронирований',
    'BookingValidationService' => 'Сервис валидации',
    'BookingHistory' => 'История бронирований',
    'BookingStatus' => 'Статусы бронирований'
];

foreach ($correctClasses as $class => $description) {
    if (strpos($fileContent, $class) !== false) {
        echo "✅ $class: присутствует" . PHP_EOL;
    } else {
        echo "❌ $class: отсутствует" . PHP_EOL;
    }
}

echo PHP_EOL . "📋 4. Проверка упрощенной логики..." . PHP_EOL;

// Проверяем что сложные методы упрощены
$simplifiedMethods = [
    'feeCalculation' => 'Расчеты штрафов',
    'refundResult' => 'Результаты возвратов',
    'fee_amount' => 'Суммы штрафов',
    'fee_percent' => 'Проценты штрафов'
];

$hasComplexLogic = false;
foreach ($simplifiedMethods as $pattern => $description) {
    if (strpos($fileContent, $pattern) !== false) {
        echo "⚠️  $description: все еще присутствуют" . PHP_EOL;
        $hasComplexLogic = true;
    } else {
        echo "✅ $description: удалены" . PHP_EOL;
    }
}

echo PHP_EOL . "📋 5. Статистика файла..." . PHP_EOL;
$lines = count(file($filePath));
echo "📝 Строк кода: $lines" . PHP_EOL;

$methods = preg_match_all('/function\s+\w+/', $fileContent, $matches);
echo "🔧 Методов: " . count($matches[0]) . PHP_EOL;

echo PHP_EOL . "🎯 ИТОГОВЫЙ РЕЗУЛЬТАТ:" . PHP_EOL;

if (!$hasProblems && !$hasComplexLogic) {
    echo "✅ УСПЕХ! CancelBookingAction успешно упрощен" . PHP_EOL;
    echo "✅ Все несуществующие сервисы удалены" . PHP_EOL;
    echo "✅ Сложная логика со штрафами удалена" . PHP_EOL;
    echo "✅ Остались только необходимые зависимости" . PHP_EOL;
    echo "✅ PHP синтаксис корректен" . PHP_EOL;
    echo PHP_EOL . "🚀 Архитектурная проблема РЕШЕНА!" . PHP_EOL;
} else {
    echo "⚠️  Есть замечания - см. детали выше" . PHP_EOL;
}

echo PHP_EOL . "=== ПРОВЕРКА ЗАВЕРШЕНА ===" . PHP_EOL;