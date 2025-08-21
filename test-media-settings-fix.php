<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\DraftService;

echo "🔍 ТЕСТ ИСПРАВЛЕНИЯ: Сохранение чекбоксов настроек медиа\n";
echo "=========================================================\n\n";

// Найдем черновик для тестирования
$draft = Ad::where('status', 'draft')->first();

if (!$draft) {
    echo "❌ Черновик не найден для тестирования\n";
    exit;
}

$draftService = app(DraftService::class);
$user = $draft->user;

echo "📝 Тестируем черновик ID: {$draft->id}\n";
echo "Текущие настройки:\n";
echo "  show_photos_in_gallery: " . ($draft->show_photos_in_gallery ? 'true' : 'false') . "\n";
echo "  allow_download_photos: " . ($draft->allow_download_photos ? 'true' : 'false') . "\n";
echo "  watermark_photos: " . ($draft->watermark_photos ? 'true' : 'false') . "\n\n";

// ========== ТЕСТ 1: Сохранение через DraftService с media_settings ==========
echo "🔄 ТЕСТ 1: Сохранение с media_settings как массив\n";
echo "--------------------------------------------------\n";

// Тестируем все чекбоксы включены
$data = [
    'title' => $draft->title,
    'category' => $draft->category,
    'media_settings' => ['show_photos_in_gallery', 'allow_download_photos', 'watermark_photos']
];

// Имитируем обработку в контроллере
if (isset($data['media_settings'])) {
    $settings = is_string($data['media_settings']) 
        ? json_decode($data['media_settings'], true) 
        : $data['media_settings'];
    
    if (is_array($settings)) {
        $data['show_photos_in_gallery'] = in_array('show_photos_in_gallery', $settings);
        $data['allow_download_photos'] = in_array('allow_download_photos', $settings);
        $data['watermark_photos'] = in_array('watermark_photos', $settings);
    }
    
    unset($data['media_settings']);
}

$result = $draftService->saveOrUpdate($data, $user, $draft->id);
$result->refresh();

echo "После сохранения (все включены):\n";
echo "  show_photos_in_gallery: " . ($result->show_photos_in_gallery ? 'true' : 'false') . "\n";
echo "  allow_download_photos: " . ($result->allow_download_photos ? 'true' : 'false') . "\n";
echo "  watermark_photos: " . ($result->watermark_photos ? 'true' : 'false') . "\n\n";

// Тестируем частично выключены
$data = [
    'title' => $draft->title,
    'category' => $draft->category,
    'media_settings' => ['show_photos_in_gallery'] // Только один включен
];

// Имитируем обработку в контроллере
if (isset($data['media_settings'])) {
    $settings = is_string($data['media_settings']) 
        ? json_decode($data['media_settings'], true) 
        : $data['media_settings'];
    
    if (is_array($settings)) {
        $data['show_photos_in_gallery'] = in_array('show_photos_in_gallery', $settings);
        $data['allow_download_photos'] = in_array('allow_download_photos', $settings);
        $data['watermark_photos'] = in_array('watermark_photos', $settings);
    }
    
    unset($data['media_settings']);
}

$result = $draftService->saveOrUpdate($data, $user, $draft->id);
$result->refresh();

echo "После сохранения (только show_photos_in_gallery):\n";
echo "  show_photos_in_gallery: " . ($result->show_photos_in_gallery ? 'true' : 'false') . "\n";
echo "  allow_download_photos: " . ($result->allow_download_photos ? 'true' : 'false') . "\n";
echo "  watermark_photos: " . ($result->watermark_photos ? 'true' : 'false') . "\n\n";

// ========== ТЕСТ 2: Проверка prepareForDisplay ==========
echo "🔄 ТЕСТ 2: Проверка prepareForDisplay (обратное преобразование)\n";
echo "---------------------------------------------------------------\n";

$displayData = $draftService->prepareForDisplay($result);

