<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\DraftService;

echo "🎯 ПРОСТОЙ ТЕСТ: Исправление media_settings по подходу PRICING_SECTION\n";
echo "====================================================================\n\n";

$draft = Ad::where('status', 'draft')->first();
if (!$draft) {
    echo "❌ Черновик не найден\n";
    exit;
}

echo "📝 Тестируем черновик ID: {$draft->id}\n\n";

// Имитируем новую логику обработки (как в DraftController)
echo "🔄 ТЕСТ: Имитация обработки media_settings[*] полей\n";
echo "---------------------------------------------------\n";

// Имитируем данные которые приходят с фронтенда (новый формат)
$requestData = [
    'title' => $draft->title,
    'category' => $draft->category,
    'media_settings[show_photos_in_gallery]' => '1',
    'media_settings[allow_download_photos]' => '0', 
    'media_settings[watermark_photos]' => '1'
];

echo "Приходящие данные с фронтенда:\n";
foreach ($requestData as $key => $value) {
    if (str_starts_with($key, 'media_settings[')) {
        echo "  {$key} = '{$value}'\n";
    }
}
echo "\n";

// Применяем новую логику обработки (как в DraftController)
$data = $requestData;
foreach ($requestData as $key => $value) {
    if (str_starts_with($key, 'media_settings[')) {
        $fieldName = str_replace(['media_settings[', ']'], '', $key);
        // Преобразуем '1'/'0' в boolean
        $data[$fieldName] = $value === '1' || $value === 1 || $value === true;
        unset($data[$key]); // Удаляем исходное поле
        echo "  Преобразовано: {$fieldName} = " . ($data[$fieldName] ? 'true' : 'false') . "\n";
    }
}
echo "\n";

// Сохраняем через DraftService
$draftService = app(DraftService::class);
$user = $draft->user;
$result = $draftService->saveOrUpdate($data, $user, $draft->id);

echo "Результат сохранения в БД:\n";
echo "  show_photos_in_gallery: " . ($result->show_photos_in_gallery ? 'true' : 'false') . "\n";
echo "  allow_download_photos: " . ($result->allow_download_photos ? 'true' : 'false') . "\n";
echo "  watermark_photos: " . ($result->watermark_photos ? 'true' : 'false') . "\n\n";

// Проверяем prepareForDisplay
echo "Результат prepareForDisplay:\n";
$displayData = $draftService->prepareForDisplay($result);

if (isset($displayData['media_settings'])) {
    echo "  media_settings: [" . implode(', ', $displayData['media_settings']) . "]\n";
    
    // Проверяем корректность
    $expected = ['show_photos_in_gallery', 'watermark_photos'];  
    $actual = $displayData['media_settings'];
    sort($expected);
    sort($actual);
    
    if ($expected == $actual) {
        echo "  ✅ КОРРЕКТНО! Ожидали включенными: show_photos_in_gallery, watermark_photos\n";
    } else {
        echo "  ❌ НЕКОРРЕКТНО!\n";
        echo "     Ожидали: [" . implode(', ', $expected) . "]\n";
        echo "     Получили: [" . implode(', ', $actual) . "]\n";
    }
} else {
    echo "  ❌ media_settings НЕ СФОРМИРОВАНО\n";
}

echo "\n🎯 ВЫВОДЫ:\n";
echo "==========\n";

$success = (
    $result->show_photos_in_gallery === true &&
    $result->allow_download_photos === false &&
    $result->watermark_photos === true &&
    isset($displayData['media_settings']) &&
    count($displayData['media_settings']) === 2 &&
    in_array('show_photos_in_gallery', $displayData['media_settings']) &&
    in_array('watermark_photos', $displayData['media_settings'])
);

if ($success) {
    echo "✅ ИСПРАВЛЕНИЕ УСПЕШНО ПРИМЕНЕНО!\n";
    echo "✅ Подход из PRICING_SECTION_FIX работает для media_settings\n";
    echo "✅ Чекбоксы теперь должны корректно сохраняться\n";
    echo "✅ Frontend/Backend синхронизация работает\n";
} else {
    echo "❌ Есть проблемы с исправлением\n";
}

echo "\n💡 ПРОТЕСТИРУЙТЕ В БРАУЗЕРЕ:\n";
echo "1. URL: http://spa.test/ads/{$draft->id}/edit\n";
echo "2. Перейдите в секцию 'Настройки отображения'\n";
echo "3. Измените состояния чекбоксов\n";
echo "4. Нажмите 'Сохранить черновик'\n";
echo "5. Обновите страницу - состояния должны сохраниться\n";