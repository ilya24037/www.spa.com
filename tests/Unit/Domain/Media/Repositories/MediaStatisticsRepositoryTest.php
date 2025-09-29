<?php

namespace Tests\Unit\Domain\Media\Repositories;

use Tests\TestCase;
use App\Domain\Media\Repositories\MediaStatisticsRepository;
use App\Domain\Media\Models\Media;
use App\Enums\{MediaType, MediaStatus};
use Tests\Traits\SafeRefreshDatabase;
use Illuminate\Database\Eloquent\Collection;

/**
 * Тест MediaStatisticsRepository согласно CLAUDE.md
 */
class MediaStatisticsRepositoryTest extends TestCase
{
    use SafeRefreshDatabase;

    private MediaStatisticsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MediaStatisticsRepository(new Media());
        
        // Создаем тестовые данные
        Media::factory()->create([
            'type' => MediaType::IMAGE,
            'status' => MediaStatus::PROCESSED,
            'size' => 1024,
            'created_at' => now()->subDays(1)
        ]);
        
        Media::factory()->create([
            'type' => MediaType::VIDEO,
            'status' => MediaStatus::PENDING,
            'size' => 2048,
            'created_at' => now()->subDays(2)
        ]);
        
        Media::factory()->create([
            'type' => MediaType::IMAGE,
            'status' => MediaStatus::FAILED,
            'size' => 512,
            'created_at' => now()->subDays(3)
        ]);
    }

    /** @test */
    public function it_can_get_general_statistics()
    {
        $stats = $this->repository->getStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_files', $stats);
        $this->assertArrayHasKey('total_size', $stats);
        $this->assertArrayHasKey('average_size', $stats);
        $this->assertArrayHasKey('total_size_mb', $stats);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertArrayHasKey('by_status', $stats);
        
        $this->assertEquals(3, $stats['total_files']);
        $this->assertEquals(3584, $stats['total_size']); // 1024 + 2048 + 512
        $this->assertEquals(3.5, $stats['total_size_mb']); // округлено
    }

    /** @test */
    public function it_can_get_top_largest_files()
    {
        $largest = $this->repository->getTopLargestFiles(2);
        
        $this->assertInstanceOf(Collection::class, $largest);
        $this->assertCount(2, $largest);
        
        // Проверяем сортировку по размеру (по убыванию)
        $sizes = $largest->pluck('size')->toArray();
        $this->assertEquals([2048, 1024], $sizes);
    }

    /** @test */
    public function it_can_get_usage_by_collection()
    {
        // Создаем медиа с коллекциями
        Media::factory()->create([
            'collection_name' => 'avatars',
            'size' => 100
        ]);
        Media::factory()->create([
            'collection_name' => 'avatars',
            'size' => 200
        ]);
        Media::factory()->create([
            'collection_name' => 'gallery',
            'size' => 300
        ]);
        
        $usage = $this->repository->getUsageByCollection();
        
        $this->assertInstanceOf(Collection::class, $usage);
        $this->assertCount(2, $usage);
        
        $avatars = $usage->firstWhere('collection_name', 'avatars');
        $this->assertEquals(2, $avatars->files_count);
        $this->assertEquals(300, $avatars->total_size);
    }

    /** @test */
    public function it_can_get_usage_by_entity_type()
    {
        // Создаем медиа с разными типами сущностей
        Media::factory()->create([
            'mediable_type' => 'App\\Models\\User',
            'mediable_id' => 1,
            'size' => 100
        ]);
        Media::factory()->create([
            'mediable_type' => 'App\\Models\\User',
            'mediable_id' => 2,
            'size' => 200
        ]);
        Media::factory()->create([
            'mediable_type' => 'App\\Models\\Ad',
            'mediable_id' => 1,
            'size' => 300
        ]);
        
        $usage = $this->repository->getUsageByEntityType();
        
        $this->assertInstanceOf(Collection::class, $usage);
        $this->assertCount(2, $usage);
        
        $userMedia = $usage->firstWhere('mediable_type', 'App\\Models\\User');
        $this->assertEquals(2, $userMedia->files_count);
        $this->assertEquals(300, $userMedia->total_size);
    }

    /** @test */
    public function it_can_get_usage_by_period()
    {
        $usage = $this->repository->getUsageByPeriod('day');
        
        $this->assertInstanceOf(Collection::class, $usage);
        $this->assertCount(3, $usage); // 3 разных дня
        
        // Проверяем структуру данных
        $firstDay = $usage->first();
        $this->assertArrayHasKey('period', $firstDay);
        $this->assertArrayHasKey('files_count', $firstDay);
        $this->assertArrayHasKey('total_size', $firstDay);
    }

    /** @test */
    public function it_can_get_processing_statistics()
    {
        $stats = $this->repository->getProcessingStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('processing', $stats);
        $this->assertArrayHasKey('processed', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('success_rate', $stats);
        $this->assertArrayHasKey('avg_processing_time_seconds', $stats);
        
        $this->assertEquals(1, $stats['pending']);
        $this->assertEquals(1, $stats['processed']);
        $this->assertEquals(1, $stats['failed']);
        $this->assertEquals(3, $stats['total']);
    }

    /** @test */
    public function it_can_get_detailed_analytics()
    {
        $analytics = $this->repository->getDetailedAnalytics(30);
        
        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('period_days', $analytics);
        $this->assertArrayHasKey('total_uploaded', $analytics);
        $this->assertArrayHasKey('total_size_mb', $analytics);
        $this->assertArrayHasKey('daily_average', $analytics);
        $this->assertArrayHasKey('daily_stats', $analytics);
        
        $this->assertEquals(30, $analytics['period_days']);
        $this->assertEquals(3, $analytics['total_uploaded']);
        $this->assertIsArray($analytics['daily_stats']);
    }

    /** @test */
    public function it_handles_errors_gracefully()
    {
        // Мокаем ошибку в модели
        $mockModel = $this->createMock(Media::class);
        $mockModel->method('count')->willThrowException(new \Exception('Database error'));
        
        $repository = new MediaStatisticsRepository($mockModel);
        $stats = $repository->getStatistics();
        
        // Проверяем, что возвращаются значения по умолчанию
        $this->assertEquals(0, $stats['total_files']);
        $this->assertEquals(0, $stats['total_size']);
        $this->assertIsArray($stats['by_type']);
        $this->assertIsArray($stats['by_status']);
    }

    /** @test */
    public function it_can_handle_empty_collections()
    {
        // Удаляем все медиа
        Media::query()->delete();
        
        $stats = $this->repository->getStatistics();
        $largest = $this->repository->getTopLargestFiles();
        $usage = $this->repository->getUsageByCollection();
        
        $this->assertEquals(0, $stats['total_files']);
        $this->assertInstanceOf(Collection::class, $largest);
        $this->assertCount(0, $largest);
        $this->assertInstanceOf(Collection::class, $usage);
        $this->assertCount(0, $usage);
    }
}