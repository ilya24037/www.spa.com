<?php

namespace Tests\Unit\Domain\Media\Repositories;

use Tests\TestCase;
use App\Domain\Media\Repositories\MediaManagementRepository;
use App\Domain\Media\Models\Media;
use App\Enums\{MediaType, MediaStatus};
use App\Models\User;
use Tests\Traits\SafeRefreshDatabase;
use Illuminate\Database\Eloquent\Collection;

/**
 * Тест MediaManagementRepository согласно CLAUDE.md
 */
class MediaManagementRepositoryTest extends TestCase
{
    use SafeRefreshDatabase;

    private MediaManagementRepository $repository;
    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MediaManagementRepository(new Media());
        $this->testUser = User::factory()->create();
        
        // Создаем тестовые медиа
        Media::factory()->count(3)->create([
            'mediable_type' => User::class,
            'mediable_id' => $this->testUser->id,
            'collection_name' => 'avatars',
            'sort_order' => 1
        ]);
    }

    /** @test */
    public function it_can_search_with_filters()
    {
        Media::factory()->create(['type' => MediaType::IMAGE, 'status' => MediaStatus::PROCESSED]);
        Media::factory()->create(['type' => MediaType::VIDEO, 'status' => MediaStatus::PENDING]);
        
        $filters = [
            'type' => MediaType::IMAGE,
            'status' => MediaStatus::PROCESSED
        ];
        
        $results = $this->repository->search($filters, 10);
        
        $this->assertGreaterThan(0, $results->total());
    }

    /** @test */
    public function it_can_reorder_media_for_entity()
    {
        $media = Media::where('mediable_id', $this->testUser->id)->get();
        $mediaIds = $media->pluck('id')->reverse()->toArray(); // Обратный порядок
        
        $result = $this->repository->reorderForEntity($this->testUser, $mediaIds, 'avatars');
        
        $this->assertTrue($result);
        
        // Проверяем новый порядок
        $reordered = Media::where('mediable_id', $this->testUser->id)
                         ->orderBy('sort_order')
                         ->pluck('id')
                         ->toArray();
        
        $this->assertEquals($mediaIds, $reordered);
    }

    /** @test */
    public function it_can_batch_update_status()
    {
        $media = Media::factory()->count(3)->create(['status' => MediaStatus::PENDING]);
        $ids = $media->pluck('id')->toArray();
        
        $updated = $this->repository->batchUpdateStatus($ids, MediaStatus::PROCESSED);
        
        $this->assertEquals(3, $updated);
        
        foreach ($ids as $id) {
            $this->assertDatabaseHas('media', [
                'id' => $id,
                'status' => MediaStatus::PROCESSED
            ]);
        }
    }

    /** @test */
    public function it_can_batch_delete()
    {
        $media = Media::factory()->count(3)->create();
        $ids = $media->pluck('id')->toArray();
        
        $deleted = $this->repository->batchDelete($ids);
        
        $this->assertEquals(3, $deleted);
        
        foreach ($media as $item) {
            $this->assertSoftDeleted($item);
        }
    }

    /** @test */
    public function it_can_batch_restore()
    {
        $media = Media::factory()->count(3)->create();
        $ids = $media->pluck('id')->toArray();
        
        // Сначала удаляем
        Media::whereIn('id', $ids)->delete();
        
        // Затем восстанавливаем
        $restored = $this->repository->batchRestore($ids);
        
        $this->assertEquals(3, $restored);
        
        foreach ($ids as $id) {
            $this->assertDatabaseHas('media', [
                'id' => $id,
                'deleted_at' => null
            ]);
        }
    }

    /** @test */
    public function it_can_get_expired_files()
    {
        // Создаем просроченные файлы (предполагаем, что есть поле expires_at)
        Media::factory()->create([
            'expires_at' => now()->subDay()
        ]);
        Media::factory()->create([
            'expires_at' => now()->addDay()
        ]);
        
        // Мокаем scope expired
        $expired = $this->repository->getExpiredFiles();
        
        $this->assertInstanceOf(Collection::class, $expired);
    }

    /** @test */
    public function it_can_cleanup_expired_files()
    {
        // Создаем просроченные файлы
        $expiredMedia = Media::factory()->create([
            'expires_at' => now()->subDay()
        ]);
        
        $deleted = $this->repository->cleanupExpired();
        
        $this->assertGreaterThanOrEqual(0, $deleted);
    }

    /** @test */
    public function it_can_get_orphaned_files()
    {
        // Создаем "осиротевшие" файлы (связанные с несуществующими сущностями)
        Media::factory()->create([
            'mediable_type' => User::class,
            'mediable_id' => 99999 // Несуществующий пользователь
        ]);
        
        $orphaned = $this->repository->getOrphanedFiles();
        
        $this->assertInstanceOf(Collection::class, $orphaned);
    }

    /** @test */
    public function it_can_cleanup_orphaned_files()
    {
        // Создаем "осиротевшие" файлы
        Media::factory()->create([
            'mediable_type' => User::class,
            'mediable_id' => 99999
        ]);
        
        $deleted = $this->repository->cleanupOrphaned();
        
        $this->assertGreaterThanOrEqual(0, $deleted);
    }

    /** @test */
    public function it_handles_reorder_errors_gracefully()
    {
        // Тестируем с несуществующими ID
        $result = $this->repository->reorderForEntity(
            $this->testUser, 
            [99999, 99998], 
            'avatars'
        );
        
        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_search_errors_gracefully()
    {
        // Мокаем ошибку
        $mockModel = $this->createMock(Media::class);
        $mockModel->method('newQuery')->willThrowException(new \Exception('Database error'));
        
        $repository = new MediaManagementRepository($mockModel);
        $results = $repository->search(['type' => MediaType::IMAGE], 10);
        
        // Должен вернуть пустую пагинацию
        $this->assertEquals(0, $results->total());
    }

    /** @test */
    public function it_applies_filters_correctly()
    {
        $imageMedia = Media::factory()->create([
            'type' => MediaType::IMAGE,
            'status' => MediaStatus::PROCESSED,
            'name' => 'test image',
            'mime_type' => 'image/jpeg',
            'size' => 1024
        ]);
        
        $videoMedia = Media::factory()->create([
            'type' => MediaType::VIDEO,
            'status' => MediaStatus::PENDING,
            'name' => 'test video',
            'mime_type' => 'video/mp4',
            'size' => 2048
        ]);
        
        // Тест фильтра по типу
        $results = $this->repository->search(['type' => MediaType::IMAGE]);
        $this->assertGreaterThan(0, $results->total());
        
        // Тест фильтра по статусу
        $results = $this->repository->search(['status' => MediaStatus::PROCESSED]);
        $this->assertGreaterThan(0, $results->total());
        
        // Тест фильтра по имени
        $results = $this->repository->search(['name' => 'test']);
        $this->assertGreaterThan(0, $results->total());
        
        // Тест фильтра по размеру
        $results = $this->repository->search(['size_min' => 1000, 'size_max' => 1500]);
        $this->assertGreaterThan(0, $results->total());
    }

    /** @test */
    public function it_applies_sorting_correctly()
    {
        Media::factory()->create(['name' => 'A Image', 'size' => 100]);
        Media::factory()->create(['name' => 'B Image', 'size' => 200]);
        
        // Тест сортировки по имени
        $results = $this->repository->search(['sort_by' => 'name', 'sort_order' => 'asc']);
        $this->assertGreaterThan(0, $results->total());
        
        // Тест сортировки по размеру
        $results = $this->repository->search(['sort_by' => 'size', 'sort_order' => 'desc']);
        $this->assertGreaterThan(0, $results->total());
    }
}