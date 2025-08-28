<?php

/**
 * Поиск всех несуществующих сервисов в проекте
 */

echo "=== ПОИСК НЕСУЩЕСТВУЮЩИХ СЕРВИСОВ ===" . PHP_EOL . PHP_EOL;

$directories = [
    'app/Domain',
    'app/Application/Http/Controllers'
];

$problematicServices = [
    // Уже найденные
    'CancellationValidationService',
    'CancellationFeeService',
    'BookingRefundService',
    'BulkCancelBookingsAction',
    'BookingCompletionValidationService',
    'BookingCompletionProcessorService',
    'BookingPaymentProcessorService',
    'BookingBulkOperationsService',
    
    // Потенциально проблемные
    'BookingValidator',
    'ValidationService',
    'RescheduleValidator',
    'BookingPaymentProcessor',
    'PricingService',
    'UserReviewsIntegrationService', // Этот может быть проблемным
    'ReviewValidator',
    'UserReviewsReader',
    'UserReviewsWriter'
];

$foundProblems = [];

foreach ($directories as $dir) {
    $fullPath = __DIR__ . '/' . $dir;
    if (!is_dir($fullPath)) {
        echo "⚠️  Директория не найдена: $fullPath" . PHP_EOL;
        continue;
    }
    
    echo "🔍 Проверка директории: $dir" . PHP_EOL;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fullPath)
    );
    
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        
        $content = file_get_contents($file->getPathname());
        $relativePath = str_replace(__DIR__ . '/', '', $file->getPathname());
        
        foreach ($problematicServices as $service) {
            // Ищем импорты или использование
            if (preg_match('/use\s+[^;]*' . preg_quote($service, '/') . '\s*;/', $content) || 
                preg_match('/\b' . preg_quote($service, '/') . '::\w+/', $content) ||
                preg_match('/new\s+' . preg_quote($service, '/') . '\s*\(/', $content) ||
                preg_match('/\$\w+\s*:\s*' . preg_quote($service, '/') . '\b/', $content)) {
                
                if (!isset($foundProblems[$service])) {
                    $foundProblems[$service] = [];
                }
                $foundProblems[$service][] = $relativePath;
            }
        }
    }
}

echo PHP_EOL . "📋 РЕЗУЛЬТАТЫ ПОИСКА:" . PHP_EOL;

if (empty($foundProblems)) {
    echo "✅ Несуществующих сервисов не найдено!" . PHP_EOL;
} else {
    foreach ($foundProblems as $service => $files) {
        echo PHP_EOL . "❌ $service найден в файлах:" . PHP_EOL;
        foreach ($files as $file) {
            echo "   • $file" . PHP_EOL;
        }
    }
}

// Дополнительная проверка - поиск файлов сервисов
echo PHP_EOL . "📋 ПРОВЕРКА СУЩЕСТВОВАНИЯ ФАЙЛОВ СЕРВИСОВ:" . PHP_EOL;

foreach ($problematicServices as $service) {
    $possiblePaths = [
        "app/Domain/Booking/Services/$service.php",
        "app/Domain/User/Services/$service.php", 
        "app/Domain/Payment/Services/$service.php",
        "app/Domain/Booking/Actions/$service.php",
        "app/Application/Services/Integration/$service.php"
    ];
    
    $exists = false;
    foreach ($possiblePaths as $path) {
        if (file_exists(__DIR__ . '/' . $path)) {
            echo "✅ $service: существует в $path" . PHP_EOL;
            $exists = true;
            break;
        }
    }
    
    if (!$exists) {
        echo "❌ $service: файл не найден" . PHP_EOL;
    }
}

echo PHP_EOL . "=== ПОИСК ЗАВЕРШЕН ===" . PHP_EOL;