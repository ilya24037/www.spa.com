<?php

namespace Tests\Unit\Domain\Master\Repositories;

use Tests\TestCase;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Models\User;
use App\Domain\Service\Models\Service;
use App\Enums\MasterStatus;
use App\Enums\MasterLevel;
use Tests\Traits\SafeRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class MasterRepositoryTest extends TestCase
{
    use SafeRefreshDatabase;

    private MasterRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new MasterRepository();
    }

    /**
     * Тест поиска профиля по ID с загрузкой связей
     */
    public function test_find_with_relations()
    {
        // Arrange
        $user = User::factory()->create();
        $master = MasterProfile::factory()->create(['user_id' => $user->id]);

        // Act
        $result = $this->repository->find($master->id);

        // Assert
        $this->assertInstanceOf(MasterProfile::class, $result);
        $this->assertEquals($master->id, $result->id);
        $this->assertTrue($result->relationLoaded('user'));
        $this->assertTrue($result->relationLoaded('services'));
        $this->assertTrue($result->relationLoaded('photos'));
        $this->assertTrue($result->relationLoaded('reviews'));
    }

    /**
     * Тест поиска профиля по user_id
     */
    public function test_find_by_user_id()
    {
        // Arrange
        $user = User::factory()->create();
        $master = MasterProfile::factory()->create(['user_id' => $user->id]);

        // Act
        $result = $this->repository->findByUserId($user->id);

        // Assert
        $this->assertInstanceOf(MasterProfile::class, $result);
        $this->assertEquals($master->id, $result->id);
        $this->assertEquals($user->id, $result->user_id);
    }

    /**
     * Тест поиска профиля по slug
     */
    public function test_find_by_slug()
    {
        // Arrange
        $master = MasterProfile::factory()->create(['slug' => 'test-master']);

        // Act
        $result = $this->repository->findBySlug('test-master');

        // Assert
        $this->assertInstanceOf(MasterProfile::class, $result);
        $this->assertEquals('test-master', $result->slug);
    }

    /**
     * Тест обновления профиля
     */
    public function test_update_master()
    {
        // Arrange
        $master = MasterProfile::factory()->create([
            'display_name' => 'Original Name',
            'bio' => 'Original bio'
        ]);

        $updateData = [
            'display_name' => 'Updated Name',
            'bio' => 'Updated bio'
        ];

        // Act
        $result = $this->repository->update($master->id, $updateData);

        // Assert
        $this->assertTrue($result);
        $master->refresh();
        $this->assertEquals('Updated Name', $master->display_name);
        $this->assertEquals('Updated bio', $master->bio);
    }

    /**
     * Тест получения активных мастеров
     */
    public function test_get_active()
    {
        // Arrange
        MasterProfile::factory()->count(3)->create(['status' => MasterStatus::ACTIVE]);
        MasterProfile::factory()->count(2)->create(['status' => MasterStatus::INACTIVE]);
        MasterProfile::factory()->create(['status' => MasterStatus::PENDING]);

        // Act
        $result = $this->repository->getActive();

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals(MasterStatus::ACTIVE, $result->first()->status);
    }

    /**
     * Тест поиска мастеров с фильтрами
     */
    public function test_search_with_filters()
    {
        // Arrange
        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'city' => 'Москва',
            'rating' => 4.5,
            'level' => MasterLevel::EXPERT
        ]);

        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'city' => 'Санкт-Петербург',
            'rating' => 3.8,
            'level' => MasterLevel::STANDARD
        ]);

        $filters = [
            'city' => 'Москва',
            'min_rating' => 4.0,
            'level' => MasterLevel::EXPERT
        ];

        // Act
        $result = $this->repository->search($filters, 10);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());
        $this->assertEquals('Москва', $result->items()[0]->city);
    }

    /**
     * Тест получения топ мастеров
     */
    public function test_get_top_masters()
    {
        // Arrange
        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'rating' => 4.8,
            'reviews_count' => 50
        ]);

        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'rating' => 4.9,
            'reviews_count' => 100
        ]);

        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'rating' => 3.5,
            'reviews_count' => 10
        ]);

        // Act
        $result = $this->repository->getTopMasters(2);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals(4.9, $result->first()->rating);
    }

    /**
     * Тест получения новых мастеров
     */
    public function test_get_new_masters()
    {
        // Arrange
        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'created_at' => now()->subDays(10)
        ]);

        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'created_at' => now()->subDays(5)
        ]);

        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'created_at' => now()->subDays(40) // Старше 30 дней
        ]);

        // Act
        $result = $this->repository->getNewMasters(5);

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->first()->created_at > $result->last()->created_at);
    }

    /**
     * Тест получения мастеров по услуге
     */
    public function test_get_masters_by_service()
    {
        // Arrange
        $service = Service::factory()->create();
        
        $master1 = MasterProfile::factory()->create(['status' => MasterStatus::ACTIVE]);
        $master1->services()->attach($service);

        $master2 = MasterProfile::factory()->create(['status' => MasterStatus::ACTIVE]);
        $master2->services()->attach($service);

        $master3 = MasterProfile::factory()->create(['status' => MasterStatus::ACTIVE]);
        // Не прикрепляем услугу к master3

        // Act
        $result = $this->repository->getMastersByService($service->id);

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $master1->id));
        $this->assertTrue($result->contains('id', $master2->id));
        $this->assertFalse($result->contains('id', $master3->id));
    }

    /**
     * Тест получения мастеров по городу
     */
    public function test_get_masters_by_city()
    {
        // Arrange
        MasterProfile::factory()->count(3)->create([
            'status' => MasterStatus::ACTIVE,
            'city' => 'Москва'
        ]);

        MasterProfile::factory()->count(2)->create([
            'status' => MasterStatus::ACTIVE,
            'city' => 'Санкт-Петербург'
        ]);

        // Act
        $result = $this->repository->getMastersByCity('Москва');

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals('Москва', $result->first()->city);
    }

    /**
     * Тест получения ближайших мастеров
     */
    public function test_get_nearby_masters()
    {
        // Arrange
        // Мастер в центре Москвы
        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'latitude' => 55.7558,
            'longitude' => 37.6173
        ]);

        // Мастер немного севернее (примерно 5 км)
        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'latitude' => 55.8058,
            'longitude' => 37.6173
        ]);

        // Мастер далеко (более 10 км)
        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'latitude' => 56.0000,
            'longitude' => 37.6173
        ]);

        // Act
        $result = $this->repository->getNearbyMasters(55.7558, 37.6173, 10);

        // Assert
        $this->assertCount(2, $result);
    }

    /**
     * Тест получения мастеров на модерации
     */
    public function test_get_pending_moderation()
    {
        // Arrange
        MasterProfile::factory()->count(2)->create(['status' => MasterStatus::PENDING]);
        MasterProfile::factory()->create(['status' => MasterStatus::ACTIVE]);

        // Act
        $result = $this->repository->getPendingModeration();

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals(MasterStatus::PENDING, $result->first()->status);
    }

    /**
     * Тест получения статистики мастера
     */
    public function test_get_master_stats()
    {
        // Arrange
        $user = User::factory()->create();
        $master = MasterProfile::factory()->create([
            'user_id' => $user->id,
            'views_count' => 100
        ]);

        // Создаем бронирования
        $master->bookings()->createMany([
            ['status' => 'completed', 'total_price' => 1000, 'client_id' => 1],
            ['status' => 'completed', 'total_price' => 1500, 'client_id' => 2],
            ['status' => 'cancelled', 'total_price' => 500, 'client_id' => 3]
        ]);

        // Act
        $stats = $this->repository->getMasterStats($master->id);

        // Assert
        $this->assertEquals(3, $stats['total_bookings']);
        $this->assertEquals(2, $stats['completed_bookings']);
        $this->assertEquals(2500, $stats['total_revenue']);
        $this->assertEquals(100, $stats['profile_views']);
    }

    /**
     * Тест увеличения счетчика просмотров
     */
    public function test_increment_views()
    {
        // Arrange
        $master = MasterProfile::factory()->create(['views_count' => 50]);

        // Act
        $this->repository->incrementViews($master);

        // Assert
        $this->assertEquals(51, $master->fresh()->views_count);
    }

    /**
     * Тест обновления рейтинга
     */
    public function test_update_rating()
    {
        // Arrange
        $user = User::factory()->create();
        $master = MasterProfile::factory()->create([
            'user_id' => $user->id,
            'rating' => 0,
            'reviews_count' => 0
        ]);

        // Мокаем методы user
        $user->setRelation('receivedReviews', collect([
            (object)['rating' => 5],
            (object)['rating' => 4],
            (object)['rating' => 5]
        ]));

        // Act
        $this->repository->updateRating($master);

        // Assert
        $master->refresh();
        $this->assertEquals(4.67, $master->rating);
        $this->assertEquals(3, $master->reviews_count);
    }

    /**
     * Тест обновления уровня мастера
     */
    public function test_update_level()
    {
        // Arrange
        $master = MasterProfile::factory()->create([
            'experience_years' => 5,
            'rating' => 4.8,
            'reviews_count' => 50,
            'level' => MasterLevel::STANDARD
        ]);

        // Act
        $this->repository->updateLevel($master);

        // Assert
        $master->refresh();
        $this->assertEquals(MasterLevel::EXPERT, $master->level);
    }

    /**
     * Тест поиска с текстовым фильтром
     */
    public function test_search_with_text_filter()
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Иван Иванов']);
        $master = MasterProfile::factory()->create([
            'user_id' => $user->id,
            'display_name' => 'Массажист Иван',
            'bio' => 'Профессиональный массаж'
        ]);

        MasterProfile::factory()->create([
            'display_name' => 'Мастер Петр',
            'bio' => 'СПА процедуры'
        ]);

        $filters = ['search' => 'Иван'];

        // Act
        $result = $this->repository->search($filters, 10);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertEquals($master->id, $result->items()[0]->id);
    }

    /**
     * Тест массового обновления статусов
     */
    public function test_batch_update_status()
    {
        // Arrange
        $masters = MasterProfile::factory()->count(3)->create(['status' => MasterStatus::PENDING]);
        $ids = $masters->pluck('id')->toArray();

        // Act
        $count = $this->repository->batchUpdateStatus($ids, MasterStatus::ACTIVE);

        // Assert
        $this->assertEquals(3, $count);
        
        foreach ($ids as $id) {
            $master = MasterProfile::find($id);
            $this->assertEquals(MasterStatus::ACTIVE, $master->status);
        }
    }

    /**
     * Тест деактивации неактивных мастеров
     */
    public function test_deactivate_inactive_masters()
    {
        // Arrange
        // Активный мастер без бронирований более 90 дней
        $inactiveMaster = MasterProfile::factory()->create(['status' => MasterStatus::ACTIVE]);

        // Активный мастер с недавним бронированием
        $activeMaster = MasterProfile::factory()->create(['status' => MasterStatus::ACTIVE]);
        $activeMaster->bookings()->create([
            'created_at' => now()->subDays(30),
            'client_id' => 1
        ]);

        // Act
        $count = $this->repository->deactivateInactiveMasters(90);

        // Assert
        $this->assertEquals(1, $count);
        $this->assertEquals(MasterStatus::INACTIVE, $inactiveMaster->fresh()->status);
        $this->assertEquals(MasterStatus::ACTIVE, $activeMaster->fresh()->status);
    }

    /**
     * Тест сортировки по цене
     */
    public function test_search_sort_by_price()
    {
        // Arrange
        $master1 = MasterProfile::factory()->create();
        $master2 = MasterProfile::factory()->create();
        $master3 = MasterProfile::factory()->create();

        // Создаем цены для услуг
        \DB::table('master_service_prices')->insert([
            ['master_profile_id' => $master1->id, 'service_id' => 1, 'price' => 3000],
            ['master_profile_id' => $master2->id, 'service_id' => 1, 'price' => 1000],
            ['master_profile_id' => $master3->id, 'service_id' => 1, 'price' => 2000],
        ]);

        $filters = [
            'sort_by' => 'price',
            'sort_order' => 'asc'
        ];

        // Act
        $result = $this->repository->search($filters, 10);

        // Assert
        $ids = $result->items()->pluck('id')->toArray();
        $this->assertEquals([$master2->id, $master3->id, $master1->id], $ids);
    }

    /**
     * Тест получения активных категорий
     */
    public function test_get_active_categories()
    {
        // Arrange
        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'category' => 'massage'
        ]);

        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'category' => 'spa'
        ]);

        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'category' => 'massage'
        ]);

        MasterProfile::factory()->create([
            'status' => MasterStatus::INACTIVE,
            'category' => 'fitness'
        ]);

        // Act
        $result = $this->repository->getActiveCategories();

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('massage'));
        $this->assertTrue($result->contains('spa'));
        $this->assertFalse($result->contains('fitness'));
    }

    /**
     * Тест получения диапазона цен
     */
    public function test_get_price_range()
    {
        // Arrange
        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'price_from' => 1000,
            'price_to' => 3000
        ]);

        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'price_from' => 500,
            'price_to' => 5000
        ]);

        // Act
        $result = $this->repository->getPriceRange();

        // Assert
        $this->assertEquals(500, $result['min']);
        $this->assertEquals(5000, $result['max']);
    }
}