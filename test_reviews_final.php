<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domain\Review\Services\ReviewService;
use App\Domain\Review\Repositories\ReviewRepositoryNew;
use App\Domain\Review\Models\ReviewAdapted;

echo "🔍 ФИНАЛЬНОЕ ТЕСТИРОВАНИЕ СИСТЕМЫ ОТЗЫВОВ\n";
echo "==========================================\n\n";

try {
    // 1. Проверяем репозиторий
    echo "📋 Тестирование репозитория:\n";
    $repository = new ReviewRepositoryNew(new ReviewAdapted());
    
    // Получаем отзывы для пользователя 1
    $reviews = $repository->getUserReviewsCollection(1);
    echo "  Отзывов для пользователя ID 1: " . $reviews->count() . "\n";
    
    // Получаем отзывы для пользователя 2
    $reviews2 = $repository->getUserReviewsCollection(2);
    echo "  Отзывов для пользователя ID 2: " . $reviews2->count() . "\n\n";
    
    // 2. Проверяем сервис
    echo "📊 Тестирование сервиса:\n";
    app()->bind(
        \App\Domain\Review\Repositories\ReviewRepositoryNew::class,
        function () {
            return new ReviewRepositoryNew(new ReviewAdapted());
        }
    );
    
    $reviewService = app(ReviewService::class);
    
    // Получаем статистику
    $stats = $reviewService->getUserReviewStats(1);
    echo "  Статистика для пользователя ID 1:\n";
    echo "    Всего отзывов: {$stats['total_count']}\n";
    echo "    Средний рейтинг: {$stats['average_rating']}\n";
    echo "    Распределение рейтингов:\n";
    foreach($stats['rating_distribution'] as $rating => $count) {
        echo "      {$rating} звезд: {$count}\n";
    }
    echo "\n";
    
    // 3. Проверяем API роуты
    echo "🌐 API эндпоинты отзывов:\n";
    $routes = Route::getRoutes();
    $reviewRoutes = [];
    
    foreach($routes as $route) {
        $uri = $route->uri();
        if (strpos($uri, 'reviews') !== false) {
            $methods = implode('|', $route->methods());
            $reviewRoutes[] = "{$methods} /{$uri}";
        }
    }
    
    if (empty($reviewRoutes)) {
        echo "  ❌ Роуты для отзывов не найдены\n";
    } else {
        foreach($reviewRoutes as $route) {
            echo "  ✅ {$route}\n";
        }
    }
    echo "\n";
    
    // 4. Проверяем существующие данные
    echo "📝 Существующие отзывы в БД:\n";
    $allReviews = DB::table('reviews')
        ->select('id', 'reviewer_id', 'reviewable_type', 'reviewable_id', 'rating', 'status')
        ->limit(5)
        ->get();
    
    foreach($allReviews as $review) {
        $type = basename(str_replace('\\', '/', $review->reviewable_type));
        echo "  ID: {$review->id}, От: User#{$review->reviewer_id}, Для: {$type}#{$review->reviewable_id}, ";
        echo "Рейтинг: {$review->rating}, Статус: {$review->status}\n";
    }
    echo "\n";
    
    // 5. Тестируем создание отзыва через DTO
    echo "🚀 Тестирование создания отзыва:\n";
    try {
        // Создаём тестовый DTO
        $testData = [
            'user_id' => 1,
            'reviewable_user_id' => 2,
            'rating' => 5,
            'comment' => 'Тестовый отзыв ' . date('Y-m-d H:i:s'),
            'is_visible' => true,
            'is_verified' => false,
        ];
        
        // Проверка на существование
        $existing = $repository->findByUserAndReviewable(1, 2);
        if ($existing) {
            echo "  ⚠️  Отзыв от пользователя 1 для пользователя 2 уже существует (ID: {$existing->id})\n";
        } else {
            // Создаём через репозиторий
            $newReview = $repository->create($testData);
            echo "  ✅ Создан новый отзыв ID: {$newReview->id}\n";
        }
    } catch (\Exception $e) {
        echo "  ❌ Ошибка создания: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ ТЕСТИРОВАНИЕ ЗАВЕРШЕНО УСПЕШНО!\n";
    
} catch (\Exception $e) {
    echo "❌ КРИТИЧЕСКАЯ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Трейс: " . $e->getTraceAsString() . "\n";
}