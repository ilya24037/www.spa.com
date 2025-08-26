<?php

use App\Domain\Ad\Models\Ad;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "🎯 ТЕСТ СОХРАНЕНИЯ ВИДЕО С ПОДЧЁРКИВАНИЯМИ\n";
echo "==========================================\n\n";

// Авторизуемся как первый пользователь
$user = \App\Domain\User\Models\User::find(1);
if (!$user) {
    echo "❌ Пользователь не найден!\n";
    exit;
}
Auth::login($user);

// Находим черновик ID 70
$draft = Ad::find(70);
if (!$draft) {
    echo "❌ Черновик ID 70 не найден!\n";
    exit;
}

echo "📋 Черновик найден: ID {$draft->id}, Title: {$draft->title}\n";
echo "📹 Текущие видео: " . json_encode($draft->video) . "\n\n";

// Создаем тестовый видео файл
$videoContent = "Test video content";
$tempFile = tempnam(sys_get_temp_dir(), 'video');
file_put_contents($tempFile, $videoContent);

// Создаем UploadedFile
$uploadedFile = new UploadedFile(
    $tempFile,
    'test_video.mp4',
    'video/mp4',
    null,
    true // test mode
);

// Создаем запрос с новым форматом имён
$requestData = [
    'title' => $draft->title,
    'category' => $draft->category,
    'video_0_file' => $uploadedFile, // Используем подчёркивания!
];

echo "📤 Отправляем запрос с video_0_file...\n";

// Создаем новый Request
$updateRequest = \Illuminate\Http\Request::create(
    "/draft/{$draft->id}",
    'PUT',
    $requestData,
    [],
    ['video_0_file' => $uploadedFile],
    ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
);

// Устанавливаем авторизацию
$updateRequest->setUserResolver(function() use ($user) {
    return $user;
});

// Получаем контроллер из DI контейнера
$controller = app(\App\Application\Http\Controllers\Ad\DraftController::class);

try {
    $response = $controller->update($updateRequest, $draft->id);
    
    echo "✅ Запрос обработан успешно\n\n";
    
    // Перезагружаем черновик из БД
    $draft->refresh();
    
    echo "📹 РЕЗУЛЬТАТ:\n";
    echo "  Видео после сохранения: " . json_encode($draft->video) . "\n";
    echo "  Тип: " . gettype($draft->video) . "\n";
    
    if (is_array($draft->video) && count($draft->video) > 0) {
        echo "\n🎉 УСПЕХ! Видео сохранилось в БД!\n";
        echo "  Количество видео: " . count($draft->video) . "\n";
        foreach ($draft->video as $index => $video) {
            echo "  Видео {$index}: {$video}\n";
        }
    } else {
        echo "\n❌ ПРОБЛЕМА: Видео НЕ сохранилось в БД\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . "\n";
    echo "Строка: " . $e->getLine() . "\n";
}

// Очистка
@unlink($tempFile);

echo "\n📊 ПРОВЕРКА ЛОГОВ:\n";
echo "Проверьте storage/logs/laravel.log для детальной информации\n";
echo "Ищите строки с '🎥' для отслеживания обработки видео\n";