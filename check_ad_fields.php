<?php

require_once 'vendor/autoload.php';

use App\Domain\Ad\Models\Ad;
use Illuminate\Support\Facades\DB;

// Загружаем Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 ПРОВЕРКА ПОЛЕЙ ОБЪЯВЛЕНИЯ\n";
echo "============================\n\n";

// Получаем последнее созданное объявление
$latestAd = Ad::latest()->first();

if (!$latestAd) {
    echo "❌ Объявления не найдены\n";
    exit;
}

echo "📋 ID объявления: {$latestAd->id}\n";
echo "📋 Статус: " . $latestAd->status->value . "\n";
echo "📋 Создано: {$latestAd->created_at}\n\n";

// Проверяем основные поля
$fieldsToCheck = [
    'title' => 'Имя',
    'work_format' => 'Формат работы',
    'service_provider' => 'Кто оказывает услуги',
    'clients' => 'Клиенты',
    'age' => 'Возраст',
    'height' => 'Рост',
    'weight' => 'Вес',
    'breast_size' => 'Размер груди',
    'hair_color' => 'Цвет волос',
    'phone' => 'Телефон',
    'whatsapp' => 'WhatsApp',
    'telegram' => 'Telegram',
    'description' => 'Описание',
    'prices' => 'Цены',
    'services' => 'Услуги',
    'photos' => 'Фотографии',
    'geo' => 'География',
    'schedule' => 'График работы',
    'online_booking' => 'Онлайн бронирование'
];

echo "🔍 ПРОВЕРКА ПОЛЕЙ:\n";
echo "==================\n";

foreach ($fieldsToCheck as $field => $label) {
    $value = $latestAd->getAttribute($field);
    $status = $value !== null && $value !== '' ? '✅' : '❌';
    $displayValue = '';
    
    if ($value !== null) {
        if (is_array($value)) {
            $displayValue = '[' . count($value) . ' элементов]';
        } elseif (is_string($value) && strlen($value) > 50) {
            $displayValue = substr($value, 0, 50) . '...';
        } else {
            $displayValue = (string)$value;
        }
    } else {
        $displayValue = 'НЕ ЗАПОЛНЕНО';
    }
    
    echo "{$status} {$label}: {$displayValue}\n";
}

echo "\n🔍 СТРУКТУРА БАЗЫ ДАННЫХ:\n";
echo "========================\n";

// Получаем структуру таблицы ads
$columns = DB::select("DESCRIBE ads");
foreach ($columns as $column) {
    $isNull = $column->Null === 'YES' ? 'NULL' : 'NOT NULL';
    $default = $column->Default ? "DEFAULT '{$column->Default}'" : '';
    echo "{$column->Field} ({$column->Type}) {$isNull} {$default}\n";
}

echo "\n🔍 RAW ДАННЫЕ ИЗ БД:\n";
echo "====================\n";

$rawData = DB::table('ads')->where('id', $latestAd->id)->first();
foreach ($rawData as $field => $value) {
    if (in_array($field, ['service_provider', 'clients', 'prices', 'services', 'photos', 'geo', 'schedule'])) {
        $decoded = json_decode($value, true);
        $displayValue = $decoded ? '[' . count($decoded) . ' элементов]' : 'NULL';
        echo "{$field}: {$displayValue}\n";
    } else {
        $displayValue = $value ?: 'NULL';
        echo "{$field}: {$displayValue}\n";
    }
}
