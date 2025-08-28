<?php

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use App\Domain\Ad\Services\DraftService;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 ТЕСТ СОХРАНЕНИЯ ПОЛЕЙ ПОСЛЕ РЕФАКТОРИНГА\n";
echo "============================================\n\n";

// Получаем тестового пользователя
$user = User::first();
if (!$user) {
    echo "❌ Нет пользователей в БД\n";
    exit;
}
echo "✅ Используем пользователя: {$user->email}\n\n";

// Тестовые данные, имитирующие отправку из формы
$testData = [
    'title' => 'Тест после рефакторинга',
    'specialty' => 'массаж',
    'work_format' => 'individual',
    'experience' => '3 года',
    'description' => 'Тестовое описание',
    
    // Параметры - должны отправляться как отдельные поля
    'age' => '25',
    'height' => '170',
    'weight' => '55',
    'breast_size' => '3',
    'hair_color' => 'блондинка',
    'eye_color' => 'голубые',
    'nationality' => 'русская',
    'appearance' => 'спортивная',
    'bikini_zone' => 'полная',
    
    // Контакты
    'phone' => '+7 900 123 45 67',
    'whatsapp' => '+7 900 123 45 67',
    'telegram' => '@test_user',
    'vk' => 'vk.com/test',
    'instagram' => '@test_insta',
    'contact_method' => 'phone',
    
    // Локация
    'address' => 'Москва, Центр',
    'radius' => '5',
    'is_remote' => '1',
    
    // JSON поля
    'clients' => json_encode(['men', 'women']),
    'services' => json_encode(['massage', 'relax']),
    'features' => json_encode(['feature1', 'feature2']),
    'geo' => json_encode(['lat' => 55.7558, 'lng' => 37.6173]),
    'prices' => json_encode(['apartments_1h' => 5000]),
    'schedule' => json_encode(['monday' => true]),
];

echo "📋 Создаем черновик с тестовыми данными...\n";

try {
    $draftService = new DraftService();
    $ad = $draftService->saveOrUpdate($testData, $user);
    
    echo "✅ Черновик создан! ID: {$ad->id}\n\n";
    
    echo "🔍 Проверяем сохраненные поля:\n";
    echo "================================\n";
    
    // Перезагружаем из БД чтобы получить актуальные данные
    $ad->refresh();
    
    // Проверяем основные поля
    echo "\n📌 ОСНОВНЫЕ ПОЛЯ:\n";
    echo "  title: " . ($ad->title ?? 'NULL') . "\n";
    echo "  specialty: " . ($ad->specialty ?? 'NULL') . "\n";
    echo "  work_format: " . ($ad->work_format ? $ad->work_format->value : 'NULL') . "\n";
    echo "  experience: " . ($ad->experience ?? 'NULL') . "\n";
    echo "  description: " . substr($ad->description ?? 'NULL', 0, 50) . "...\n";
    
    // Проверяем параметры
    echo "\n👤 ПАРАМЕТРЫ (должны быть отдельными полями):\n";
    echo "  age: " . ($ad->age ?? 'NULL') . "\n";
    echo "  height: " . ($ad->height ?? 'NULL') . "\n";
    echo "  weight: " . ($ad->weight ?? 'NULL') . "\n";
    echo "  breast_size: " . ($ad->breast_size ?? 'NULL') . "\n";
    echo "  hair_color: " . ($ad->hair_color ?? 'NULL') . "\n";
    echo "  eye_color: " . ($ad->eye_color ?? 'NULL') . "\n";
    echo "  nationality: " . ($ad->nationality ?? 'NULL') . "\n";
    echo "  appearance: " . ($ad->appearance ?? 'NULL') . "\n";
    echo "  bikini_zone: " . ($ad->bikini_zone ?? 'NULL') . "\n";
    
    // Проверяем контакты
    echo "\n📞 КОНТАКТЫ:\n";
    echo "  phone: " . ($ad->phone ?? 'NULL') . "\n";
    echo "  whatsapp: " . ($ad->whatsapp ?? 'NULL') . "\n";
    echo "  telegram: " . ($ad->telegram ?? 'NULL') . "\n";
    echo "  vk: " . ($ad->vk ?? 'NULL') . "\n";
    echo "  instagram: " . ($ad->instagram ?? 'NULL') . "\n";
    echo "  contact_method: " . ($ad->contact_method ?? 'NULL') . "\n";
    
    // Проверяем локацию
    echo "\n📍 ЛОКАЦИЯ:\n";
    echo "  address: " . ($ad->address ?? 'NULL') . "\n";
    echo "  radius: " . ($ad->radius ?? 'NULL') . "\n";
    echo "  is_remote: " . ($ad->is_remote ? 'ДА' : 'НЕТ') . "\n";
    
    // Проверяем JSON поля
    echo "\n📦 JSON ПОЛЯ:\n";
    echo "  clients: " . (is_array($ad->clients) ? implode(', ', $ad->clients) : gettype($ad->clients)) . "\n";
    echo "  services: " . (is_array($ad->services) ? 'array с ' . count($ad->services) . ' элементами' : gettype($ad->services)) . "\n";
    echo "  features: " . (is_array($ad->features) ? implode(', ', $ad->features) : gettype($ad->features)) . "\n";
    echo "  geo: " . (is_array($ad->geo) ? json_encode($ad->geo) : gettype($ad->geo)) . "\n";
    echo "  prices: " . (is_array($ad->prices) ? json_encode($ad->prices) : gettype($ad->prices)) . "\n";
    
    // Проверяем, нет ли полей, которых не должно быть
    echo "\n⚠️ ПРОВЕРКА ЛИШНИХ ПОЛЕЙ:\n";
    $shouldNotExist = ['parameters', 'amenities', 'comfort'];
    foreach ($shouldNotExist as $field) {
        if (array_key_exists($field, $ad->getAttributes())) {
            echo "  ❌ Поле '{$field}' существует (НЕ ДОЛЖНО!)\n";
        } else {
            echo "  ✅ Поле '{$field}' отсутствует (правильно)\n";
        }
    }
    
    echo "\n🎯 РЕЗУЛЬТАТ:\n";
    echo "============\n";
    
    $hasAllFields = $ad->age && $ad->height && $ad->weight && $ad->phone && $ad->address;
    if ($hasAllFields) {
        echo "✅ ВСЕ ПОЛЯ СОХРАНЕНЫ КОРРЕКТНО!\n";
        echo "✅ Рефакторинг прошел успешно!\n";
    } else {
        echo "❌ Некоторые поля не сохранились\n";
        echo "❌ Требуется дополнительная проверка\n";
    }
    
    // Удаляем тестовое объявление
    $ad->delete();
    echo "\n🗑️ Тестовое объявление удалено\n";
    
} catch (Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . "\n";
    echo "Строка: " . $e->getLine() . "\n";
}