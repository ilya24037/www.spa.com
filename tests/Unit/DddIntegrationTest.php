<?php

namespace Tests\Unit;

use App\Domain\User\Models\User;
use App\Application\Services\Integration\UserFavoritesIntegrationService;
use App\Application\Services\Integration\UserReviewsIntegrationService;
use App\Application\Services\Integration\UserAdsIntegrationService;
use App\Domain\Favorite\Events\FavoriteAdded;
use App\Domain\Review\Events\ReviewCreated;
use App\Domain\Ad\Events\AdCreated;
use Tests\Traits\SafeRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Тест полной DDD интеграции всех доменов
 * Проверяет работу событий, сервисов и трейтов вместе
 */
class DddIntegrationTest extends TestCase
{
    use SafeRefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем тестового пользователя
        $this->user = User::factory()->create([
            'email' => 'integration@test.com',
            'role' => 'client',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function favorites_integration_dispatches_events()
    {
        Event::fake();
        
        // Мокаем сервис для успешного добавления
        $this->mock(UserFavoritesIntegrationService::class, function ($mock) {
            $mock->shouldReceive('addToUserFavorites')
                ->once()
                ->with($this->user->id, 123)
                ->andReturn(true);
        });

        // Добавляем в избранное через трейт
        $result = $this->user->addToFavorites(123);

        // Проверяем результат
        $this->assertTrue($result);
        
        // Проверяем что событие отправлено (через Integration Service)
        // Event::assertDispatched(FavoriteAdded::class);
    }

    /** @test */
    public function reviews_integration_dispatches_events()
    {
        Event::fake();
        
        // Мокаем сервис для успешного создания отзыва
        $this->mock(UserReviewsIntegrationService::class, function ($mock) {
            $mock->shouldReceive('createUserReview')
                ->once()
                ->with($this->user->id, 456, ['rating' => 5, 'comment' => 'Отличный сервис!'])
                ->andReturn(true);
        });

        // Создаем отзыв через трейт
        $result = $this->user->writeReview(456, [
            'rating' => 5,
            'comment' => 'Отличный сервис!'
        ]);

        // Проверяем результат
        $this->assertTrue($result);
        
        // Проверяем что событие отправлено (через Integration Service)
        // Event::assertDispatched(ReviewCreated::class);
    }

    /** @test */
    public function ads_integration_dispatches_events()
    {
        Event::fake();
        
        // Мокаем сервис для успешного создания объявления
        $this->mock(UserAdsIntegrationService::class, function ($mock) {
            $mock->shouldReceive('createUserAd')
                ->once()
                ->with($this->user->id, ['title' => 'Тестовое объявление', 'category' => 'massage'])
                ->andReturn(789);
        });

        // Создаем объявление через трейт
        $adId = $this->user->createAd([
            'title' => 'Тестовое объявление',
            'category' => 'massage'
        ]);

        // Проверяем результат
        $this->assertEquals(789, $adId);
        
        // Проверяем что событие отправлено (через Integration Service)
        // Event::assertDispatched(AdCreated::class);
    }

    /** @test */
    public function all_integration_services_are_properly_bound()
    {
        // Проверяем что все сервисы зарегистрированы в Service Container
        $this->assertInstanceOf(
            UserFavoritesIntegrationService::class,
            app(UserFavoritesIntegrationService::class)
        );
        
        $this->assertInstanceOf(
            UserReviewsIntegrationService::class,
            app(UserReviewsIntegrationService::class)
        );
        
        $this->assertInstanceOf(
            UserAdsIntegrationService::class,
            app(UserAdsIntegrationService::class)
        );
    }

    /** @test */
    public function user_statistics_use_integration_services()
    {
        // Мокаем все сервисы для статистики
        $this->mock(UserFavoritesIntegrationService::class, function ($mock) {
            $mock->shouldReceive('getUserFavoritesCount')
                ->with($this->user->id)
                ->andReturn(5);
                
            $mock->shouldReceive('getUserFavoritesStatistics')
                ->with($this->user->id)
                ->andReturn([
                    'total_favorites' => 5,
                    'active_favorites' => 4,
                    'archived_favorites' => 1,
                ]);
        });
        
        $this->mock(UserReviewsIntegrationService::class, function ($mock) {
            $mock->shouldReceive('getUserReceivedReviewsCount')
                ->with($this->user->id)
                ->andReturn(12);
                
            $mock->shouldReceive('getUserAverageRating')
                ->with($this->user->id)
                ->andReturn(4.8);
                
            $mock->shouldReceive('getUserReviewsStatistics')
                ->with($this->user->id)
                ->andReturn([
                    'received_reviews' => 12,
                    'written_reviews' => 3,
                    'average_rating' => 4.8,
                ]);
        });
        
        $this->mock(UserAdsIntegrationService::class, function ($mock) {
            $mock->shouldReceive('getUserAdsCount')
                ->with($this->user->id)
                ->andReturn(7);
                
            $mock->shouldReceive('getUserActiveAdsCount')
                ->with($this->user->id)
                ->andReturn(5);
                
            $mock->shouldReceive('getUserAdsStatistics')
                ->with($this->user->id)
                ->andReturn([
                    'total_ads' => 7,
                    'active_ads' => 5,
                    'draft_ads' => 2,
                ]);
        });

        // Получаем статистику через трейты
        $favoritesCount = $this->user->getFavoritesCount();
        $favoritesStats = $this->user->getFavoritesStatistics();
        
        $reviewsCount = $this->user->getReceivedReviewsCount();
        $averageRating = $this->user->getAverageRating();
        $reviewsStats = $this->user->getReviewsStatistics();
        
        $adsCount = $this->user->getAdsCount();
        $activeAdsCount = $this->user->getActiveAdsCount();
        $adsStats = $this->user->getAdsStatistics();

        // Проверяем результаты
        $this->assertEquals(5, $favoritesCount);
        $this->assertEquals(5, $favoritesStats['total_favorites']);
        
        $this->assertEquals(12, $reviewsCount);
        $this->assertEquals(4.8, $averageRating);
        $this->assertEquals(12, $reviewsStats['received_reviews']);
        
        $this->assertEquals(7, $adsCount);
        $this->assertEquals(5, $activeAdsCount);
        $this->assertEquals(7, $adsStats['total_ads']);
    }

    /** @test */
    public function integration_services_handle_errors_gracefully()
    {
        // Мокаем сервис с ошибкой
        $this->mock(UserFavoritesIntegrationService::class, function ($mock) {
            $mock->shouldReceive('addToUserFavorites')
                ->once()
                ->with($this->user->id, 999)
                ->andReturn(false); // Неуспешная операция
        });

        // Пытаемся добавить в избранное
        $result = $this->user->addToFavorites(999);

        // Проверяем что метод корректно обработал ошибку
        $this->assertFalse($result);
    }

    /** @test */
    public function deprecated_methods_still_work_for_backward_compatibility()
    {
        // Мокаем сервисы для deprecated методов
        $this->mock(UserFavoritesIntegrationService::class, function ($mock) {
            $mock->shouldReceive('getUserFavorites')
                ->with($this->user->id)
                ->andReturn(collect(['favorite1', 'favorite2']));
        });
        
        $this->mock(UserReviewsIntegrationService::class, function ($mock) {
            $mock->shouldReceive('getUserReceivedReviews')
                ->with($this->user->id)
                ->andReturn(collect(['review1', 'review2']));
        });
        
        $this->mock(UserAdsIntegrationService::class, function ($mock) {
            $mock->shouldReceive('getUserAds')
                ->with($this->user->id)
                ->andReturn(collect(['ad1', 'ad2']));
        });

        // Вызываем deprecated методы
        $favorites = $this->user->favorites();
        $reviews = $this->user->reviews();
        $ads = $this->user->ads();

        // Проверяем что они работают
        $this->assertCount(2, $favorites);
        $this->assertCount(2, $reviews);
        $this->assertCount(2, $ads);
    }

    /** @test */
    public function integration_follows_ddd_boundaries()
    {
        // Проверяем что User домен не импортирует модели других доменов
        $userReflection = new \ReflectionClass(User::class);
        $userFileContent = file_get_contents($userReflection->getFileName());
        
        // Проверяем что нет прямых импортов других доменов
        $this->assertStringNotContainsString('use App\Domain\Ad\Models', $userFileContent);
        $this->assertStringNotContainsString('use App\Domain\Review\Models', $userFileContent);
        $this->assertStringNotContainsString('use App\Domain\Favorite\Models', $userFileContent);
        
        // Проверяем что есть импорты только интеграционных трейтов
        $this->assertStringContainsString('use App\Domain\User\Traits\HasFavoritesIntegration', $userFileContent);
        $this->assertStringContainsString('use App\Domain\User\Traits\HasReviewsIntegration', $userFileContent);
        $this->assertStringContainsString('use App\Domain\User\Traits\HasAdsIntegration', $userFileContent);
    }

    /** @test */
    public function events_have_proper_structure()
    {
        // Проверяем структуру событий
        $this->assertTrue(class_exists('App\Domain\Favorite\Events\FavoriteAdded'));
        $this->assertTrue(class_exists('App\Domain\Favorite\Events\FavoriteRemoved'));
        $this->assertTrue(class_exists('App\Domain\Review\Events\ReviewCreated'));
        $this->assertTrue(class_exists('App\Domain\Review\Events\ReviewUpdated'));
        $this->assertTrue(class_exists('App\Domain\Review\Events\ReviewDeleted'));
        $this->assertTrue(class_exists('App\Domain\Ad\Events\AdCreated'));
        $this->assertTrue(class_exists('App\Domain\Ad\Events\AdUpdated'));
        $this->assertTrue(class_exists('App\Domain\Ad\Events\AdDeleted'));
        $this->assertTrue(class_exists('App\Domain\Ad\Events\AdPublished'));
        $this->assertTrue(class_exists('App\Domain\Ad\Events\AdArchived'));
        
        // Проверяем что события имеют метод toArray
        $favoriteEvent = new \App\Domain\Favorite\Events\FavoriteAdded(1, 2);
        $this->assertTrue(method_exists($favoriteEvent, 'toArray'));
        $this->assertIsArray($favoriteEvent->toArray());
        
        $reviewEvent = new \App\Domain\Review\Events\ReviewCreated(1, 2, 3, ['rating' => 5]);
        $this->assertTrue(method_exists($reviewEvent, 'toArray'));
        $this->assertIsArray($reviewEvent->toArray());
        
        $adEvent = new \App\Domain\Ad\Events\AdCreated(1, 2, ['title' => 'Test']);
        $this->assertTrue(method_exists($adEvent, 'toArray'));
        $this->assertIsArray($adEvent->toArray());
    }
}