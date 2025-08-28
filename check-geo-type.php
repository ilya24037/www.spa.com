<?php

use App\Domain\Ad\Models\Ad;
use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 ПРОВЕРКА ТИПА ПОЛЯ GEO\n";
echo "==========================\n\n";

// Прямой SQL запрос
$rawData = DB::table('ads')->where('id', 97)->first();
if ($rawData) {
    echo "📊 RAW данные из БД:\n";
    echo "  geo тип: " . gettype($rawData->geo) . "\n";
    echo "  geo значение: ";
    var_dump($rawData->geo);
    echo "\n";
}

// Через Eloquent модель
$ad = Ad::find(97);
if ($ad) {
    echo "📊 Через Eloquent модель:\n";
    echo "  geo тип: " . gettype($ad->geo) . "\n";
    echo "  geo значение: ";
    var_dump($ad->geo);
    echo "\n";
    
    // Проверим JsonFieldsTrait
    echo "📋 JsonFieldsTrait:\n";
    $jsonFields = $ad->getJsonFields();
    echo "  JSON поля модели: " . implode(', ', $jsonFields) . "\n";
    
    if (in_array('geo', $jsonFields)) {
        echo "  ✅ geo в списке JSON полей\n";
    } else {
        echo "  ❌ geo НЕ в списке JSON полей\n";
    }
    
    // Попробуем установить geo как массив
    echo "\n🧪 Тест установки geo как массива:\n";
    $testGeo = [
        'outcall' => 'zones',
        'zones' => ['Тестовый район'],
        'outcall_apartment' => true
    ];
    
    $ad->geo = $testGeo;
    echo "  Установлено: ";
    var_dump($ad->geo);
    
    // Сохраним и проверим
    $ad->save();
    $ad->refresh();
    
    echo "  После сохранения: ";
    var_dump($ad->geo);
}