<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\DraftService;

echo "🎯 ТЕСТ СКОПИРОВАННОЙ ЛОГИКИ: MediaSection теперь как FeaturesSection\n";
echo "====================================================================\n\n";

$draft = Ad::where('status', 'draft')->first();
if (!$draft) {
    echo "❌ Черновик не найден\n";
    exit;
}

echo "📝 Тестируем черновик ID: {$draft->id}\n\n";

// Тест 1: Проверим что backend логика всё ещё работает
echo "🔄 ТЕСТ 1: Backend логика не сломана\n";
echo "------------------------------------\n";

$draftService = app(DraftService::class);
$user = $draft->user;

// Сохраним тестовые настройки
$data = [
    'title' => $draft->title,
    'category' => $draft->category,
    'media_settings' => ['show_photos_in_gallery', 'watermark_photos'] // Только 2 из 3
];

// Имитируем контроллер логику
if (isset($data['media_settings'])) {
    $settings = $data['media_settings'];
    $data['show_photos_in_gallery'] = in_array('show_photos_in_gallery', $settings);
    $data['allow_download_photos'] = in_array('allow_download_photos', $settings);
    $data['watermark_photos'] = in_array('watermark_photos', $settings);
    unset($data['media_settings']);
}

$result = $draftService->saveOrUpdate($data, $user, $draft->id);

echo "Сохранено в БД:\n";
echo "  show_photos_in_gallery: " . ($result->show_photos_in_gallery ? 'true' : 'false') . "\n";
echo "  allow_download_photos: " . ($result->allow_download_photos ? 'true' : 'false') . "\n";
echo "  watermark_photos: " . ($result->watermark_photos ? 'true' : 'false') . "\n\n";

// Тест 2: Проверим prepareForDisplay
echo "🔄 ТЕСТ 2: prepareForDisplay работает\n";
echo "------------------------------------\n";

$displayData = $draftService->prepareForDisplay($result);

if (isset($displayData['media_settings'])) {
    echo "media_settings: [" . implode(', ', $displayData['media_settings']) . "]\n";
    
    // Проверим корректность
    $expected = ['show_photos_in_gallery', 'watermark_photos'];
    $actual = $displayData['media_settings'];
    sort($expected);
    sort($actual);
    
    if ($expected == $actual) {
        echo "✅ Корректно! Ожидали: [show_photos_in_gallery, watermark_photos]\n";
    } else {
        echo "❌ Некорректно! Получили: [" . implode(', ', $actual) . "]\n";
    }
} else {
    echo "❌ media_settings НЕ СФОРМИРОВАНО\n";
}
echo "\n";

// Тест 3: Проверим что новая логика на фронтенде должна работать
echo "🔄 ТЕСТ 3: Симуляция новой логики фронтенда\n";
echo "--------------------------------------------\n";

echo "НОВАЯ ЛОГИКА (скопирована из FeaturesSection):\n";
echo "\n";

echo "1. allMediaSettings = [\n";
echo "   { id: 'show_photos_in_gallery', label: '...' },\n";  
echo "   { id: 'allow_download_photos', label: '...' },\n";
echo "   { id: 'watermark_photos', label: '...' }\n";
echo "]\n\n";

echo "2. localMediaSettings = ref([...props.mediaSettings])\n";
echo "   Инициализация: " . json_encode($displayData['media_settings']) . "\n\n";

echo "3. isMediaSettingSelected(settingId) {\n";
echo "   return localMediaSettings.value.includes(settingId)\n";
echo "}\n\n";

// Имитируем логику
$localMediaSettings = $displayData['media_settings'];

echo "Проверки:\n";
echo "  isMediaSettingSelected('show_photos_in_gallery'): " . 
      (in_array('show_photos_in_gallery', $localMediaSettings) ? 'true' : 'false') . "\n";
echo "  isMediaSettingSelected('allow_download_photos'): " . 
      (in_array('allow_download_photos', $localMediaSettings) ? 'true' : 'false') . "\n";
echo "  isMediaSettingSelected('watermark_photos'): " . 
      (in_array('watermark_photos', $localMediaSettings) ? 'true' : 'false') . "\n\n";

echo "4. toggleMediaSetting('allow_download_photos') - включаем:\n";
if (!in_array('allow_download_photos', $localMediaSettings)) {
    $localMediaSettings[] = 'allow_download_photos';
}
echo "   Результат: " . json_encode($localMediaSettings) . "\n\n";

echo "5. emit('update:media-settings', [...localMediaSettings])\n";
echo "   Отправится: " . json_encode($localMediaSettings) . "\n\n";

// Итоговая проверка
echo "🎯 ВЫВОДЫ:\n";
echo "==========\n";

$success = (
    $result->show_photos_in_gallery === true &&
    $result->allow_download_photos === false && 
    $result->watermark_photos === true &&
    isset($displayData['media_settings']) &&
    is_array($displayData['media_settings']) &&
    count($displayData['media_settings']) === 2
);

if ($success) {
    echo "✅ СКОПИРОВАННАЯ ЛОГИКА ДОЛЖНА РАБОТАТЬ!\n";
    echo "✅ Backend логика не сломана\n";
    echo "✅ prepareForDisplay корректно формирует массив\n";
    echo "✅ Новая логика фронтенда (как в FeaturesSection) получает правильные данные\n";
    echo "✅ Чекбоксы должны теперь корректно работать\n";
} else {
    echo "❌ Есть проблемы в логике\n";
}

echo "\n💡 ПРОТЕСТИРУЙТЕ В БРАУЗЕРЕ:\n";
echo "1. URL: http://spa.test/ads/{$draft->id}/edit\n";
echo "2. Секция 'Настройки отображения' - чекбоксы теперь как в 'Особенности'\n";
echo "3. Кликните чекбоксы - они должны toggleMediaSetting()\n";
echo "4. Сохраните черновик - должно emit('update:media-settings')\n";
echo "5. Обновите страницу - состояния должны восстановиться\n";