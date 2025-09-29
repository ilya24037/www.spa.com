<?php

namespace Tests\Unit\Domain\Ad\Repositories;

use Tests\TestCase;
use App\Domain\Ad\Repositories\AdRepository;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Enums\AdStatus;
use App\Domain\User\Models\User;
use Tests\Traits\SafeRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class AdRepositoryTest extends TestCase
{
    use SafeRefreshDatabase;

    private AdRepository $repository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new AdRepository(new Ad());
        $this->user = User::factory()->create();
    }

    /**
     * Тест поиска объявления по ID с загрузкой связей
     */
    public function test_find_with_components()
    {
        // Arrange
        $ad = Ad::factory()->create([
            'user_id' => $this->user->id,
            'status' => AdStatus::ACTIVE
        ]);

        // Act
        $result = $this->repository->find($ad->id, true);

        // Assert
        $this->assertInstanceOf(Ad::class, $result);
        $this->assertEquals($ad->id, $result->id);
        $this->assertTrue($result->relationLoaded('content'));
        $this->assertTrue($result->relationLoaded('pricing'));
        $this->assertTrue($result->relationLoaded('schedule'));
        $this->assertTrue($result->relationLoaded('media'));
        $this->assertTrue($result->relationLoaded('user'));
    }

    /**
     * Тест поиска объявления без загрузки связей
     */
    public function test_find_without_components()
    {
        // Arrange
        $ad = Ad::factory()->create(['user_id' => $this->user->id]);

        // Act
        $result = $this->repository->find($ad->id, false);

        // Assert
        $this->assertInstanceOf(Ad::class, $result);
        $this->assertFalse($result->relationLoaded('content'));
        $this->assertFalse($result->relationLoaded('pricing'));
    }

    /**
     * Тест обновления объявления по экземпляру модели
     */
    public function test_update_ad()
    {
        // Arrange
        $ad = Ad::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Original Title'
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description'
        ];

        // Act
        $result = $this->repository->updateAd($ad, $updateData);

        // Assert
        $this->assertInstanceOf(Ad::class, $result);
        $this->assertEquals('Updated Title', $result->title);
        $this->assertEquals('Updated Description', $result->description);
    }

    /**
     * Тест получения объявлений пользователя
     */
    public function test_find_by_user()
    {
        // Arrange
        Ad::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => AdStatus::ACTIVE
        ]);
        
        Ad::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => AdStatus::DRAFT
        ]);

        // Создаем объявления другого пользователя
        $otherUser = User::factory()->create();
        Ad::factory()->count(2)->create(['user_id' => $otherUser->id]);

        // Act
        $result = $this->repository->findByUser($this->user, AdStatus::ACTIVE, 10);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->total());
        $this->assertTrue($result->items()[0]->relationLoaded('content'));
    }

    /**
     * Тест поиска объявлений с фильтрами
     */
    public function test_search_with_filters()
    {
        // Arrange
        Ad::factory()->create([
            'status' => AdStatus::ACTIVE,
            'category' => 'massage',
            'address' => 'Москва, Центр',
            'age' => 25
        ]);

        Ad::factory()->create([
            'status' => AdStatus::ACTIVE,
            'category' => 'spa',
            'address' => 'Санкт-Петербург',
            'age' => 30
        ]);

        $filters = [
            'category' => 'massage',
            'location' => 'Москва',
            'age_from' => 20,
            'age_to' => 28
        ];

        // Act
        $result = $this->repository->search($filters, 10);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());
        $this->assertEquals('massage', $result->items()[0]->category);
    }

    /**
     * Тест поиска по тексту
     */
    public function test_search_by_text()
    {
        // Arrange
        $ad = Ad::factory()->create(['status' => AdStatus::ACTIVE]);
        $ad->content()->create([
            'title' => 'Профессиональный массаж',
            'description' => 'Качественные услуги массажа',
            'specialty' => 'Массажист'
        ]);

        Ad::factory()->create(['status' => AdStatus::ACTIVE])
            ->content()->create([
                'title' => 'СПА процедуры',
                'description' => 'Релакс и отдых'
            ]);

        $filters = ['search' => 'массаж'];

        // Act
        $result = $this->repository->search($filters, 10);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertStringContainsString('массаж', strtolower($result->items()[0]->content->title));
    }

    /**
     * Тест получения популярных объявлений
     */
    public function test_get_popular()
    {
        // Arrange
        Ad::factory()->create([
            'status' => AdStatus::ACTIVE,
            'views_count' => 100,
            'favorites_count' => 50
        ]);

        Ad::factory()->create([
            'status' => AdStatus::ACTIVE,
            'views_count' => 200,
            'favorites_count' => 30
        ]);

        Ad::factory()->create([
            'status' => AdStatus::ACTIVE,
            'views_count' => 50,
            'favorites_count' => 10
        ]);

        // Act
        $result = $this->repository->getPopular(2);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals(200, $result->first()->views_count);
    }

    /**
     * Тест получения недавних объявлений
     */
    public function test_get_recent()
    {
        // Arrange
        $oldAd = Ad::factory()->create([
            'status' => AdStatus::ACTIVE,
            'created_at' => now()->subDays(5)
        ]);

        $newAd = Ad::factory()->create([
            'status' => AdStatus::ACTIVE,
            'created_at' => now()->subHour()
        ]);

        // Act
        $result = $this->repository->getRecent(5);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals($newAd->id, $result->first()->id);
    }

    /**
     * Тест получения объявлений на модерации
     */
    public function test_get_pending_moderation()
    {
        // Arrange
        Ad::factory()->count(3)->create(['status' => AdStatus::WAITING_PAYMENT]);
        Ad::factory()->count(2)->create(['status' => AdStatus::ACTIVE]);

        // Act
        $result = $this->repository->getPendingModeration(10);

        // Assert
        $this->assertEquals(3, $result->total());
        $this->assertEquals(AdStatus::WAITING_PAYMENT, $result->items()[0]->status);
    }

    /**
     * Тест получения истекающих объявлений
     */
    public function test_get_expiring()
    {
        // Arrange
        Ad::factory()->create([
            'status' => AdStatus::ACTIVE,
            'expires_at' => now()->addDays(2)
        ]);

        Ad::factory()->create([
            'status' => AdStatus::ACTIVE,
            'expires_at' => now()->addDays(5) // Не истекает в ближайшие 3 дня
        ]);

        Ad::factory()->create([
            'status' => AdStatus::ACTIVE,
            'expires_at' => null // Нет срока истечения
        ]);

        // Act
        $result = $this->repository->getExpiring(3);

        // Assert
        $this->assertCount(1, $result);
    }

    /**
     * Тест поиска похожих объявлений
     */
    public function test_find_similar()
    {
        // Arrange
        $originalAd = Ad::factory()->create([
            'status' => AdStatus::ACTIVE,
            'category' => 'massage',
            'address' => 'Москва, Центральный район'
        ]);

        $similarAd = Ad::factory()->create([
            'status' => AdStatus::ACTIVE,
            'category' => 'massage',
            'address' => 'Москва, Центр'
        ]);

        $differentAd = Ad::factory()->create([
            'status' => AdStatus::ACTIVE,
            'category' => 'spa',
            'address' => 'Санкт-Петербург'
        ]);

        // Act
        $result = $this->repository->findSimilar($originalAd, 5);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($similarAd->id, $result->first()->id);
    }

    /**
     * Тест получения статистики
     */
    public function test_get_stats()
    {
        // Arrange
        Ad::factory()->count(5)->create(['status' => AdStatus::ACTIVE]);
        Ad::factory()->count(3)->create(['status' => AdStatus::DRAFT]);
        Ad::factory()->count(2)->create(['status' => AdStatus::WAITING_PAYMENT]);
        Ad::factory()->count(1)->create(['status' => AdStatus::ARCHIVED]);

        // Act
        $stats = $this->repository->getStats();

        // Assert
        $this->assertEquals(11, $stats['total']);
        $this->assertEquals(5, $stats['active']);
        $this->assertEquals(3, $stats['draft']);
        $this->assertEquals(2, $stats['waiting_payment']);
        $this->assertEquals(1, $stats['archived']);
    }

    /**
     * Тест увеличения счетчика просмотров
     */
    public function test_increment_views()
    {
        // Arrange
        $ad = Ad::factory()->create(['views_count' => 10]);

        // Act
        $this->repository->incrementViews($ad);

        // Assert
        $this->assertEquals(11, $ad->fresh()->views_count);
    }

    /**
     * Тест увеличения счетчика показа контактов
     */
    public function test_increment_contacts_shown()
    {
        // Arrange
        $ad = Ad::factory()->create(['contacts_shown' => 5]);

        // Act
        $this->repository->incrementContactsShown($ad);

        // Assert
        $this->assertEquals(6, $ad->fresh()->contacts_shown);
    }

    /**
     * Тест поиска по списку ID
     */
    public function test_find_by_ids()
    {
        // Arrange
        $ads = Ad::factory()->count(5)->create();
        $ids = $ads->pluck('id')->take(3)->toArray();

        // Act
        $result = $this->repository->findByIds($ids);

        // Assert
        $this->assertCount(3, $result);
        $this->assertTrue($result->pluck('id')->contains($ids[0]));
        $this->assertTrue($result->pluck('id')->contains($ids[1]));
        $this->assertTrue($result->pluck('id')->contains($ids[2]));
    }

    /**
     * Тест массового обновления статуса
     */
    public function test_update_status_bulk()
    {
        // Arrange
        $ads = Ad::factory()->count(3)->create(['status' => AdStatus::DRAFT]);
        $ids = $ads->pluck('id')->toArray();

        // Act
        $count = $this->repository->updateStatusBulk($ids, AdStatus::ACTIVE);

        // Assert
        $this->assertEquals(3, $count);
        
        foreach ($ids as $id) {
            $ad = Ad::find($id);
            $this->assertEquals(AdStatus::ACTIVE, $ad->status);
        }
    }

    /**
     * Тест поиска черновика пользователя
     */
    public function test_find_user_draft()
    {
        // Arrange
        $draft = Ad::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft'
        ]);

        $activeAd = Ad::factory()->create([
            'user_id' => $this->user->id,
            'status' => AdStatus::ACTIVE
        ]);

        // Act
        $result = $this->repository->findUserDraft($this->user->id, $draft->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($draft->id, $result->id);
        
        // Проверяем что активное объявление не найдено
        $notFound = $this->repository->findUserDraft($this->user->id, $activeAd->id);
        $this->assertNull($notFound);
    }

    /**
     * Тест получения статистики пользователя
     */
    public function test_get_user_stats()
    {
        // Arrange
        Ad::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => AdStatus::ACTIVE
        ]);
        
        Ad::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => AdStatus::DRAFT
        ]);
        
        Ad::factory()->create([
            'user_id' => $this->user->id,
            'status' => AdStatus::ARCHIVED
        ]);

        // Act
        $stats = $this->repository->getUserStats($this->user->id);

        // Assert
        $this->assertEquals(6, $stats['total']);
        $this->assertEquals(3, $stats['active']);
        $this->assertEquals(2, $stats['draft']);
        $this->assertEquals(1, $stats['archived']);
    }

    /**
     * Тест сортировки по цене
     */
    public function test_search_sort_by_price()
    {
        // Arrange
        $ad1 = Ad::factory()->create(['status' => AdStatus::ACTIVE]);
        $ad1->pricing()->create(['price' => 3000]);

        $ad2 = Ad::factory()->create(['status' => AdStatus::ACTIVE]);
        $ad2->pricing()->create(['price' => 1000]);

        $ad3 = Ad::factory()->create(['status' => AdStatus::ACTIVE]);
        $ad3->pricing()->create(['price' => 2000]);

        $filters = [
            'sort_by' => 'price',
            'sort_order' => 'asc'
        ];

        // Act
        $result = $this->repository->search($filters, 10);

        // Assert
        $prices = $result->items()->map(fn($ad) => $ad->pricing->price)->toArray();
        $this->assertEquals([1000, 2000, 3000], $prices);
    }

    /**
     * Тест фильтрации по цене
     */
    public function test_search_filter_by_price_range()
    {
        // Arrange
        $ad1 = Ad::factory()->create(['status' => AdStatus::ACTIVE]);
        $ad1->pricing()->create(['price' => 1000]);

        $ad2 = Ad::factory()->create(['status' => AdStatus::ACTIVE]);
        $ad2->pricing()->create(['price' => 2000]);

        $ad3 = Ad::factory()->create(['status' => AdStatus::ACTIVE]);
        $ad3->pricing()->create(['price' => 3000]);

        $filters = [
            'price_from' => 1500,
            'price_to' => 2500
        ];

        // Act
        $result = $this->repository->search($filters, 10);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertEquals(2000, $result->items()[0]->pricing->price);
    }
}