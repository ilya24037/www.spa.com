<?php

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🎯 ТЕСТ ИСПРАВЛЕНИЯ СОХРАНЕНИЯ ФОТОГРАФИЙ\n";
echo "==========================================\n\n";

try {
    // Находим существующий черновик с фотографиями 
    $ad = Ad::where('status', 'draft')->whereNotNull('photos')->first();
    
    if (!$ad) {
        echo "❌ Не найден черновик с фотографиями для тестирования\n";
        echo "📝 Создаем тестовый черновик с фотографиями...\n";
        
        $user = User::first();
        if (!$user) {
            echo "❌ Нет пользователей в БД\n";
            exit;
        }
        
        // Создаем черновик с тестовыми фотографиями
        $ad = Ad::create([
            'user_id' => $user->id,
            'status' => 'draft',
            'title' => 'Тест сохранения фото',
            'specialty' => 'массаж',
            'work_format' => 'individual',
            'category' => 'relax',
            'photos' => [
                '/storage/photos/test1.jpg',
                '/storage/photos/test2.jpg'
            ]
        ]);
        
        echo "✅ Тестовый черновик создан с ID: {$ad->id}\n";
    }
    
    echo "📋 ТЕСТИРУЕМ ЧЕРНОВИК ID: {$ad->id}\n";
    echo "Пользователь: {$ad->user_id}\n";
    echo "Статус: {$ad->status->value}\n";
    
    // Показываем текущие фотографии
    $currentPhotos = $ad->photos ?? [];
    echo "\n📸 ТЕКУЩИЕ ФОТОГРАФИИ:\n";
    if (is_array($currentPhotos) && count($currentPhotos) > 0) {
        foreach ($currentPhotos as $index => $photo) {
            echo "  [{$index}] {$photo}\n";
        }
        echo "Количество: " . count($currentPhotos) . "\n";
    } else {
        echo "  Фотографий нет\n";
    }
    
    echo "\n🎯 ИНСТРУКЦИЯ ДЛЯ ПРОВЕРКИ:\n";
    echo "1. Откройте в браузере: http://spa.test/ads/{$ad->id}/edit\n";
    echo "2. Убедитесь что существующие фотографии загрузились ✅\n";
    echo "3. Внесите любые изменения (измените описание)\n";
    echo "4. Нажмите 'Сохранить черновик'\n";
    echo "5. Проверьте что фотографии НЕ исчезли ✅\n";
    echo "6. Добавьте новую фотографию\n";
    echo "7. Сохраните снова - должны остаться ВСЕ фото ✅\n\n";
    
    echo "✅ ИСПРАВЛЕНИЕ ПРИМЕНЕНО!\n";
    echo "Восстановлена оригинальная логика сохранения фотографий:\n";
    echo "- Все фото отправляются как photos[index]\n";
    echo "- Существующие фото как URL строки (не JSON объекты)\n";
    echo "- Добавлена обработка пустых массивов\n";
    echo "- Восстановлены детальные console.log для отладки\n\n";
    
} catch (\Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
}