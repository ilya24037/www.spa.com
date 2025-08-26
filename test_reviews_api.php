<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domain\Review\Services\ReviewService;
use App\Domain\Review\DTOs\CreateReviewDTO;
use App\Domain\User\Models\User;

echo "🔍 ТЕСТИРОВАНИЕ СИСТЕМЫ ОТЗЫВОВ\n";
echo "================================\n\n";

try {
    $reviewService = app(ReviewService::class);
    
    // 1. Проверяем существующие отзывы
    echo "📋 Существующие отзывы:\n";
    $reviews = DB::table('reviews')->select('id', 'reviewer_id', 'rating', 'comment')->limit(5)->get();
    foreach($reviews as $review) {
        echo "  ID: {$review->id}, User: {$review->reviewer_id}, Rating: {$review->rating}\n";
        if ($review->comment) {
            echo "  Comment: " . substr($review->comment, 0, 50) . "...\n";
        }
    }
    echo "\n";
    
    // 2. Проверяем пользователей
    echo "👥 Пользователи для тестирования:\n";
    $users = User::limit(3)->get(['id', 'name', 'email']);
    foreach($users as $user) {
        echo "  ID: {$user->id}, Name: {$user->name}, Email: {$user->email}\n";
    }
    echo "\n";
    
    // 3. Проверяем статистику
    if ($users->count() > 0) {
        $userId = $users->first()->id;
        echo "📊 Статистика отзывов для пользователя ID {$userId}:\n";
        try {
            $stats = $reviewService->getUserReviewStats($userId);
            echo "  Всего отзывов: {$stats['total_count']}\n";
            echo "  Средний рейтинг: {$stats['average_rating']}\n";
            echo "  Распределение рейтингов:\n";
            foreach($stats['rating_distribution'] as $rating => $count) {
                echo "    {$rating} звезд: {$count}\n";
            }
        } catch (\Exception $e) {
            echo "  ❌ Ошибка получения статистики: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
    
    // 4. Проверяем API эндпоинты
    echo "🌐 API эндпоинты отзывов:\n";
    $routes = [
        'GET /api/reviews',
        'GET /api/reviews/{id}',
        'POST /api/reviews',
        'PUT /api/reviews/{id}',
        'DELETE /api/reviews/{id}'
    ];
    
    foreach($routes as $route) {
        $parts = explode(' ', $route);
        $method = $parts[0];
        $uri = $parts[1];
        
        $routeExists = false;
        foreach(Route::getRoutes() as $r) {
            if (in_array($method, $r->methods()) && strpos($r->uri(), trim($uri, '/')) !== false) {
                $routeExists = true;
                break;
            }
        }
        
        echo "  {$route}: " . ($routeExists ? "✅ Существует" : "❌ Не найден") . "\n";
    }
    echo "\n";
    
    // 5. Проверяем наличие компонентов
    echo "🎨 Vue компоненты:\n";
    $components = [
        'resources/js/src/widgets/profile-dashboard/tabs/ReviewsTab.vue',
        'resources/js/src/features/review-management/ui/ReviewList/ReviewList.vue',
        'resources/js/src/entities/review/ui/ReviewCard/ReviewCard.vue'
    ];
    
    foreach($components as $component) {
        $exists = file_exists($component);
        echo "  " . basename($component) . ": " . ($exists ? "✅ Существует" : "❌ Не найден") . "\n";
    }
    
    echo "\n✅ ТЕСТИРОВАНИЕ ЗАВЕРШЕНО!\n";
    
} catch (\Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Трейс: " . $e->getTraceAsString() . "\n";
}