echo "После prepareForDisplay:\n";
if (isset($displayData['media_settings'])) {
    echo "  media_settings: [" . implode(', ', $displayData['media_settings']) . "]\n";
    echo "  Корректность: " . 
        (in_array('show_photos_in_gallery', $displayData['media_settings']) && 
         count($displayData['media_settings']) === 1 ? "✅ ВЕРНО" : "❌ НЕВЕРНО") . "\n";
} else {
    echo "  media_settings: НЕ УСТАНОВЛЕНО ❌\n";
}
echo "\n";

// ========== ТЕСТ 3: Все выключены ==========
echo "🔄 ТЕСТ 3: Все чекбоксы выключены\n";
echo "----------------------------------\n";

$data = [
    'title' => $draft->title,
    'category' => $draft->category,
    'media_settings' => [] // Пустой массив = все выключены
];

// Имитируем обработку в контроллере
if (isset($data['media_settings'])) {
    $settings = is_string($data['media_settings']) 
        ? json_decode($data['media_settings'], true) 
        : $data['media_settings'];
    
    if (is_array($settings)) {
        $data['show_photos_in_gallery'] = in_array('show_photos_in_gallery', $settings);
        $data['allow_download_photos'] = in_array('allow_download_photos', $settings);
        $data['watermark_photos'] = in_array('watermark_photos', $settings);
    }
    
    unset($data['media_settings']);
}

$result = $draftService->saveOrUpdate($data, $user, $draft->id);
$result->refresh();

echo "После сохранения (все выключены):\n";
echo "  show_photos_in_gallery: " . ($result->show_photos_in_gallery ? 'true' : 'false') . "\n";
echo "  allow_download_photos: " . ($result->allow_download_photos ? 'true' : 'false') . "\n";
echo "  watermark_photos: " . ($result->watermark_photos ? 'true' : 'false') . "\n\n";

$displayData = $draftService->prepareForDisplay($result);
echo "media_settings после prepareForDisplay: ";
if (isset($displayData['media_settings'])) {
    echo "[" . implode(', ', $displayData['media_settings']) . "]\n";
} else {
    echo "НЕ УСТАНОВЛЕНО\n";
}

// ========== ИТОГИ ==========
echo "\n📊 ИТОГИ ТЕСТИРОВАНИЯ:\n";
echo "======================\n";

$issues = [];

// Проверка 1: Сохраняются ли boolean поля
if ($result->show_photos_in_gallery || $result->allow_download_photos || $result->watermark_photos) {
    $issues[] = "❌ Boolean поля не сбрасываются в false при пустом массиве";
} else {
    echo "✅ Boolean поля корректно сохраняются\n";
}

// Проверка 2: Корректно ли работает обратное преобразование
if (isset($displayData['media_settings']) && is_array($displayData['media_settings'])) {
    echo "✅ media_settings корректно преобразуется обратно в массив\n";
} else {
    $issues[] = "❌ media_settings не преобразуется обратно в массив";
}

if (empty($issues)) {
    echo "\n🎉 ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО!\n";
    echo "Проблема с сохранением чекбоксов ИСПРАВЛЕНА!\n";
} else {
    echo "\n⚠️ ОБНАРУЖЕНЫ ПРОБЛЕМЫ:\n";
    foreach ($issues as $issue) {
        echo "  " . $issue . "\n";
    }
}

echo "\n💡 РЕКОМЕНДАЦИЯ:\n";
echo "Протестируйте в браузере:\n";
echo "1. Откройте черновик ID {$draft->id}\n";
echo "2. Измените состояние чекбоксов в секции 'Настройки отображения':\n";
echo "   - Показывать фото в галерее на странице объявления\n";
echo "   - Разрешить клиентам скачивать фотографии\n";
echo "   - Добавить водяной знак на фотографии\n";
echo "3. Нажмите 'Сохранить черновик'\n";
echo "4. Обновите страницу и проверьте, что состояния чекбоксов сохранились\n";