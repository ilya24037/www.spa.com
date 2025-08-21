<?php

echo "📊 ПРОВЕРКА КОНСОЛИДАЦИИ BOOKING СЕРВИСОВ\n";
echo "==========================================\n\n";

// Подсчет файлов в директории
$servicesDir = __DIR__ . '/app/Domain/Booking/Services';
$files = glob($servicesDir . '/*.php');
$fileCount = count($files);

echo "1. КОЛИЧЕСТВО СЕРВИСОВ:\n";
echo "   Было: 26 сервисов\n";
echo "   Цель: 7 сервисов\n";
echo "   Сейчас: {$fileCount} сервисов\n";
echo "   Статус: " . ($fileCount == 7 ? '✅ УСПЕХ' : '❌ НЕ СООТВЕТСТВУЕТ') . "\n\n";

echo "2. СПИСОК СЕРВИСОВ:\n";
$expectedServices = [
    'BookingService.php',
    'BookingQueryService.php',
    'BookingValidationService.php',
    'BookingNotificationService.php',
    'BookingSlotService.php',
    'BookingPaymentService.php',
    'BookingStatisticsService.php'
];

$actualServices = array_map('basename', $files);

foreach ($expectedServices as $service) {
    $exists = in_array($service, $actualServices);
    echo "   " . ($exists ? '✅' : '❌') . " {$service}\n";
}

// Проверка лишних файлов
$extraFiles = array_diff($actualServices, $expectedServices);
if (!empty($extraFiles)) {
    echo "\n3. ⚠️ ЛИШНИЕ ФАЙЛЫ:\n";
    foreach ($extraFiles as $file) {
        echo "   ❌ {$file}\n";
    }
}

// Проверка размеров файлов
echo "\n4. РАЗМЕРЫ ФАЙЛОВ:\n";
foreach ($files as $file) {
    $lines = count(file($file));
    $name = basename($file);
    $status = $lines < 800 ? '✅' : '⚠️';
    echo "   {$status} {$name}: {$lines} строк\n";
}

// ИТОГ
echo "\n" . str_repeat("=", 50) . "\n";
if ($fileCount == 7 && empty($extraFiles)) {
    echo "✅ ДЕНЬ 1 УСПЕШНО ЗАВЕРШЕН!\n";
    echo "\nДостижения:\n";
    echo "• Сокращение с 26 до 7 сервисов (-73%)\n";
    echo "• Консолидация валидации в 1 сервис\n";
    echo "• Консолидация уведомлений в 1 сервис\n";
    echo "• Консолидация слотов в 1 сервис\n";
    echo "• Консолидация платежей в 1 сервис\n";
} else {
    echo "❌ КОНСОЛИДАЦИЯ НЕ ЗАВЕРШЕНА\n";
    echo "Проверьте список выше для деталей.\n";
}