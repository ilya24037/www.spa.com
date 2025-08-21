<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use App\Domain\Ad\Services\DraftService;

echo "🎯 ОТЛАДКА СОХРАНЕНИЯ FAQ\n";
echo "=====================================\n\n";

$user = User::where('email', 'anna@spa.test')->first();
if (!$user) {
    $user = User::first();
}

if ($user) {
    echo "👤 Используем пользователя: {$user->email} (ID: {$user->id})\n\n";
    
    // Простой тест - создаем черновик напрямую через модель
    echo "📝 Тест 1: Прямое сохранение через модель\n";
    echo "---------------------------------------------------\n";
    
    $faqData = [
        'faq_1' => 1,
        'faq_2' => [1, 2, 3],
        'faq_3' => [1],
        'faq_5' => 2
    ];
    
    $ad = new Ad();
    $ad->user_id = $user->id;
    $ad->title = 'Тест FAQ напрямую';
    $ad->status = 'draft';
    $ad->faq = $faqData; // Eloquent должен автоматически преобразовать в JSON
    $ad->save();
    
    echo "✅ Создан черновик ID: {$ad->id}\n";
    echo "📋 FAQ при сохранении: " . json_encode($faqData, JSON_UNESCAPED_UNICODE) . "\n";
    
    // Перезагружаем из БД
    $ad->refresh();
    echo "📋 FAQ после загрузки: " . json_encode($ad->faq, JSON_UNESCAPED_UNICODE) . "\n";
    
    if ($ad->faq && is_array($ad->faq) && count($ad->faq) > 0) {
        echo "✅ FAQ сохранен корректно!\n\n";
    } else {
        echo "❌ FAQ не сохранился!\n\n";
    }
    
    // Тест 2: Сохранение через DraftService
    echo "📝 Тест 2: Сохранение через DraftService\n";
    echo "---------------------------------------------------\n";
    
    $draftService = app(DraftService::class);
    
    $testData = [
        'user_id' => $user->id,
        'title' => 'Тест FAQ через сервис',
        'service_provider' => ['women'],
        'clients' => ['men'],
        'phone' => '+79001234567',
        'geo' => ['lat' => 55.7558, 'lng' => 37.6173],
        'prices' => ['apartments_1h' => 5000],
        'services' => ['massage' => true],
        'photos' => [],
        'faq' => $faqData,
        'status' => 'draft'
    ];
    
    echo "📋 FAQ перед сохранением: " . json_encode($testData['faq'], JSON_UNESCAPED_UNICODE) . "\n";
    
    $draft = $draftService->saveOrUpdate($testData, $user, null);
    
    echo "✅ Создан черновик ID: {$draft->id}\n";
    echo "📋 FAQ после сохранения: " . json_encode($draft->faq, JSON_UNESCAPED_UNICODE) . "\n";
    
    // Перезагружаем из БД
    $draft->refresh();
    echo "📋 FAQ после перезагрузки: " . json_encode($draft->faq, JSON_UNESCAPED_UNICODE) . "\n";
    
    if ($draft->faq && is_array($draft->faq) && count($draft->faq) > 0) {
        echo "✅ FAQ сохранен корректно через сервис!\n\n";
    } else {
        echo "❌ FAQ не сохранился через сервис!\n\n";
    }
    
    // Тест 3: Проверка что в БД
    echo "📝 Тест 3: Проверка данных в БД напрямую\n";
    echo "---------------------------------------------------\n";
    
    $rawData = \DB::table('ads')
        ->where('id', $draft->id)
        ->select('faq')
        ->first();
    
    echo "📋 Сырые данные из БД: " . ($rawData->faq ?? 'NULL') . "\n";
    
    if ($rawData->faq) {
        $decoded = json_decode($rawData->faq, true);
        echo "📋 Декодированные данные: " . json_encode($decoded, JSON_UNESCAPED_UNICODE) . "\n";
    }
    
    // Очистка
    $ad->delete();
    $draft->delete();
    echo "\n🗑️ Тестовые черновики удалены\n";
    
} else {
    echo "❌ Нет пользователей для теста\n";
}

echo "\n🎯 ДИАГНОСТИКА:\n";
echo "================\n";
echo "1. Проверьте, что поле 'faq' добавлено в \$fillable массив модели Ad\n";
echo "2. Проверьте, что поле 'faq' добавлено в \$casts как 'array' или в \$jsonFields\n";
echo "3. Проверьте миграцию - поле должно быть типа JSON или TEXT\n";