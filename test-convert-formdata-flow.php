<?php

use App\Application\Http\Controllers\Ad\DraftController;
use App\Domain\User\Models\User;
use Illuminate\Http\Request;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🎯 СИМУЛЯЦИЯ ПОЛНОГО ПОТОКА: FormData → convertFormDataToPlainObject → Controller\n";
echo "=========================================================================\n\n";

/**
 * Копия функции convertFormDataToPlainObject из useAdFormSubmission.ts
 * для симуляции того, что происходит во frontend
 */
function convertFormDataToPlainObject($formDataArray): array {
    $plainData = [];
    
    foreach ($formDataArray as $key => $value) {
        // Обработка массивов (photos[0], photos[1])
        if (strpos($key, '[') !== false) {
            if (preg_match('/^(.+?)\[(\d+)\]$/', $key, $matches)) {
                $fieldName = $matches[1];
                $index = (int)$matches[2];
                if (!isset($plainData[$fieldName])) {
                    $plainData[$fieldName] = [];
                }
                $plainData[$fieldName][$index] = $value;
            }
        } else {
            // Парсинг JSON строк (как в оригинале)
            if (is_string($value) && (strpos($value, '{') === 0 || strpos($value, '[') === 0)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $plainData[$key] = $decoded;
                    echo "🔄 convertFormDataToPlainObject: $key декодирован из JSON строки в " . gettype($decoded) . "\n";
                } else {
                    $plainData[$key] = $value;
                }
            } else {
                $plainData[$key] = $value;
            }
        }
    }
    
    return $plainData;
}

try {
    // Получаем пользователя
    $user = User::first();
    if (!$user) {
        echo "❌ Нет пользователей в БД\n";
        exit;
    }

    echo "✅ Пользователь найден: {$user->email}\n\n";

    // ШАГ 1: Данные как они приходят из FormData
    echo "📋 ШАГ 1: Исходные данные из FormData\n";
    $formDataArray = [
        'status' => 'draft',
        'specialty' => 'массаж',
        'work_format' => 'individual',
        'category' => 'relax',
        
        // JSON поля как строки (из FormData)
        'prices' => '[]',
        'services' => '{"hygiene_amenities":{"shower_before":{"enabled":false,"price_comment":""}}}',
        'clients' => '["men"]',
        'service_provider' => '["women"]',
        'features' => '[]',
        'schedule' => '[]',
        'geo' => '[]',
    ];
    
    foreach (['services', 'clients', 'prices', 'geo'] as $field) {
        $value = $formDataArray[$field];
        echo "  $field: " . gettype($value) . " = '$value'\n";
    }

    // ШАГ 2: Применяем convertFormDataToPlainObject (симуляция frontend)
    echo "\n📋 ШАГ 2: После convertFormDataToPlainObject\n";
    $convertedData = convertFormDataToPlainObject($formDataArray);
    
    foreach (['services', 'clients', 'prices', 'geo'] as $field) {
        $value = $convertedData[$field];
        echo "  $field: " . gettype($value);
        if (is_array($value)) {
            echo " с " . count($value) . " элементами";
        } else {
            echo " = '$value'";
        }
        echo "\n";
    }

    // ШАГ 3: Создаем Request и проверяем типы
    echo "\n📋 ШАГ 3: После создания Laravel Request\n";
    $request = new Request($convertedData);
    
    foreach (['services', 'clients', 'prices', 'geo'] as $field) {
        $value = $request->input($field);
        echo "  $field: " . gettype($value);
        if (is_array($value)) {
            echo " с " . count($value) . " элементами";
        } else {
            echo " = '$value'";
        }
        echo "\n";
    }
    
    // ШАГ 4: Авторизуем пользователя и вызываем контроллер
    auth()->login($user);
    
    echo "\n🔧 ШАГ 4: Вызываем контроллер...\n";
    $controller = app(DraftController::class);
    $response = $controller->store($request);

    echo "✅ Контроллер выполнен успешно!\n";
    echo "📋 Тип ответа: " . get_class($response) . "\n";

} catch (\Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Тип: " . get_class($e) . "\n";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    echo "\n🔍 Трассировка ошибки:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n📋 Проверьте логи Laravel для отладочных данных\n";