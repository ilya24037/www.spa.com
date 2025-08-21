<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\DraftService;

// Загружаем приложение Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "🎬 ТЕСТ ИСПРАВЛЕНИЯ ОТОБРАЖЕНИЯ ВИДЕО\n\n";

// Создаем сервис для тестирования
$draftService = new DraftService();

// Получаем объявление из БД
$ad = Ad::find(52);

if (!$ad) {
    echo "❌ Объявление с ID 52 не найдено\n";
    exit(1);
}

echo "📋 СЫРЫЕ ДАННЫЕ ИЗ БД:\n";
echo "Video field: " . $ad->video . "\n\n";

echo "🔧 ОБРАБОТКА ЧЕРЕЗ prepareForDisplay():\n";
$displayData = $draftService->prepareForDisplay($ad);

echo "📊 РЕЗУЛЬТАТ ОБРАБОТКИ:\n";
echo "video тип: " . gettype($displayData['video']) . "\n";
echo "video количество: " . (is_array($displayData['video']) ? count($displayData['video']) : 0) . "\n\n";

if (is_array($displayData['video']) && count($displayData['video']) > 0) {
    echo "📹 ПЕРВОЕ ВИДЕО:\n";
    $firstVideo = $displayData['video'][0];
    echo "  - Тип: " . gettype($firstVideo) . "\n";
    
    if (is_array($firstVideo)) {
        foreach ($firstVideo as $key => $value) {
            echo "  - {$key}: " . (is_string($value) ? $value : json_encode($value)) . "\n";
        }
    }
    
    echo "\n✅ УСПЕХ! Видео преобразовано в объект Video\n";
    echo "\n🎯 Теперь VideoUpload компонент должен отображать видео!\n";
    echo "\nURL для тестирования: http://spa.test/ads/52/edit\n";
} else {
    echo "❌ Видео не найдено или не обработано\n";
}

echo "\n📝 ЧТО ДОЛЖНО ПРОИЗОЙТИ В ИНТЕРФЕЙСЕ:\n";
echo "1. Откройте http://spa.test/ads/52/edit\n";
echo "2. В секции 'Видео' должно появиться сохраненное видео\n";
echo "3. Видео должно иметь мини-плеер с возможностью воспроизведения\n";
echo "4. Имя файла должно быть: 68a5bf006d165_1755692800.webm\n";
echo "5. Должна быть кнопка удаления\n";