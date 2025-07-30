<?php

require_once 'vendor/autoload.php';

// Загружаем конфигурацию Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ad;

echo "=== ИСПРАВЛЕНИЕ ДАННЫХ SCHEDULE ===\n";

// Находим черновик 137
$ad = Ad::find(137);

if ($ad) {
    echo "Текущий schedule: " . $ad->getRawOriginal('schedule') . "\n\n";
    
    // Устанавливаем правильный график
    $correctSchedule = [
        'monday' => ['enabled' => true, 'from' => '09:00', 'to' => '18:00'],
        'tuesday' => ['enabled' => true, 'from' => '09:00', 'to' => '18:00'],
        'wednesday' => ['enabled' => true, 'from' => '09:00', 'to' => '18:00'],
        'thursday' => ['enabled' => true, 'from' => '09:00', 'to' => '18:00'],
        'friday' => ['enabled' => true, 'from' => '09:00', 'to' => '18:00'],
        'saturday' => ['enabled' => false, 'from' => null, 'to' => null],
        'sunday' => ['enabled' => true, 'from' => null, 'to' => null]
    ];
    
    // Обновляем через cast (Laravel автоматически сделает json_encode)
    $ad->schedule = $correctSchedule;
    $ad->save();
    
    echo "✅ Schedule исправлен!\n";
    echo "Новый schedule: " . json_encode($correctSchedule) . "\n";
    
    // Проверяем результат
    $ad->refresh();
    echo "\nПроверка из БД: " . $ad->getRawOriginal('schedule') . "\n";
    
} else {
    echo "❌ Черновик 137 не найден\n";
}

?>