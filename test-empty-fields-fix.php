<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\DraftService;

echo "🔍 ТЕСТ ИСПРАВЛЕНИЯ: Сохранение пустых полей в секциях\n";
echo "========================================================\n\n";

// Найдем черновик для тестирования
$draft = Ad::where('status', 'draft')->first();

if (!$draft) {
    echo "❌ Черновик не найден для тестирования\n";
    exit;
}

$draftService = app(DraftService::class);
$user = $draft->user;

echo "📝 Тестируем черновик ID: {$draft->id}\n\n";

// ========== ТЕСТ 1: additional_features ==========
echo "🔄 ТЕСТ 1: Поле additional_features (Дополнительные особенности)\n";
echo "-----------------------------------------------------------------\n";

// Сохраняем с заполненным текстом
$data = [
    'title' => $draft->title,
    'additional_features' => 'Тестовые дополнительные особенности',
    'category' => $draft->category,
];

$result = $draftService->saveOrUpdate($data, $user, $draft->id);
$result->refresh();

echo "После сохранения с текстом:\n";
echo "  additional_features: '{$result->additional_features}'\n\n";

// Теперь очищаем поле
$data['additional_features'] = '';
$result = $draftService->saveOrUpdate($data, $user, $draft->id);
$result->refresh();

echo "После очистки (пустая строка):\n";
echo "  additional_features: " . ($result->additional_features !== null ? "'{$result->additional_features}'" : "NULL") . "\n";
echo "  Длина: " . strlen($result->additional_features ?? '') . " символов\n";
echo "  ✅ Пустая строка " . (strlen($result->additional_features ?? '') === 0 ? "СОХРАНЕНА" : "НЕ сохранена") . "\n\n";

// ========== ТЕСТ 2: schedule_notes ==========
echo "🔄 ТЕСТ 2: Поле schedule_notes (Дополнительная информация о графике)\n";
echo "--------------------------------------------------------------------\n";

// Сохраняем с заполненным текстом
$data = [
    'title' => $draft->title,
    'schedule_notes' => 'Работаю по предварительной записи',
    'category' => $draft->category,
];

$result = $draftService->saveOrUpdate($data, $user, $draft->id);
$result->refresh();

echo "После сохранения с текстом:\n";
echo "  schedule_notes: '{$result->schedule_notes}'\n\n";

// Теперь очищаем поле
$data['schedule_notes'] = '';
$result = $draftService->saveOrUpdate($data, $user, $draft->id);
$result->refresh();

echo "После очистки (пустая строка):\n";
echo "  schedule_notes: " . ($result->schedule_notes !== null ? "'{$result->schedule_notes}'" : "NULL") . "\n";
echo "  Длина: " . strlen($result->schedule_notes ?? '') . " символов\n";
echo "  ✅ Пустая строка " . (strlen($result->schedule_notes ?? '') === 0 ? "СОХРАНЕНА" : "НЕ сохранена") . "\n\n";

// ========== ТЕСТ 3: Проверка prepareForDisplay ==========
echo "🔄 ТЕСТ 3: Проверка prepareForDisplay\n";
echo "--------------------------------------\n";

$displayData = $draftService->prepareForDisplay($result);

echo "После prepareForDisplay:\n";
echo "  description: " . (isset($displayData['description']) ? "'{$displayData['description']}'" : "НЕ УСТАНОВЛЕНО") . "\n";
echo "  additional_features: " . (isset($displayData['additional_features']) ? "'{$displayData['additional_features']}'" : "НЕ УСТАНОВЛЕНО") . "\n";
echo "  schedule_notes: " . (isset($displayData['schedule_notes']) ? "'{$displayData['schedule_notes']}'" : "НЕ УСТАНОВЛЕНО") . "\n\n";

// ========== ТЕСТ 4: Проверка в БД ==========
echo "🔄 ТЕСТ 4: Проверка значений в базе данных\n";
echo "-------------------------------------------\n";

$rawData = \DB::table('ads')
    ->where('id', $draft->id)
    ->select('description', 'additional_features', 'schedule_notes')
    ->first();

echo "В базе данных:\n";
echo "  description: " . ($rawData->description !== null ? "'{$rawData->description}'" : "NULL") . "\n";
echo "  additional_features: " . ($rawData->additional_features !== null ? "'{$rawData->additional_features}'" : "NULL") . "\n";
echo "  schedule_notes: " . ($rawData->schedule_notes !== null ? "'{$rawData->schedule_notes}'" : "NULL") . "\n\n";

// ========== ИТОГИ ==========
echo "📊 ИТОГИ ТЕСТИРОВАНИЯ:\n";
echo "======================\n";

$issues = [];

// Проверка additional_features
if ($rawData->additional_features === null || strlen($rawData->additional_features) > 0) {
    $issues[] = "❌ additional_features: Пустая строка НЕ сохраняется";
} else {
    echo "✅ additional_features: Пустая строка корректно сохраняется\n";
}

// Проверка schedule_notes
if ($rawData->schedule_notes === null || strlen($rawData->schedule_notes) > 0) {
    $issues[] = "❌ schedule_notes: Пустая строка НЕ сохраняется";
} else {
    echo "✅ schedule_notes: Пустая строка корректно сохраняется\n";
}

// Проверка description (должно работать из предыдущего исправления)
if ($rawData->description === null) {
    $issues[] = "❌ description: NULL вместо пустой строки";
} else {
    echo "✅ description: Корректно сохраняется как строка\n";
}

// Проверка prepareForDisplay
if (!isset($displayData['additional_features']) || !isset($displayData['schedule_notes'])) {
    $issues[] = "❌ prepareForDisplay не возвращает все поля";
} else {
    echo "✅ prepareForDisplay: Возвращает все текстовые поля\n";
}

if (empty($issues)) {
    echo "\n🎉 ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО!\n";
    echo "Проблема с сохранением пустых полей ИСПРАВЛЕНА!\n";
} else {
    echo "\n⚠️ ОБНАРУЖЕНЫ ПРОБЛЕМЫ:\n";
    foreach ($issues as $issue) {
        echo "  " . $issue . "\n";
    }
}

echo "\n💡 РЕКОМЕНДАЦИЯ:\n";
echo "Протестируйте в браузере:\n";
echo "1. Откройте черновик ID {$draft->id}\n";
echo "2. Очистите поля:\n";
echo "   - 'Дополнительные особенности' в секции 'Особенности'\n";
echo "   - 'Дополнительная информация о графике работы' в секции 'График работы'\n";
echo "3. Нажмите 'Сохранить черновик'\n";
echo "4. Обновите страницу и проверьте, что поля остались пустыми\n";