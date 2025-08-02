<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Models\User;
use App\Enums\MasterStatus;
use App\Enums\MasterLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class MasterRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private MasterRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MasterRepository(new MasterProfile());
    }

    /** @test */
    public function it_can_create_master_profile()
    {
        $user = User::factory()->create();
        
        $data = [
            'user_id' => $user->id,
            'display_name' => 'Test Master',
            'slug' => 'test-master',
            'bio' => 'Test bio with more than 50 characters to pass validation requirements',
            'phone' => '+79123456789',
            'city' => 'Moscow',
            'status' => MasterStatus::DRAFT,
            'level' => MasterLevel::BEGINNER,
        ];

        $master = $this->repository->create($data);

        $this->assertInstanceOf(MasterProfile::class, $master);
        $this->assertEquals('Test Master', $master->display_name);
        $this->assertEquals($user->id, $master->user_id);
    }

    /** @test */
    public function it_can_find_master_by_id()
    {
        $user = User::factory()->create();
        $master = MasterProfile::factory()->create(['user_id' => $user->id]);

        $found = $this->repository->findById($master->id);

        $this->assertNotNull($found);
        $this->assertEquals($master->id, $found->id);
    }

    /** @test */
    public function it_can_find_master_by_user_id()
    {
        $user = User::factory()->create();
        $master = MasterProfile::factory()->create(['user_id' => $user->id]);

        $found = $this->repository->findByUserId($user->id);

        $this->assertNotNull($found);
        $this->assertEquals($master->id, $found->id);
    }

    /** @test */
    public function it_can_find_master_by_slug()
    {
        $master = MasterProfile::factory()->create(['slug' => 'test-slug']);

        $found = $this->repository->findBySlug('test-slug');

        $this->assertNotNull($found);
        $this->assertEquals($master->id, $found->id);
    }

    /** @test */
    public function it_can_update_master()
    {
        $master = MasterProfile::factory()->create();
        
        $updated = $this->repository->update($master, [
            'display_name' => 'Updated Name'
        ]);

        $this->assertTrue($updated);
        $this->assertEquals('Updated Name', $master->fresh()->display_name);
    }

    /** @test */
    public function it_can_get_active_masters()
    {
        MasterProfile::factory()->count(3)->create(['status' => MasterStatus::ACTIVE]);
        MasterProfile::factory()->count(2)->create(['status' => MasterStatus::DRAFT]);

        $active = $this->repository->getActive();

        $this->assertCount(3, $active);
        $this->assertTrue($active->every(fn($m) => $m->status === MasterStatus::ACTIVE));
    }

    /** @test */
    public function it_can_search_masters_with_filters()
    {
        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'city' => 'Moscow',
            'rating' => 4.5
        ]);

        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'city' => 'Petersburg',
            'rating' => 3.5
        ]);

        $results = $this->repository->search(['city' => 'Moscow']);

        $this->assertCount(1, $results->items());
        $this->assertEquals('Moscow', $results->items()[0]->city);
    }

    /** @test */
    public function it_can_get_top_masters()
    {
        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'rating' => 5.0,
            'reviews_count' => 100
        ]);

        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'rating' => 4.0,
            'reviews_count' => 50
        ]);

        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'rating' => 3.0,
            'reviews_count' => 10
        ]);

        $top = $this->repository->getTopMasters(2);

        $this->assertCount(2, $top);
        $this->assertEquals(5.0, $top->first()->rating);
        $this->assertEquals(4.0, $top->last()->rating);
    }

    /** @test */
    public function it_can_get_new_masters()
    {
        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'created_at' => now()->subDays(10)
        ]);

        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'created_at' => now()->subDays(40)
        ]);

        $new = $this->repository->getNewMasters();

        $this->assertCount(1, $new);
    }

    /** @test */
    public function it_can_update_rating()
    {
        $master = MasterProfile::factory()->create();
        
        // Создаем отзывы
        $master->reviews()->createMany([
            ['rating' => 5, 'user_id' => 1],
            ['rating' => 4, 'user_id' => 2],
            ['rating' => 5, 'user_id' => 3],
        ]);

        $this->repository->updateRating($master);

        $master->refresh();
        $this->assertEquals(4.67, $master->rating);
        $this->assertEquals(3, $master->reviews_count);
    }

    /** @test */
    public function it_can_update_level()
    {
        $master = MasterProfile::factory()->create([
            'experience_years' => 5,
            'rating' => 4.5,
            'reviews_count' => 50,
            'level' => MasterLevel::BEGINNER
        ]);

        $this->repository->updateLevel($master);

        $master->refresh();
        $this->assertNotEquals(MasterLevel::BEGINNER, $master->level);
    }

    /** @test */
    public function it_can_increment_views()
    {
        $master = MasterProfile::factory()->create(['views_count' => 10]);

        $this->repository->incrementViews($master);

        $this->assertEquals(11, $master->fresh()->views_count);
    }

    /** @test */
    public function it_can_search_by_service()
    {
        $master1 = MasterProfile::factory()->create(['status' => MasterStatus::ACTIVE]);
        $master2 = MasterProfile::factory()->create(['status' => MasterStatus::ACTIVE]);
        
        // Предполагаем, что у нас есть связь с услугами
        $serviceId = 1;
        $master1->services()->attach($serviceId);

        $results = $this->repository->getMastersByService($serviceId);

        $this->assertCount(1, $results);
        $this->assertEquals($master1->id, $results->first()->id);
    }

    /** @test */
    public function it_can_get_masters_by_city()
    {
        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'city' => 'Moscow'
        ]);

        MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'city' => 'Petersburg'
        ]);

        $results = $this->repository->getMastersByCity('Moscow');

        $this->assertCount(1, $results);
        $this->assertEquals('Moscow', $results->first()->city);
    }

    /** @test */
    public function it_can_get_pending_moderation()
    {
        MasterProfile::factory()->count(2)->create(['status' => MasterStatus::PENDING]);
        MasterProfile::factory()->create(['status' => MasterStatus::ACTIVE]);

        $pending = $this->repository->getPendingModeration();

        $this->assertCount(2, $pending);
        $this->assertTrue($pending->every(fn($m) => $m->status === MasterStatus::PENDING));
    }

    /** @test */
    public function it_can_batch_update_status()
    {
        $masters = MasterProfile::factory()->count(3)->create(['status' => MasterStatus::DRAFT]);
        $ids = $masters->pluck('id')->toArray();

        $updated = $this->repository->batchUpdateStatus($ids, MasterStatus::ACTIVE);

        $this->assertEquals(3, $updated);
        
        foreach ($masters as $master) {
            $this->assertEquals(MasterStatus::ACTIVE, $master->fresh()->status);
        }
    }

    /** @test */
    public function it_can_deactivate_inactive_masters()
    {
        // Создаем мастера без бронирований более 90 дней
        $inactiveMaster = MasterProfile::factory()->create([
            'status' => MasterStatus::ACTIVE,
            'created_at' => now()->subDays(100)
        ]);

        // Создаем активного мастера с недавним бронированием
        $activeMaster = MasterProfile::factory()->create(['status' => MasterStatus::ACTIVE]);
        $activeMaster->bookings()->create([
            'client_id' => 1,
            'service_id' => 1,
            'date' => now(),
            'created_at' => now()->subDays(10)
        ]);

        $deactivated = $this->repository->deactivateInactiveMasters();

        $this->assertEquals(1, $deactivated);
        $this->assertEquals(MasterStatus::INACTIVE, $inactiveMaster->fresh()->status);
        $this->assertEquals(MasterStatus::ACTIVE, $activeMaster->fresh()->status);
    }
}