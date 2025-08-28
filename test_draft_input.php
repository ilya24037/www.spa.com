<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 ТЕСТ: Создание массива данных как в DraftController\n";
echo "===================================================\n\n";

// Имитируем данные от фронтенда - то что должно приходить в FormData
$mockRequestData = [
    'id' => '85',
    '_method' => 'PUT',
    'status' => 'draft',
    
    // Основная информация
    'specialty' => 'Массаж',
    'work_format' => 'individual', 
    'experience' => '5 лет',
    'description' => 'Описание услуги',
    'title' => 'Тестовый заголовок',
    'category' => 'relax',
    
    // Контакты
    'phone' => '79001234567',
    'whatsapp' => '79001234567',
    'telegram' => '@test',
    'vk' => 'vk.com/test',
    'instagram' => 'insta_test',
    
    // Параметры
    'age' => '25',
    'height' => '170',
    'weight' => '60',
    'breast_size' => '2',
    'hair_color' => 'блондинка',
    'eye_color' => 'голубые',
    'nationality' => 'русская',
    'appearance' => 'красивая',
    
    // Локация
    'address' => 'Тестовый адрес',
    'radius' => '10',
    'is_remote' => '0',
    'geo' => '{}',
    
    // Расписание
    'schedule' => '{}',
    'schedule_notes' => 'Заметки',
    
    // JSON поля
    'prices' => '{"hour":3000}',
    'services' => '["massage"]',
    'clients' => '["men"]',
    'features' => '[]',
    'additional_features' => '[]',
    
    // Дополнительные
    'discount' => '0',
    'gift' => 'Подарок',
    'has_girlfriend' => '0',
];

echo "📋 ВХОДЯЩИЕ ДАННЫЕ (имитация FormData):\n";
foreach ($mockRequestData as $key => $value) {
    $type = is_string($value) && strlen($value) > 50 ? 'JSON (' . strlen($value) . ' chars)' : gettype($value);
    echo "  {$key}: \"{$value}\" [{$type}]\n";
}

echo "\n🔍 ПРОВЕРКА FILLABLE:\n";
$adModel = new App\Domain\Ad\Models\Ad();
$fillable = $adModel->getFillable();

$missingFields = [];
foreach ($mockRequestData as $key => $value) {
    if ($key === 'id' || $key === '_method' || $key === 'status') continue;
    
    if (in_array($key, $fillable)) {
        echo "  ✅ {$key} - в fillable\n";
    } else {
        echo "  ❌ {$key} - НЕ В fillable!\n";
        $missingFields[] = $key;
    }
}

if (empty($missingFields)) {
    echo "\n✅ Все поля присутствуют в fillable!\n";
    echo "   Проблема где-то в другом месте...\n";
} else {
    echo "\n❌ НАЙДЕНЫ ОТСУТСТВУЮЩИЕ ПОЛЯ:\n";
    foreach ($missingFields as $field) {
        echo "  - {$field}\n";
    }
    echo "\nЭти поля нужно добавить в \$fillable!\n";
}

echo "\n🎯 СЛЕДУЮЩИЙ ШАГ:\n";
echo "   Если все поля в fillable - проблема в DraftController или DraftService\n";
echo "   Если поля отсутствуют - добавить в fillable\n";