<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Domain\Master\Services\MasterService;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Media\Services\MasterMediaService;
use App\Infrastructure\Notification\NotificationService;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Models\User;
use App\Domain\Master\DTOs\CreateMasterDTO;
use App\Domain\Master\DTOs\UpdateMasterDTO;
use App\Domain\Master\DTOs\MasterFilterDTO;
use App\Enums\MasterStatus;
use App\Enums\MasterLevel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Carbon\Carbon;

class MasterServiceTest extends TestCase
{
    private MasterService $service;
    private $mockRepository;
    private $mockMediaService;
    private $mockNotificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(MasterRepository::class);
        $this->mockMediaService = Mockery::mock(MediaService::class);
        $this->mockNotificationService = Mockery::mock(NotificationService::class);

        $this->service = new MasterService(
            $this->mockRepository,
            $this->mockMediaService,
            $this->mockNotificationService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_master_profile()
    {
        Storage::fake('public');
        
        $dto = new CreateMasterDTO(
            user_id: 1,
            display_name: 'Test Master',
            bio: 'Test bio with more than 50 characters to meet requirements',
            phone: '+79123456789',
            city: 'Moscow',
            home_service: true,
            avatar: UploadedFile::fake()->image('avatar.jpg')
        );

        $master = new MasterProfile([
            'id' => 1,
            'user_id' => 1,
            'display_name' => 'Test Master',
            'slug' => 'test-master',
            'status' => MasterStatus::DRAFT,
            'level' => MasterLevel::BEGINNER
        ]);

        $this->mockRepository
            ->shouldReceive('findBySlug')
            ->andReturn(null);

        $this->mockRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($master);

        $master->shouldReceive('update')->once();

        $this->mockMediaService
            ->shouldReceive('uploadAvatar')
            ->once()
            ->andReturn((object)['path' => 'avatars/test.jpg']);

        $this->mockNotificationService
            ->shouldReceive('sendMasterProfileCreated')
            ->once();

        $result = $this->service->createProfile($dto);

        $this->assertInstanceOf(MasterProfile::class, $result);
        $this->assertEquals(MasterStatus::DRAFT, $result->status);
    }

    /** @test */
    public function it_can_update_master_profile()
    {
        $masterId = 1;
        $dto = new UpdateMasterDTO(
            display_name: 'Updated Name',
            bio: 'Updated bio with sufficient length to meet requirements'
        );

        $master = Mockery::mock(MasterProfile::class);
        $master->display_name = 'Old Name';

        $this->mockRepository
            ->shouldReceive('findById')
            ->once()
            ->with($masterId)
            ->andReturn($master);

        $this->mockRepository
            ->shouldReceive('findBySlug')
            ->andReturn(null);

        $this->mockRepository
            ->shouldReceive('update')
            ->once()
            ->with($master, Mockery::type('array'))
            ->andReturn(true);

        $this->mockRepository
            ->shouldReceive('updateLevel')
            ->once()
            ->with($master);

        $master->shouldReceive('fresh')->once()->andReturn($master);

        $result = $this->service->updateProfile($masterId, $dto);

        $this->assertInstanceOf(MasterProfile::class, $result);
    }

    /** @test */
    public function it_throws_exception_when_master_not_found_for_update()
    {
        $this->mockRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Профиль мастера не найден');

        $this->service->updateProfile(999, new UpdateMasterDTO());
    }

    /** @test */
    public function it_can_activate_profile()
    {
        $master = Mockery::mock(MasterProfile::class);
        $master->display_name = 'Test';
        $master->bio = 'Bio';
        $master->phone = '+79123456789';
        $master->city = 'Moscow';
        $master->experience_years = 1;
        $master->services = [1, 2];
        $master->home_service = true;
        $master->salon_service = false;

        $master->shouldReceive('photos->count')->andReturn(2);
        $master->shouldReceive('update')->once()->with(['status' => MasterStatus::PENDING]);

        $this->mockRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($master);

        $this->mockNotificationService
            ->shouldReceive('sendProfileForModeration')
            ->once();

        $result = $this->service->activateProfile(1);

        $this->assertInstanceOf(MasterProfile::class, $result);
    }

    /** @test */
    public function it_throws_exception_when_profile_not_ready_for_activation()
    {
        $master = Mockery::mock(MasterProfile::class);
        $master->display_name = null; // Missing required field

        $this->mockRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($master);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Профиль не готов к активации');

        $this->service->activateProfile(1);
    }

    /** @test */
    public function it_can_approve_profile()
    {
        $master = Mockery::mock(MasterProfile::class);
        $master->status = MasterStatus::PENDING;
        $moderator = new User(['id' => 1]);

        $master->shouldReceive('update')->once()->with([
            'status' => MasterStatus::ACTIVE,
            'is_verified' => true,
            'verified_at' => Mockery::type(Carbon::class),
            'verified_by' => 1
        ]);

        $this->mockRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($master);

        $this->mockNotificationService
            ->shouldReceive('sendProfileApproved')
            ->once();

        $result = $this->service->approveProfile(1, $moderator);

        $this->assertInstanceOf(MasterProfile::class, $result);
    }

    /** @test */
    public function it_can_reject_profile()
    {
        $master = Mockery::mock(MasterProfile::class);
        $master->status = MasterStatus::PENDING;
        $moderator = new User(['id' => 1]);
        $reason = 'Недостаточно информации';

        $master->shouldReceive('update')->once();

        $this->mockRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($master);

        $this->mockNotificationService
            ->shouldReceive('sendProfileRejected')
            ->once()
            ->with($master, $reason);

        $result = $this->service->rejectProfile(1, $reason, $moderator);

        $this->assertInstanceOf(MasterProfile::class, $result);
    }

    /** @test */
    public function it_can_set_vacation()
    {
        $master = Mockery::mock(MasterProfile::class);
        $master->status = MasterStatus::ACTIVE;
        $until = Carbon::now()->addWeek();

        $master->shouldReceive('status->canTransitionTo')
            ->with(MasterStatus::VACATION)
            ->andReturn(true);

        $master->shouldReceive('update')->once()->with([
            'status' => MasterStatus::VACATION,
            'vacation_until' => $until,
            'vacation_message' => 'В отпуске'
        ]);

        $this->mockRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($master);

        $result = $this->service->setVacation(1, $until, 'В отпуске');

        $this->assertInstanceOf(MasterProfile::class, $result);
    }

    /** @test */
    public function it_can_search_masters()
    {
        $filters = MasterFilterDTO::active();
        $masters = collect([new MasterProfile()]);

        $this->mockRepository
            ->shouldReceive('search')
            ->once()
            ->andReturn($masters);

        $result = $this->service->search($filters);

        $this->assertNotNull($result);
    }

    /** @test */
    public function it_can_get_master_by_slug()
    {
        $master = Mockery::mock(MasterProfile::class);
        $master->status = MasterStatus::ACTIVE;

        $master->shouldReceive('status->isPubliclyVisible')->andReturn(true);

        $this->mockRepository
            ->shouldReceive('findBySlug')
            ->once()
            ->with('test-slug')
            ->andReturn($master);

        $this->mockRepository
            ->shouldReceive('incrementViews')
            ->once()
            ->with($master);

        $result = $this->service->getBySlug('test-slug');

        $this->assertInstanceOf(MasterProfile::class, $result);
    }

    /** @test */
    public function it_returns_null_for_non_public_master()
    {
        $master = Mockery::mock(MasterProfile::class);
        $master->status = MasterStatus::DRAFT;

        $master->shouldReceive('status->isPubliclyVisible')->andReturn(false);

        $this->mockRepository
            ->shouldReceive('findBySlug')
            ->once()
            ->andReturn($master);

        $result = $this->service->getBySlug('test-slug');

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_update_premium_status()
    {
        $master = Mockery::mock(MasterProfile::class);
        $until = Carbon::now()->addMonth();

        $master->shouldReceive('update')->once()->with([
            'is_premium' => true,
            'premium_until' => $until
        ]);

        $this->mockRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($master);

        $result = $this->service->updatePremiumStatus(1, $until);

        $this->assertInstanceOf(MasterProfile::class, $result);
    }

    /** @test */
    public function it_generates_unique_slug()
    {
        $this->mockRepository
            ->shouldReceive('findBySlug')
            ->with('test-name')
            ->once()
            ->andReturn(new MasterProfile());

        $this->mockRepository
            ->shouldReceive('findBySlug')
            ->with('test-name-1')
            ->once()
            ->andReturn(null);

        $dto = new CreateMasterDTO(
            user_id: 1,
            display_name: 'Test Name',
            bio: 'Test bio with sufficient length',
            phone: '+79123456789',
            city: 'Moscow',
            home_service: true
        );

        $this->mockRepository
            ->shouldReceive('create')
            ->once()
            ->andReturnUsing(function($data) {
                $this->assertEquals('test-name-1', $data['slug']);
                return new MasterProfile($data);
            });

        $this->mockNotificationService
            ->shouldReceive('sendMasterProfileCreated')
            ->once();

        $this->service->createProfile($dto);
    }
}