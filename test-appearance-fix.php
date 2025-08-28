<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;

echo "🔍 ТЕСТ СОХРАНЕНИЯ ПОЛЯ APPEARANCE\n";
echo "=====================================\n\n";

// Найдем пользователя для теста
$user = User::first();
if (!$user) {
    echo "❌ Нет пользователей в БД для теста\n";
    exit(1);
}

echo "✅ Используем пользователя: {$user->email}\n\n";

// Создаем тестовое объявление
$testAd = new Ad();
$testAd->user_id = $user->id;
$testAd->title = "Тест Внешность";
$testAd->category = 'relax';
$testAd->status = 'draft';
$testAd->appearance = 'slavic'; // Устанавливаем значение appearance
$testAd->description = 'Тестовое объявление для проверки поля appearance';
$testAd->phone = '+7999999999';
$testAd->price = 5000;
$testAd->save();

echo "📝 Создано тестовое объявление ID: {$testAd->id}\n";
echo "   appearance установлен: {$testAd->appearance}\n\n";

// Проверяем сохранение
$savedAd = Ad::find($testAd->id);
if ($savedAd) {
    echo "✅ Объявление найдено в БД\n";
    echo "   ID: {$savedAd->id}\n";
    echo "   Title: {$savedAd->title}\n";
    echo "   Appearance: " . ($savedAd->appearance ?: 'NULL') . "\n\n";
    
    if ($savedAd->appearance === 'slavic') {
        echo "✅ УСПЕХ! Поле appearance корректно сохраняется в БД!\n";
    } else {
        echo "❌ ОШИБКА! Поле appearance не сохранилось корректно\n";
        echo "   Ожидалось: 'slavic'\n";
        echo "   Получено: '{$savedAd->appearance}'\n";
    }
    
    // Тестируем обновление
    echo "\n📝 Тестируем обновление appearance...\n";
    $savedAd->appearance = 'mediterranean';
    $savedAd->save();
    
    $updatedAd = Ad::find($testAd->id);
    echo "   Новое значение: " . ($updatedAd->appearance ?: 'NULL') . "\n";
    
    if ($updatedAd->appearance === 'mediterranean') {
        echo "✅ Обновление работает корректно!\n";
    } else {
        echo "❌ Ошибка при обновлении\n";
    }
    
    // Удаляем тестовое объявление
    $updatedAd->delete();
    echo "\n🗑️ Тестовое объявление удалено\n";
} else {
    echo "❌ Не удалось найти сохраненное объявление\n";
}

echo "\n===========================================\n";
echo "🎯 РЕКОМЕНДАЦИИ:\n";
echo "1. Проверьте в браузере создание нового объявления\n";
echo "2. Выберите 'Внешность' в секции Параметры\n";
echo "3. Сохраните как черновик\n";
echo "4. Откройте снова и проверьте, что значение сохранилось\n";