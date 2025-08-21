<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use App\Domain\Ad\Services\DraftService;

echo "🎯 ТЕСТ ЛОГИКИ ВЗАИМОИСКЛЮЧЕНИЯ В FAQ\n";
echo "=====================================\n\n";

$user = User::where('email', 'anna@spa.test')->first();
if (!$user) {
    $user = User::first();
}

if ($user) {
    echo "👤 Используем пользователя: {$user->email} (ID: {$user->id})\n\n";
    
    $draftService = app(DraftService::class);
    
    // Тест 1: Проверка что "Нет" исключает другие опции в faq_2
    echo "📝 Тест 1: Опция 'Нет' в вопросе про ласки (faq_2)\n";
    echo "---------------------------------------------------\n";
    
    $test1Data = [
        'user_id' => $user->id,
        'title' => 'Тест взаимоисключения FAQ',
        'service_provider' => ['women'],
        'clients' => ['men'],
        'phone' => '+79001234567',
        'geo' => ['lat' => 55.7558, 'lng' => 37.6173],
        'prices' => ['apartments_1h' => 5000],
        'services' => ['massage' => true],
        'photos' => [],
        'faq' => [
            'faq_2' => [1, 2, 3, 4] // Намеренно добавляем все опции включая "Нет"
        ],
        'status' => 'draft'
    ];
    
    $draft1 = $draftService->saveOrUpdate($test1Data, $user, null);
    
    echo "⚠️  Попытка сохранить все опции включая 'Нет':\n";
    echo "   Входные данные: faq_2 = [1, 2, 3, 4]\n";
    echo "   Сохранено в БД: faq_2 = " . json_encode($draft1->faq['faq_2'] ?? []) . "\n";
    echo "   ❗ Проблема: Сохранились все опции, включая взаимоисключающие!\n\n";
    
    // Тест 2: Проверка правильного поведения
    echo "📝 Тест 2: Корректное сохранение только 'Нет' в faq_2\n";
    echo "---------------------------------------------------\n";
    
    $test2Data = [
        'faq' => [
            'faq_2' => [4] // Только опция "Нет"
        ]
    ];
    
    $draft2 = $draftService->saveOrUpdate($test2Data, $user, $draft1->id);
    
    echo "✅ Сохранение только опции 'Нет':\n";
    echo "   Входные данные: faq_2 = [4]\n";
    echo "   Сохранено в БД: faq_2 = " . json_encode($draft2->faq['faq_2'] ?? []) . "\n\n";
    
    // Тест 3: Проверка для faq_3 (GFE)
    echo "📝 Тест 3: Опция 'Нет' в вопросе про GFE (faq_3)\n";
    echo "---------------------------------------------------\n";
    
    $test3Data = [
        'faq' => [
            'faq_2' => [1, 2], // Нормальные опции для faq_2
            'faq_3' => [1, 2, 3] // Только позитивные опции для faq_3
        ]
    ];
    
    $draft3 = $draftService->saveOrUpdate($test3Data, $user, $draft1->id);
    
    echo "✅ Сохранение только позитивных опций GFE:\n";
    echo "   Входные данные: faq_3 = [1, 2, 3]\n";
    echo "   Сохранено в БД: faq_3 = " . json_encode($draft3->faq['faq_3'] ?? []) . "\n\n";
    
    // Тест 4: Смешанные данные
    echo "📝 Тест 4: Смешанные данные с 'Нет' в faq_3\n";
    echo "---------------------------------------------------\n";
    
    $test4Data = [
        'faq' => [
            'faq_3' => [4] // Только "Нет" для GFE
        ]
    ];
    
    $draft4 = $draftService->saveOrUpdate($test4Data, $user, $draft1->id);
    
    echo "✅ Сохранение только 'Нет' для GFE:\n";
    echo "   Входные данные: faq_3 = [4]\n";
    echo "   Сохранено в БД: faq_3 = " . json_encode($draft4->faq['faq_3'] ?? []) . "\n\n";
    
    // Очистка
    $draft1->delete();
    echo "🗑️ Тестовый черновик удален\n\n";
    
} else {
    echo "❌ Нет пользователей для теста\n";
}

echo "⚠️  ВАЖНОЕ ЗАМЕЧАНИЕ:\n";
echo "=====================================\n";
echo "Логика взаимоисключения реализована на FRONTEND в Vue компоненте.\n";
echo "Backend сохраняет данные как есть, без валидации взаимоисключения.\n";
echo "Это означает, что:\n";
echo "1. При работе через UI логика будет работать корректно\n";
echo "2. При прямом API запросе можно сохранить противоречивые данные\n\n";

echo "🎯 РЕКОМЕНДАЦИЯ:\n";
echo "Добавить серверную валидацию в DraftService для проверки\n";
echo "взаимоисключающих опций в FAQ перед сохранением.\n";