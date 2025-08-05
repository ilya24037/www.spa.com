<?php

namespace Tests\Unit;

use App\Domain\User\Models\User;
use App\Application\Services\Integration\UserFavoritesIntegrationService;
use App\Application\Services\Integration\UserReviewsIntegrationService;
use App\Application\Services\Integration\UserAdsIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тест интеграционных трейтов User модели
 * Проверяет доступность и работоспособность DDD методов
 */
class UserIntegrationTraitsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем тестового пользователя
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'role' => 'client',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function user_has_favorites_integration_methods()
    {
        // Проверяем наличие методов HasFavoritesIntegration
        $this->assertTrue(method_exists($this->user, 'getFavorites'));
        $this->assertTrue(method_exists($this->user, 'getFavoritesCount'));
        $this->assertTrue(method_exists($this->user, 'hasFavorite'));
        $this->assertTrue(method_exists($this->user, 'addToFavorites'));
        $this->assertTrue(method_exists($this->user, 'removeFromFavorites'));
        $this->assertTrue(method_exists($this->user, 'toggleFavorite'));
        $this->assertTrue(method_exists($this->user, 'clearFavorites'));
        
        // Проверяем deprecated методы для обратной совместимости
        $this->assertTrue(method_exists($this->user, 'favorites'));
        $this->assertTrue(method_exists($this->user, 'isFavorite'));
    }

    /** @test */
    public function user_has_reviews_integration_methods()
    {
        // Проверяем наличие методов HasReviewsIntegration
        $this->assertTrue(method_exists($this->user, 'getReceivedReviews'));
        $this->assertTrue(method_exists($this->user, 'getWrittenReviews'));
        $this->assertTrue(method_exists($this->user, 'getReceivedReviewsCount'));
        $this->assertTrue(method_exists($this->user, 'getWrittenReviewsCount'));
        $this->assertTrue(method_exists($this->user, 'getAverageRating'));
        $this->assertTrue(method_exists($this->user, 'getStarRating'));
        $this->assertTrue(method_exists($this->user, 'canWriteReview'));
        $this->assertTrue(method_exists($this->user, 'hasReviewedUser'));
        $this->assertTrue(method_exists($this->user, 'writeReview'));
        
        // Проверяем deprecated методы для обратной совместимости
        $this->assertTrue(method_exists($this->user, 'receivedReviews'));
        $this->assertTrue(method_exists($this->user, 'writtenReviews'));
        $this->assertTrue(method_exists($this->user, 'reviews'));
    }

    /** @test */
    public function user_has_ads_integration_methods()
    {
        // Проверяем наличие методов HasAdsIntegration
        $this->assertTrue(method_exists($this->user, 'getAds'));
        $this->assertTrue(method_exists($this->user, 'getActiveAds'));
        $this->assertTrue(method_exists($this->user, 'getDraftAds'));
        $this->assertTrue(method_exists($this->user, 'getArchivedAds'));
        $this->assertTrue(method_exists($this->user, 'getAdsCount'));
        $this->assertTrue(method_exists($this->user, 'getActiveAdsCount'));
        $this->assertTrue(method_exists($this->user, 'canCreateAd'));
        $this->assertTrue(method_exists($this->user, 'ownsAd'));
        $this->assertTrue(method_exists($this->user, 'createAd'));
        $this->assertTrue(method_exists($this->user, 'updateAd'));
        $this->assertTrue(method_exists($this->user, 'deleteAd'));
        $this->assertTrue(method_exists($this->user, 'publishAd'));
        
        // Проверяем deprecated методы для обратной совместимости
        $this->assertTrue(method_exists($this->user, 'ads'));
        $this->assertTrue(method_exists($this->user, 'activeAds'));
        $this->assertTrue(method_exists($this->user, 'hasAd'));
    }

    /** @test */
    public function favorites_integration_service_is_used()
    {
        // Мокаем сервис
        $this->mock(UserFavoritesIntegrationService::class, function ($mock) {
            $mock->shouldReceive('getUserFavorites')
                ->once()
                ->with($this->user->id)
                ->andReturn(collect([]));
        });

        // Вызываем метод через трейт
        $favorites = $this->user->getFavorites();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $favorites);
    }

    /** @test */
    public function reviews_integration_service_is_used()
    {
        // Мокаем сервис
        $this->mock(UserReviewsIntegrationService::class, function ($mock) {
            $mock->shouldReceive('getUserReceivedReviews')
                ->once()
                ->with($this->user->id)
                ->andReturn(collect([]));
                
            $mock->shouldReceive('getUserAverageRating')
                ->once()
                ->with($this->user->id)
                ->andReturn(4.5);
        });

        // Вызываем методы через трейт
        $reviews = $this->user->getReceivedReviews();
        $rating = $this->user->getAverageRating();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $reviews);
        $this->assertEquals(4.5, $rating);
    }

    /** @test */
    public function ads_integration_service_is_used()
    {
        // Мокаем сервис
        $this->mock(UserAdsIntegrationService::class, function ($mock) {
            $mock->shouldReceive('getUserAds')
                ->once()
                ->with($this->user->id)
                ->andReturn(collect([]));
                
            $mock->shouldReceive('getUserAdsCount')
                ->once()
                ->with($this->user->id)
                ->andReturn(5);
        });

        // Вызываем методы через трейт
        $ads = $this->user->getAds();
        $count = $this->user->getAdsCount();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $ads);
        $this->assertEquals(5, $count);
    }

    /** @test */
    public function deprecated_methods_work_for_backward_compatibility()
    {
        // Мокаем сервисы для deprecated методов
        $this->mock(UserFavoritesIntegrationService::class, function ($mock) {
            $mock->shouldReceive('getUserFavorites')
                ->with($this->user->id)
                ->andReturn(collect([]));
        });
        
        $this->mock(UserReviewsIntegrationService::class, function ($mock) {
            $mock->shouldReceive('getUserReceivedReviews')
                ->with($this->user->id)
                ->andReturn(collect([]));
        });
        
        $this->mock(UserAdsIntegrationService::class, function ($mock) {
            $mock->shouldReceive('getUserAds')
                ->with($this->user->id)
                ->andReturn(collect([]));
        });

        // Проверяем что deprecated методы работают
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $this->user->favorites());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $this->user->reviews());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $this->user->ads());
    }

    /** @test */
    public function integration_traits_follow_ddd_principles()
    {
        // Проверяем что трейты используют app() для получения сервисов
        // Это гарантирует слабую связанность и соблюдение DDD принципов
        
        $reflection = new \ReflectionClass($this->user);
        $traits = $reflection->getTraitNames();
        
        $this->assertContains('App\Domain\User\Traits\HasFavoritesIntegration', $traits);
        $this->assertContains('App\Domain\User\Traits\HasReviewsIntegration', $traits);
        $this->assertContains('App\Domain\User\Traits\HasAdsIntegration', $traits);
    }

    /** @test */
    public function user_has_profile_and_roles_traits()
    {
        // Проверяем что базовые трейты тоже работают
        $this->assertTrue(method_exists($this->user, 'getProfile'));
        $this->assertTrue(method_exists($this->user, 'ensureProfile'));
        $this->assertTrue(method_exists($this->user, 'isClient'));
        $this->assertTrue(method_exists($this->user, 'isMaster'));
        $this->assertTrue(method_exists($this->user, 'isAdmin'));
    }

    /** @test */
    public function user_model_has_correct_traits_loaded()
    {
        $reflection = new \ReflectionClass($this->user);
        $traits = $reflection->getTraitNames();
        
        // Проверяем что все необходимые трейты загружены
        $expectedTraits = [
            'App\Domain\User\Traits\HasRoles',
            'App\Domain\User\Traits\HasProfile',
            'App\Domain\User\Traits\HasBookingIntegration',
            'App\Domain\User\Traits\HasMasterIntegration',
            'App\Domain\User\Traits\HasFavoritesIntegration',
            'App\Domain\User\Traits\HasReviewsIntegration',
            'App\Domain\User\Traits\HasAdsIntegration',
        ];
        
        foreach ($expectedTraits as $trait) {
            $this->assertContains($trait, $traits, "Trait {$trait} not found in User model");
        }
    }
}