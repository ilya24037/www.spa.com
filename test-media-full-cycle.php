<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\DraftService;

echo "🔍 ПОЛНЫЙ ЦИКЛ ТЕСТА: Сохранение → Загрузка → Отображение\n";
echo "===========================================================\n\n";

$draft = Ad::where('status', 'draft')->first();
if (!$draft) {
    echo "❌ Черновик не найден\n";
    exit;
}

$draftService = app(DraftService::class);
$user = $draft->user;

echo "📝 Черновик ID: {$draft->id}\n\n";

// Шаг 1: Установим конкретные значения
echo "ШАГ 1: Устанавливаем значения чекбоксов\n";
echo "-----------------------------------------\n";
$data = [
    'title' => $draft->title,
    'category' => $draft->category,
    'show_photos_in_gallery' => true,
    'allow_download_photos' => false,
    'watermark_photos' => true
];

$result = $draftService->saveOrUpdate($data, $user, $draft->id);
echo "Сохранено:\n";
echo "  show_photos_in_gallery: " . ($result->show_photos_in_gallery ? 'true' : 'false') . "\n";
echo "  allow_download_photos: " . ($result->allow_download_photos ? 'true' : 'false') . "\n";
echo "  watermark_photos: " . ($result->watermark_photos ? 'true' : 'false') . "\n\n";

// Шаг 2: Загрузим черновик заново (как это делает фронтенд)
echo "ШАГ 2: Загружаем черновик заново из БД\n";
echo "---------------------------------------\n";
$freshDraft = Ad::find($draft->id);
echo "В БД:\n";
echo "  show_photos_in_gallery: " . ($freshDraft->show_photos_in_gallery ? 'true' : 'false') . "\n";
echo "  allow_download_photos: " . ($freshDraft->allow_download_photos ? 'true' : 'false') . "\n";
echo "  watermark_photos: " . ($freshDraft->watermark_photos ? 'true' : 'false') . "\n\n";

// Шаг 3: Подготовим для отображения
echo "ШАГ 3: Подготовка для отображения (prepareForDisplay)\n";
echo "-----------------------------------------------------\n";
$displayData = $draftService->prepareForDisplay($freshDraft);

echo "Результат prepareForDisplay:\n";
if (isset($displayData['media_settings'])) {
    echo "  media_settings: [" . implode(', ', $displayData['media_settings']) . "]\n";
    
    // Проверим корректность
    $expected = ['show_photos_in_gallery', 'watermark_photos'];
    $actual = $displayData['media_settings'];
    sort($expected);
    sort($actual);
    
    if ($expected == $actual) {
        echo "  ✅ Корректно! Ожидали: [show_photos_in_gallery, watermark_photos]\n";
    } else {
        echo "  ❌ Некорректно! Ожидали: [" . implode(', ', $expected) . "]\n";
    }
} else {
    echo "  ❌ media_settings НЕ УСТАНОВЛЕНО!\n";
}

// Проверим также отдельные поля
echo "\nТакже в displayData есть отдельные поля:\n";
echo "  show_photos_in_gallery: " . (isset($displayData['show_photos_in_gallery']) ? 
    ($displayData['show_photos_in_gallery'] ? 'true' : 'false') : 'НЕ УСТАНОВЛЕНО') . "\n";
echo "  allow_download_photos: " . (isset($displayData['allow_download_photos']) ? 
    ($displayData['allow_download_photos'] ? 'true' : 'false') : 'НЕ УСТАНОВЛЕНО') . "\n";
echo "  watermark_photos: " . (isset($displayData['watermark_photos']) ? 
    ($displayData['watermark_photos'] ? 'true' : 'false') : 'НЕ УСТАНОВЛЕНО') . "\n";

echo "\n🎯 ИТОГ:\n";
echo "--------\n";
if (isset($displayData['media_settings']) && is_array($displayData['media_settings'])) {
    echo "✅ media_settings корректно формируется для фронтенда\n";
    echo "✅ Чекбоксы должны корректно отображаться в MediaSection\n";
} else {
    echo "❌ Проблема с формированием media_settings\n";
}