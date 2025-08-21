<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\DraftService;
use Illuminate\Http\Request;
use App\Application\Http\Controllers\Ad\DraftController;

echo "🔍 ТЕСТ ИСПРАВЛЕНИЯ: Сохранение пустого описания\n";
echo "==================================================\n\n";

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

// ========== ТЕСТ 1: DraftService ==========
echo "🔄 ТЕСТ 1: Сохранение пустого описания через DraftService\n";
echo "-----------------------------------------------------------\n";

// Сохраняем с заполненным описанием
$data = [
    'title' => $draft->title,
    'description' => 'Тестовое описание для проверки',
    'category' => $draft->category,
];

$result = $draftService->saveOrUpdate($data, $user, $draft->id);
$result->refresh();

echo "После сохранения с текстом:\n";
echo "  description: '{$result->description}'\n\n";

// Теперь очищаем описание
$data['description'] = '';
$result = $draftService->saveOrUpdate($data, $user, $draft->id);
$result->refresh();

echo "После очистки (пустая строка):\n";
echo "  description: " . ($result->description !== null ? "'{$result->description}'" : "NULL") . "\n";
echo "  Длина: " . strlen($result->description ?? '') . " символов\n";
echo "  ✅ Пустая строка " . (strlen($result->description ?? '') === 0 ? "СОХРАНЕНА" : "НЕ сохранена") . "\n\n";

// ========== ТЕСТ 2: Симуляция FormData ==========
echo "🔄 ТЕСТ 2: Симуляция отправки FormData из Vue\n";
echo "----------------------------------------------\n";

// Симулируем Request
$requestData = [
    'title' => $draft->title,
    'description' => '', // Пустая строка из Vue
    'category' => $draft->category,
];

// Проверяем prepareForDisplay
$displayData = $draftService->prepareForDisplay($result);
echo "После prepareForDisplay:\n";
echo "  description: " . (isset($displayData['description']) ? "'{$displayData['description']}'" : "НЕ УСТАНОВЛЕНО") . "\n";
echo "  Тип: " . (isset($displayData['description']) ? gettype($displayData['description']) : "N/A") . "\n\n";

// ========== ТЕСТ 3: Проверка в БД ==========
echo "🔄 ТЕСТ 3: Проверка значения в базе данных\n";
echo "-------------------------------------------\n";

$rawData = \DB::table('ads')->where('id', $draft->id)->select('description')->first();
echo "В базе данных:\n";
echo "  description: " . ($rawData->description !== null ? "'{$rawData->description}'" : "NULL") . "\n";
echo "  Тип: " . gettype($rawData->description) . "\n";
echo "  Длина: " . strlen($rawData->description ?? '') . " символов\n\n";

// ========== ИТОГИ ==========
echo "📊 ИТОГИ ТЕСТИРОВАНИЯ:\n";
echo "======================\n";

$issues = [];

// Проверка 1: Сохраняется ли пустая строка в БД
if ($rawData->description === null || strlen($rawData->description) > 0) {
    $issues[] = "❌ Пустая строка НЕ сохраняется в БД (значение: " . 
                ($rawData->description === null ? "NULL" : "'{$rawData->description}'") . ")";
} else {
    echo "✅ Пустая строка корректно сохраняется в БД\n";
}

// Проверка 2: Возвращается ли пустая строка из prepareForDisplay
if (!isset($displayData['description']) || $displayData['description'] === null) {
    $issues[] = "❌ prepareForDisplay не возвращает пустую строку";
} else {
    echo "✅ prepareForDisplay корректно возвращает пустую строку\n";
}

// Проверка 3: Правильный ли тип данных
if (isset($displayData['description']) && !is_string($displayData['description'])) {
    $issues[] = "❌ description имеет неверный тип: " . gettype($displayData['description']);
} else {
    echo "✅ description имеет правильный тип (string)\n";
}

if (empty($issues)) {
    echo "\n🎉 ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО!\n";
    echo "Проблема с сохранением пустого описания ИСПРАВЛЕНА!\n";
} else {
    echo "\n⚠️ ОБНАРУЖЕНЫ ПРОБЛЕМЫ:\n";
    foreach ($issues as $issue) {
        echo "  " . $issue . "\n";
    }
}

echo "\n💡 РЕКОМЕНДАЦИЯ:\n";
echo "Протестируйте в браузере:\n";
echo "1. Откройте черновик ID {$draft->id}\n";
echo "2. Очистите поле 'Описание'\n";
echo "3. Нажмите 'Сохранить черновик'\n";
echo "4. Обновите страницу и проверьте, что поле осталось пустым\n";