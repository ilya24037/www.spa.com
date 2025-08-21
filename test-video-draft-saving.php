<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Domain\Ad\Models\Ad;

// Загружаем приложение Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "🎬 ТЕСТ СОХРАНЕНИЯ ВИДЕО В ЧЕРНОВИКЕ\n\n";

// Проверяем объявление ID 52
$ad = Ad::find(52);

if (!$ad) {
    echo "❌ Объявление с ID 52 не найдено\n";
    exit(1);
}

echo "📋 ТЕКУЩЕЕ СОСТОЯНИЕ ОБЪЯВЛЕНИЯ ID 52:\n";
echo "Статус: " . $ad->status->value . "\n";
echo "Заголовок: " . $ad->title . "\n";

// Проверяем поле video
echo "\n🎬 ПОЛЕ VIDEO:\n";
echo "Raw значение: " . ($ad->video ?: 'NULL') . "\n";
echo "Тип: " . gettype($ad->video) . "\n";

if ($ad->video) {
    $decoded = json_decode($ad->video, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "Декодированное:\n";
        if (is_array($decoded)) {
            echo "  Количество видео: " . count($decoded) . "\n";
            foreach ($decoded as $index => $video) {
                echo "  [{$index}] " . (is_string($video) ? $video : json_encode($video)) . "\n";
            }
        } else {
            echo "  Неожиданный формат: " . gettype($decoded) . "\n";
        }
    } else {
        echo "  Ошибка декодирования JSON: " . json_last_error_msg() . "\n";
    }
}

// Проверяем поле photos для сравнения  
echo "\n📸 ПОЛЕ PHOTOS (для сравнения):\n";
echo "Raw значение: " . ($ad->photos ?: 'NULL') . "\n";

if ($ad->photos) {
    $decoded = json_decode($ad->photos, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        echo "  Количество фото: " . count($decoded) . "\n";
    }
}

echo "\n✅ ПРОВЕРКА ЗАВЕРШЕНА\n";
echo "\n📝 ИНСТРУКЦИЯ ДЛЯ ТЕСТИРОВАНИЯ:\n";
echo "1. Откройте http://spa.test/ads/52/edit\n";
echo "2. Добавьте любое видео в секцию 'Видео'\n";
echo "3. Нажмите 'Сохранить черновик'\n";
echo "4. Проверьте логи браузера (должны появиться логи с '🎬 adFormModel')\n";
echo "5. Проверьте логи Laravel (должны появиться логи с '🎬 DraftController')\n";
echo "6. Запустите этот скрипт еще раз, чтобы увидеть результат\n";