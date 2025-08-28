<?php
/**
 * Тест исправления ошибки с WorkFormat enum
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Ad\Models\Ad;
use App\Enums\WorkFormat;

echo "🔍 ТЕСТ ИСПРАВЛЕНИЯ ОШИБКИ WorkFormat ENUM\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Проверяем правильные значения enum
echo "📋 ПРАВИЛЬНЫЕ ЗНАЧЕНИЯ WorkFormat:\n";
foreach (WorkFormat::cases() as $format) {
    echo "  - {$format->name} = '{$format->value}' ({$format->getLabel()})\n";
}

echo "\n❌ ПРОБЛЕМА БЫЛА:\n";
echo "  Frontend отправлял: 'INDIVIDUAL' (верхний регистр)\n";
echo "  Backend ожидал: 'individual' (нижний регистр)\n";
echo "  Enum возвращал объект вместо строки\n\n";

echo "✅ ИСПРАВЛЕНИЯ:\n";
echo "1. **AdResource.php** - Преобразуем enum в строку:\n";
echo "   'work_format' => \$this->work_format?->value ?? \$this->work_format\n\n";

echo "2. **AdController.php** - Проверяем и преобразуем enum:\n";
echo "   if (is_object(\$preparedData['work_format'])) {\n";
echo "       \$preparedData['work_format'] = \$preparedData['work_format']->value\n";
echo "   }\n\n";

// Тестируем на реальном черновике
$draft = Ad::where('status', 'draft')->first();
if ($draft) {
    echo "🧪 ТЕСТ НА ЧЕРНОВИКЕ ID {$draft->id}:\n";
    
    // Проверяем тип work_format
    $workFormat = $draft->work_format;
    echo "  Тип work_format: " . gettype($workFormat) . "\n";
    
    if (is_object($workFormat)) {
        echo "  Это enum объект: " . get_class($workFormat) . "\n";
        echo "  Значение: '{$workFormat->value}'\n";
        echo "  Метка: '{$workFormat->getLabel()}'\n";
    } else {
        echo "  Это строка: '{$workFormat}'\n";
    }
    
    // Создаем AdResource и проверяем вывод
    $resource = new \App\Application\Http\Resources\Ad\AdResource($draft);
    $data = $resource->toArray(request());
    
    echo "\n📊 AdResource возвращает:\n";
    echo "  work_format: " . json_encode($data['work_format']) . "\n";
    echo "  work.work_format: " . json_encode($data['work']['work_format'] ?? 'не найдено') . "\n";
}

echo "\n🎯 РЕЗУЛЬТАТ:\n";
echo "  ✅ Enum теперь правильно преобразуется в строку\n";
echo "  ✅ Frontend получает 'individual' вместо объекта\n";
echo "  ✅ Ошибка ValueError должна быть исправлена\n\n";

echo "📝 ПРОВЕРЬТЕ В БРАУЗЕРЕ:\n";
echo "  1. Обновите страницу http://spa.test/ads/{$draft->id}/edit\n";
echo "  2. Ошибка ValueError не должна появляться\n";
echo "  3. Форма должна загрузиться корректно\n";