<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\DraftService;

echo "🔍 ТЕСТ: Проверка обработки пустого описания через DraftService\n";
echo "================================================================\n\n";

// Найдем черновик для тестирования
$draft = Ad::where('status', 'draft')->first();

if (!$draft) {
    echo "❌ Черновик не найден для тестирования\n";
    exit;
}

$draftService = app(DraftService::class);
$user = $draft->user;

echo "📝 Тестируем черновик ID: {$draft->id}\n";
echo "Текущее описание: " . ($draft->description ? "'{$draft->description}'" : "NULL") . "\n\n";

// Тест 1: Отправка пустого описания через DraftService
echo "🔄 Тест 1: Сохраняем пустое описание через DraftService\n";
$data = [
    'title' => $draft->title,
    'description' => '',  // Пустое описание
    'category' => $draft->category,
    'specialty' => $draft->specialty,
];

$result = $draftService->saveOrUpdate($data, $user, $draft->id);
$result->refresh();

echo "После DraftService с пустым description:\n";
echo "  - description в результате: " . ($result->description !== null ? "'{$result->description}'" : "NULL") . "\n";
echo "  - Тип: " . gettype($result->description) . "\n";
echo "  - Длина: " . strlen($result->description ?? '') . " символов\n\n";

// Тест 2: Отправка без поля description
echo "🔄 Тест 2: Сохраняем БЕЗ поля description в массиве данных\n";
$data = [
    'title' => $draft->title,
    // description НЕ передаем вообще
    'category' => $draft->category,
    'specialty' => $draft->specialty,
];

$result = $draftService->saveOrUpdate($data, $user, $draft->id);
$result->refresh();

echo "После DraftService БЕЗ description в данных:\n";
echo "  - description в результате: " . ($result->description !== null ? "'{$result->description}'" : "NULL") . "\n";
echo "  - Тип: " . gettype($result->description) . "\n\n";

// Тест 3: Проверяем prepareForDisplay
echo "🔄 Тест 3: Проверяем prepareForDisplay\n";
$displayData = $draftService->prepareForDisplay($result);

echo "После prepareForDisplay:\n";
echo "  - description в массиве: " . (isset($displayData['description']) ? "'{$displayData['description']}'" : "НЕ УСТАНОВЛЕНО") . "\n";
echo "  - Тип: " . (isset($displayData['description']) ? gettype($displayData['description']) : "N/A") . "\n\n";

// Тест 4: Имитация FormData (как с фронтенда)
echo "🔄 Тест 4: Имитация данных из FormData\n";
$formData = [
    'title' => $draft->title,
    'description' => '', // FormData отправляет пустую строку
    'category' => $draft->category,
    'specialty' => $draft->specialty,
    'prices' => [
        'apartments_1h' => '5000',
        'outcall_1h' => '6000'
    ]
];

// Преобразуем prices как в контроллере
$prices = [];
foreach ($formData as $key => $value) {
    if ($key === 'prices' && is_array($value)) {
        $formData['prices'] = $value;
    }
}

$result = $draftService->saveOrUpdate($formData, $user, $draft->id);
$result->refresh();

echo "После сохранения с данными как из FormData:\n";
echo "  - description: " . ($result->description !== null ? "'{$result->description}'" : "NULL") . "\n";
echo "  - В БД напрямую: ";
$rawData = \DB::table('ads')->where('id', $draft->id)->select('description')->first();
echo ($rawData->description !== null ? "'{$rawData->description}'" : "NULL") . "\n\n";

echo "✅ Тестирование завершено\n";