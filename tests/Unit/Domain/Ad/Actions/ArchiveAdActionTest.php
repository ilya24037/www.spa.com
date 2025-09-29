<?php

namespace Tests\Unit\Domain\Ad\Actions;

use Tests\TestCase;
use App\Domain\Ad\Actions\ArchiveAdAction;
use App\Domain\Ad\Repositories\AdRepository;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Enums\AdStatus;
use Tests\Traits\SafeRefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Carbon\Carbon;

class ArchiveAdActionTest extends TestCase
{
    use SafeRefreshDatabase;

    private ArchiveAdAction $action;
    private AdRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(AdRepository::class);
        $this->action = new ArchiveAdAction($this->repository);

        Log::shouldReceive('info', 'error')->andReturnNull();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Тест успешной архивации активного объявления
     */
    public function test_execute_archives_active_ad_successfully()
    {
        // Arrange
        $ad = new Ad();
        $ad->id = 1;
        $ad->user_id = 1;
        $ad->status = AdStatus::ACTIVE->value;
        
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        $this->repository->shouldReceive('updateAd')
            ->once()
            ->with($ad, Mockery::on(function ($data) {
                return $data['status'] === AdStatus::ARCHIVED->value
                    && isset($data['archived_at']);
            }))
            ->andReturn($ad);

        // Act
        $result = $this->action->execute(1, 1);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Объявление перемещено в архив', $result['message']);
        $this->assertArrayHasKey('ad', $result);
    }

    /**
     * Тест успешной архивации черновика
     */
    public function test_execute_archives_draft_ad_successfully()
    {
        // Arrange
        $ad = new Ad();
        $ad->id = 1;
        $ad->user_id = 1;
        $ad->status = AdStatus::DRAFT->value;
        
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        $this->repository->shouldReceive('updateAd')
            ->once()
            ->with($ad, Mockery::on(function ($data) {
                return $data['status'] === AdStatus::ARCHIVED->value
                    && isset($data['archived_at']);
            }))
            ->andReturn($ad);

        // Act
        $result = $this->action->execute(1, 1);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Объявление перемещено в архив', $result['message']);
        $this->assertArrayHasKey('ad', $result);
    }

    /**
     * Тест отказа архивации - нет прав доступа
     */
    public function test_execute_fails_when_user_not_owner()
    {
        // Arrange
        $ad = new Ad();
        $ad->id = 1;
        $ad->user_id = 2; // Другой пользователь
        $ad->status = AdStatus::ACTIVE->value;
        
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        // Act
        $result = $this->action->execute(1, 1);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('У вас нет прав для архивирования этого объявления', $result['message']);
    }

    /**
     * Тест отказа архивации - объявление уже в архиве
     */
    public function test_execute_fails_when_ad_already_archived()
    {
        // Arrange
        $ad = new Ad();
        $ad->id = 1;
        $ad->user_id = 1;
        $ad->status = AdStatus::ARCHIVED->value;
        
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        // Act
        $result = $this->action->execute(1, 1);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Объявление уже в архиве', $result['message']);
    }

    /**
     * Тест успешной архивации объявления ожидающего оплаты
     */
    public function test_execute_archives_waiting_payment_ad_successfully()
    {
        // Arrange
        $ad = new Ad();
        $ad->id = 1;
        $ad->user_id = 1;
        $ad->status = AdStatus::WAITING_PAYMENT->value; // Скорее всего, это была причина ошибки
        
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        $this->repository->shouldReceive('updateAd')
            ->once()
            ->with($ad, Mockery::on(function ($data) {
                return $data['status'] === AdStatus::ARCHIVED->value
                    && isset($data['archived_at']);
            }))
            ->andReturn($ad);

        // Act
        $result = $this->action->execute(1, 1);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Объявление перемещено в архив', $result['message']);
        $this->assertArrayHasKey('ad', $result);
    }

    /**
     * Тест успешной архивации заблокированного объявления
     */
    public function test_execute_archives_blocked_ad_successfully()
    {
        // Arrange
        $ad = new Ad();
        $ad->id = 1;
        $ad->user_id = 1;
        $ad->status = AdStatus::BLOCKED->value; // Теперь можно архивировать из любого статуса
        
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        $this->repository->shouldReceive('updateAd')
            ->once()
            ->with($ad, Mockery::on(function ($data) {
                return $data['status'] === AdStatus::ARCHIVED->value
                    && isset($data['archived_at']);
            }))
            ->andReturn($ad);

        // Act
        $result = $this->action->execute(1, 1);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Объявление перемещено в архив', $result['message']);
        $this->assertArrayHasKey('ad', $result);
    }

    /**
     * Тест обработки исключений
     */
    public function test_execute_handles_exceptions()
    {
        // Arrange
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andThrow(new \Exception('Database error'));

        // Act
        $result = $this->action->execute(1, 1);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Ошибка при архивировании объявления', $result['message']);
    }

    /**
     * Тест логирования успешной архивации
     */
    public function test_execute_logs_successful_archiving()
    {
        // Arrange
        $ad = new Ad();
        $ad->id = 1;
        $ad->user_id = 1;
        $ad->status = AdStatus::ACTIVE->value;
        
        $this->repository->shouldReceive('findOrFail')->andReturn($ad);
        $this->repository->shouldReceive('updateAd')->andReturn($ad);

        Log::shouldReceive('info')
            ->once()
            ->with('Ad archived', [
                'ad_id' => 1,
                'user_id' => 1,
            ]);

        // Act
        $this->action->execute(1, 1);
    }

    /**
     * Тест логирования ошибок
     */
    public function test_execute_logs_errors()
    {
        // Arrange
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->andThrow(new \Exception('Database error'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to archive ad', [
                'ad_id' => 1,
                'user_id' => 1,
                'error' => 'Database error',
            ]);

        // Act
        $this->action->execute(1, 1);
    }
}