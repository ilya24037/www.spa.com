<?php

require_once 'vendor/autoload.php';

// Загружаем Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 ПРОВЕРКА ЛОГОВ ДЛЯ SCHEDULE_NOTES\n";
echo "===================================\n\n";

// Читаем последние записи из лога
$logFile = storage_path('logs/laravel.log');

if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $lines = explode("\n", $logContent);
    $lastLines = array_slice($lines, -50); // Последние 50 строк
    
    echo "📋 Поиск записей связанных со schedule_notes:\n";
    
    $found = false;
    foreach ($lastLines as $line) {
        if (stripos($line, 'schedule_notes') !== false) {
            echo "   " . trim($line) . "\n";
            $found = true;
        }
    }
    
    if (!$found) {
        echo "❌ Записи со schedule_notes не найдены в последних 50 строках\n\n";
        
        echo "📋 Поиск записей от DraftController:\n";
        foreach ($lastLines as $line) {
            if (stripos($line, 'DraftController') !== false) {
                echo "   " . trim($line) . "\n";
            }
        }
    }
    
} else {
    echo "❌ Лог файл не найден: $logFile\n";
}

// Также проверим черновик напрямую
echo "\n🔍 ПРЯМАЯ ПРОВЕРКА ЧЕРНОВИКА 97:\n";
$ad = \App\Domain\Ad\Models\Ad::find(97);
if ($ad) {
    echo "   ID: {$ad->id}\n";
    echo "   schedule_notes: '" . ($ad->schedule_notes ?? 'NULL') . "'\n";
    echo "   schedule: " . json_encode($ad->schedule) . "\n";
    echo "   description: '" . substr($ad->description ?? '', 0, 50) . "...'\n";
} else {
    echo "❌ Черновик 97 не найден\n";
}