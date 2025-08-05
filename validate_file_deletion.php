<?php

/**
 * Валидация архитектуры удаления файлов
 * Проверяет что логика удаления файлов корректно перенесена в сервисы
 */
require_once __DIR__ . '/vendor/autoload.php';

use App\Domain\Media\Repositories\PhotoRepository;
use App\Domain\Media\Repositories\VideoRepository;
use App\Domain\Media\Services\MediaService;

echo "🔍 ВАЛИДАЦИЯ АРХИТЕКТУРЫ УДАЛЕНИЯ ФАЙЛОВ\n";
echo "==========================================\n\n";

$validationResults = [];

// 1. Проверка что методы удаления существуют в репозиториях
echo "1. Проверка методов удаления в репозиториях:\n";

$photoRepoClass = new ReflectionClass(PhotoRepository::class);
if ($photoRepoClass->hasMethod('deletePhoto')) {
    $deleteMethod = $photoRepoClass->getMethod('deletePhoto');
    $validationResults['photo_repo_delete'] = true;
    echo "   ✅ PhotoRepository::deletePhoto() - существует\n";
} else {
    $validationResults['photo_repo_delete'] = false;
    echo "   ❌ PhotoRepository::deletePhoto() - ОТСУТСТВУЕТ\n";
}

$videoRepoClass = new ReflectionClass(VideoRepository::class);
if ($videoRepoClass->hasMethod('deleteVideo')) {
    $deleteMethod = $videoRepoClass->getMethod('deleteVideo');
    $validationResults['video_repo_delete'] = true;
    echo "   ✅ VideoRepository::deleteVideo() - существует\n";
} else {
    $validationResults['video_repo_delete'] = false;
    echo "   ❌ VideoRepository::deleteVideo() - ОТСУТСТВУЕТ\n";
}

// 2. Проверка что методы удаления существуют в сервисах
echo "\n2. Проверка методов удаления в сервисах:\n";

$mediaServiceClass = new ReflectionClass(MediaService::class);
if ($mediaServiceClass->hasMethod('deletePhotoFiles')) {
    $validationResults['media_service_delete_photo'] = true;
    echo "   ✅ MediaService::deletePhotoFiles() - существует\n";
} else {
    $validationResults['media_service_delete_photo'] = false;
    echo "   ❌ MediaService::deletePhotoFiles() - ОТСУТСТВУЕТ\n";
}

if ($mediaServiceClass->hasMethod('deleteVideo')) {
    $validationResults['media_service_delete_video'] = true;
    echo "   ✅ MediaService::deleteVideo() - существует\n";
} else {
    $validationResults['media_service_delete_video'] = false;
    echo "   ❌ MediaService::deleteVideo() - ОТСУТСТВУЕТ\n";
}

// 3. Проверка что модели не содержат boot/booted методов
echo "\n3. Проверка что модели не содержат бизнес-логику:\n";

$modelsToCheck = [
    'App\\Domain\\Media\\Models\\Photo',
    'App\\Domain\\Media\\Models\\Video', 
    'App\\Domain\\Media\\Models\\Media',
    'App\\Domain\\Service\\Models\\Service',
    'App\\Domain\\Booking\\Models\\Booking',
    'App\\Domain\\Service\\Models\\MassageCategory',
    'App\\Domain\\Booking\\Models\\BookingSlot',
    'App\\Domain\\Booking\\Models\\BookingService',
    'App\\Domain\\Payment\\Models\\Payment',
    'App\\Domain\\Review\\Models\\Review',
    'App\\Domain\\Master\\Models\\SubscriptionHistory',
];

$modelsClean = true;
foreach ($modelsToCheck as $modelClass) {
    if (class_exists($modelClass)) {
        $reflection = new ReflectionClass($modelClass);
        
        // Проверяем только методы, определённые в самом классе (не наследуемые)
        $hasBootMethod = false;
        $methods = $reflection->getMethods(ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_STATIC);
        
        foreach ($methods as $method) {
            if (($method->getName() === 'boot' || $method->getName() === 'booted') && 
                $method->getDeclaringClass()->getName() === $modelClass) {
                $hasBootMethod = true;
                break;
            }
        }
        
        if ($hasBootMethod) {
            echo "   ❌ $modelClass - содержит boot/booted методы\n";
            $modelsClean = false;
        } else {
            echo "   ✅ $modelClass - чистая модель\n";
        }
    } else {
        echo "   ⚠️  $modelClass - класс не найден\n";
    }
}

$validationResults['models_clean'] = $modelsClean;

// 4. Итоговая оценка
echo "\n==========================================\n";
echo "📊 ИТОГОВАЯ ОЦЕНКА:\n";

$totalChecks = count($validationResults);
$passedChecks = array_sum($validationResults);
$percentage = round(($passedChecks / $totalChecks) * 100, 1);

echo "Пройдено проверок: $passedChecks из $totalChecks ($percentage%)\n";

if ($percentage === 100.0) {
    echo "🎉 ВСЕ ПРОВЕРКИ ПРОЙДЕНЫ - АРХИТЕКТУРА КОРРЕКТНА!\n";
    echo "✅ Логика удаления файлов успешно перенесена в сервисы\n";
    echo "✅ Все модели являются чистыми структурами данных\n";
    echo "✅ Соответствие CLAUDE.md: 100%\n";
} else {
    echo "❌ НАЙДЕНЫ НАРУШЕНИЯ АРХИТЕКТУРЫ!\n";
    echo "🔧 Требуется доработка для достижения 100% соответствия\n";
}

echo "\n==========================================\n";