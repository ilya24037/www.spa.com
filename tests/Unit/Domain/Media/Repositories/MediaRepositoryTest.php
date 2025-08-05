<?php

namespace Tests\Unit\Domain\Media\Repositories;

use Tests\TestCase;
use App\Domain\Media\Repositories\{
    MediaRepository,
    MediaCrudRepository,
    MediaStatisticsRepository,
    MediaManagementRepository
};
use App\Domain\Media\Models\Media;
use App\Enums\{MediaType, MediaStatus};
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Тест главного MediaRepository фасада согласно CLAUDE.md
 */
class MediaRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private MediaRepository $repository;
    private MediaCrudRepository $crudRepository;
    private MediaStatisticsRepository $statisticsRepository;
    private MediaManagementRepository $managementRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->crudRepository = new MediaCrudRepository(new Media());
        $this->statisticsRepository = new MediaStatisticsRepository(new Media());
        $this->managementRepository = new MediaManagementRepository(new Media());
        
        $this->repository = new MediaRepository(
            $this->crudRepository,
            $this->statisticsRepository,
            $this->managementRepository
        );
    }

    /** @test */
    public function it_delegates_crud_operations_correctly()
    {
        $testData = [
            'file_name' => 'test.jpg',
            'name' => 'Test Image',
            'type' => MediaType::IMAGE,
            'status' => MediaStatus::PROCESSED,
            'size' => 1024,
            'mime_type' => 'image/jpeg'
        ];
        
        // Тест создания
        $media = $this->repository->create($testData);
        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals('test.jpg', $media->file_name);
        
        // Тест поиска
        $found = $this->repository->find($media->id);
        $this->assertNotNull($found);
        $this->assertEquals($media->id, $found->id);
        
        // Тест поиска по имени файла
        $foundByName = $this->repository->findByFileName('test.jpg');
        $this->assertNotNull($foundByName);
        $this->assertEquals($media->id, $foundByName->id);
        
        // Тест обновления
        $updated = $this->repository->update($media->id, ['name' => 'Updated Image']);
        $this->assertTrue($updated);
        
        // Тест удаления
        $deleted = $this->repository->delete($media->id);
        $this->assertTrue($deleted);
    }

    /** @test */
    public function it_delegates_statistics_operations_correctly()
    {
        // Создаем тестовые данные
        Media::factory()->count(3)->create([
            'type' => MediaType::IMAGE,
            'status' => MediaStatus::PROCESSED,
            'size' => 1024
        ]);
        
        Media::factory()->count(2)->create([
            'type' => MediaType::VIDEO,
            'status' => MediaStatus::PENDING,
            'size' => 2048
        ]);
        
        // Тест общей статистики
        $stats = $this->repository->getStatistics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_files', $stats);
        $this->assertEquals(5, $stats['total_files']);
        
        // Тест топ файлов
        $topFiles = $this->repository->getTopLargestFiles(3);
        $this->assertCount(3, $topFiles);
        
        // Тест использования по коллекциям
        $usage = $this->repository->getUsageByCollection();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $usage);
        
        // Тест статистики обработки
        $processingStats = $this->repository->getProcessingStatistics();
        $this->assertIsArray($processingStats);
        $this->assertArrayHasKey('pending', $processingStats);
        $this->assertArrayHasKey('processed', $processingStats);
    }

    /** @test */
    public function it_delegates_management_operations_correctly()
    {
        $media = Media::factory()->count(3)->create(['status' => MediaStatus::PENDING]);
        $ids = $media->pluck('id')->toArray();
        
        // Тест поиска с фильтрами
        $results = $this->repository->search(['status' => MediaStatus::PENDING], 10);
        $this->assertGreaterThan(0, $results->total());
        
        // Тест массового обновления статуса
        $updated = $this->repository->batchUpdateStatus($ids, MediaStatus::PROCESSED);
        $this->assertEquals(3, $updated);
        
        // Тест массового удаления
        $deleted = $this->repository->batchDelete($ids);
        $this->assertEquals(3, $deleted);
        
        // Тест массового восстановления
        $restored = $this->repository->batchRestore($ids);
        $this->assertEquals(3, $restored);
    }

    /** @test */
    public function it_provides_backward_compatibility_methods()
    {
        // Тест deprecated методов
        Media::factory()->create(['status' => MediaStatus::PENDING]);
        Media::factory()->create(['status' => 'failed']);
        
        $oldestUnprocessed = $this->repository->getOldestUnprocessed(5);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $oldestUnprocessed);
        
        $failedProcessing = $this->repository->getFailedProcessing(5);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $failedProcessing);
        
        $duplicates = $this->repository->getDuplicatesByHash();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $duplicates);
        
        $optimizedQuery = $this->repository->optimizeQuery();
        $this->assertNotNull($optimizedQuery);
    }

    /** @test */
    public function it_handles_entity_related_operations()
    {
        $user = \App\Models\User::factory()->create();
        
        // Создаем медиа для пользователя
        $userMedia = Media::factory()->count(2)->create([
            'mediable_type' => get_class($user),
            'mediable_id' => $user->id,
            'collection_name' => 'avatars'
        ]);
        
        // Тест поиска медиа для сущности
        $found = $this->repository->findForEntity($user, 'avatars');
        $this->assertCount(2, $found);
        
        // Тест получения первого медиа
        $first = $this->repository->getFirstForEntity($user, 'avatars');
        $this->assertInstanceOf(Media::class, $first);
        
        // Тест подсчета медиа
        $count = $this->repository->countForEntity($user, 'avatars');
        $this->assertEquals(2, $count);
        
        // Тест переупорядочивания
        $mediaIds = $userMedia->pluck('id')->reverse()->toArray();
        $reordered = $this->repository->reorderForEntity($user, $mediaIds, 'avatars');
        $this->assertTrue($reordered);
    }

    /** @test */
    public function it_handles_type_and_status_filtering()
    {
        Media::factory()->create(['type' => MediaType::IMAGE, 'status' => MediaStatus::PROCESSED]);
        Media::factory()->create(['type' => MediaType::VIDEO, 'status' => MediaStatus::PENDING]);
        
        // Тест поиска по типу
        $images = $this->repository->findByType(MediaType::IMAGE);
        $this->assertGreaterThan(0, $images->count());
        
        $videos = $this->repository->findByType(MediaType::VIDEO, 5);
        $this->assertGreaterThan(0, $videos->count());
        
        // Тест поиска по статусу
        $processed = $this->repository->findByStatus(MediaStatus::PROCESSED);
        $this->assertGreaterThan(0, $processed->count());
        
        $pending = $this->repository->findByStatus(MediaStatus::PENDING, 10);
        $this->assertGreaterThan(0, $pending->count());
    }

    /** @test */
    public function it_handles_soft_delete_operations()
    {
        $media = Media::factory()->create();
        
        // Тест мягкого удаления
        $softDeleted = $this->repository->softDelete($media->id);
        $this->assertTrue($softDeleted);
        $this->assertSoftDeleted($media);
        
        // Тест восстановления
        $restored = $this->repository->restore($media->id);
        $this->assertTrue($restored);
        
        // Тест принудительного удаления
        $media->delete(); // Сначала мягко удаляем
        $forceDeleted = $this->repository->forceDelete($media->id);
        $this->assertTrue($forceDeleted);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    /** @test */
    public function it_handles_processing_queue_operations()
    {
        $pendingMedia = Media::factory()->create(['status' => MediaStatus::PENDING]);
        
        // Тест получения очереди обработки
        $queue = $this->repository->getProcessingQueue(10);
        $this->assertGreaterThan(0, $queue->count());
        
        // Тест пометки как "в обработке"
        $marked = $this->repository->markAsProcessing($pendingMedia->id);
        $this->assertTrue($marked);
        
        $this->assertDatabaseHas('media', [
            'id' => $pendingMedia->id,
            'status' => MediaStatus::PROCESSING
        ]);
        
        // Тест получения недавно добавленных
        $recent = $this->repository->getRecentlyAdded(7, 5);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $recent);
    }
}