<?php

namespace Tests\Unit\Domain\Media\Repositories;

use Tests\TestCase;
use App\Domain\Media\Repositories\MediaCrudRepository;
use App\Domain\Media\Models\Media;
use App\Enums\{MediaType, MediaStatus};
use Tests\Traits\SafeRefreshDatabase;
use Illuminate\Database\Eloquent\Collection;

/**
 * Тест MediaCrudRepository согласно CLAUDE.md
 */
class MediaCrudRepositoryTest extends TestCase
{
    use SafeRefreshDatabase;

    private MediaCrudRepository $repository;
    private Media $testMedia;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MediaCrudRepository(new Media());
        
        $this->testMedia = Media::factory()->create([
            'file_name' => 'test-image.jpg',
            'name' => 'Test Image',
            'type' => MediaType::IMAGE,
            'status' => MediaStatus::PROCESSED,
            'size' => 1024
        ]);
    }

    /** @test */
    public function it_can_find_media_by_filename()
    {
        $result = $this->repository->findByFileName('test-image.jpg');
        
        $this->assertNotNull($result);
        $this->assertEquals($this->testMedia->id, $result->id);
        $this->assertEquals('test-image.jpg', $result->file_name);
    }

    /** @test */
    public function it_returns_null_for_non_existent_filename()
    {
        $result = $this->repository->findByFileName('non-existent.jpg');
        
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_find_by_type()
    {
        Media::factory()->create(['type' => MediaType::VIDEO, 'status' => MediaStatus::PROCESSED]);
        
        $images = $this->repository->findByType(MediaType::IMAGE);
        $videos = $this->repository->findByType(MediaType::VIDEO);
        
        $this->assertInstanceOf(Collection::class, $images);
        $this->assertInstanceOf(Collection::class, $videos);
        $this->assertCount(1, $images);
        $this->assertCount(1, $videos);
    }

    /** @test */
    public function it_can_find_by_status()
    {
        Media::factory()->create(['status' => MediaStatus::PENDING]);
        
        $processed = $this->repository->findByStatus(MediaStatus::PROCESSED);
        $pending = $this->repository->findByStatus(MediaStatus::PENDING);
        
        $this->assertCount(1, $processed);
        $this->assertCount(1, $pending);
    }

    /** @test */
    public function it_can_soft_delete_media()
    {
        $result = $this->repository->softDelete($this->testMedia->id);
        
        $this->assertTrue($result);
        $this->assertSoftDeleted($this->testMedia);
    }

    /** @test */
    public function it_can_restore_soft_deleted_media()
    {
        $this->testMedia->delete();
        
        $result = $this->repository->restore($this->testMedia->id);
        
        $this->assertTrue($result);
        $this->assertDatabaseHas('media', [
            'id' => $this->testMedia->id,
            'deleted_at' => null
        ]);
    }

    /** @test */
    public function it_can_force_delete_media()
    {
        $this->testMedia->delete();
        
        $result = $this->repository->forceDelete($this->testMedia->id);
        
        $this->assertTrue($result);
        $this->assertDatabaseMissing('media', ['id' => $this->testMedia->id]);
    }

    /** @test */
    public function it_can_get_recently_added_media()
    {
        // Создаем старую медиа (более 7 дней назад)
        Media::factory()->create(['created_at' => now()->subDays(10)]);
        
        $recent = $this->repository->getRecentlyAdded(7, 10);
        
        $this->assertInstanceOf(Collection::class, $recent);
        $this->assertCount(1, $recent); // Только testMedia попадает в последние 7 дней
    }

    /** @test */
    public function it_can_get_processing_queue()
    {
        Media::factory()->create(['status' => MediaStatus::PENDING]);
        Media::factory()->create(['status' => MediaStatus::PENDING]);
        
        $queue = $this->repository->getProcessingQueue(10);
        
        $this->assertInstanceOf(Collection::class, $queue);
        $this->assertCount(2, $queue);
    }

    /** @test */
    public function it_can_mark_as_processing()
    {
        $pendingMedia = Media::factory()->create(['status' => MediaStatus::PENDING]);
        
        $result = $this->repository->markAsProcessing($pendingMedia->id);
        
        $this->assertTrue($result);
        $this->assertDatabaseHas('media', [
            'id' => $pendingMedia->id,
            'status' => MediaStatus::PROCESSING
        ]);
    }

    /** @test */
    public function it_can_batch_update_status()
    {
        $media1 = Media::factory()->create(['status' => MediaStatus::PENDING]);
        $media2 = Media::factory()->create(['status' => MediaStatus::PENDING]);
        
        $updated = $this->repository->batchUpdateStatus(
            [$media1->id, $media2->id], 
            MediaStatus::PROCESSED
        );
        
        $this->assertEquals(2, $updated);
        $this->assertDatabaseHas('media', ['id' => $media1->id, 'status' => MediaStatus::PROCESSED]);
        $this->assertDatabaseHas('media', ['id' => $media2->id, 'status' => MediaStatus::PROCESSED]);
    }

    /** @test */
    public function it_can_batch_delete()
    {
        $media1 = Media::factory()->create();
        $media2 = Media::factory()->create();
        
        $deleted = $this->repository->batchDelete([$media1->id, $media2->id]);
        
        $this->assertEquals(2, $deleted);
        $this->assertSoftDeleted($media1);
        $this->assertSoftDeleted($media2);
    }

    /** @test */
    public function it_handles_errors_gracefully()
    {
        // Тестируем обработку ошибок при несуществующем ID
        $result = $this->repository->softDelete(99999);
        $this->assertFalse($result);
        
        $result = $this->repository->restore(99999);
        $this->assertFalse($result);
        
        $result = $this->repository->forceDelete(99999);
        $this->assertFalse($result);
    }
}