<?php

namespace Tests\Unit\Support\Services;

use Tests\TestCase;
use App\Support\Services\BaseService;
use App\Support\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Tests\Traits\SafeRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Mockery;

class BaseServiceTest extends TestCase
{
    use SafeRefreshDatabase;

    private $mockRepository;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = Mockery::mock(RepositoryInterface::class);
        $this->service = new TestService($this->mockRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_returns_repository_instance()
    {
        $result = $this->service->getRepository();
        
        $this->assertEquals($this->mockRepository, $result);
    }

    /** @test */
    public function it_can_find_a_record_with_caching()
    {
        $id = 1;
        $expectedModel = new TestModel(['id' => $id]);
        
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($expectedModel);

        $result = $this->service->find($id);

        $this->assertEquals($expectedModel, $result);
    }

    /** @test */
    public function it_can_create_a_record_with_transaction_and_logging()
    {
        $data = ['name' => 'Test Name'];
        $expectedModel = new TestModel($data);
        
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        
        $this->mockRepository
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andReturn($expectedModel);
            
        Cache::shouldReceive('forget')->twice(); // clearCache вызывает forget
        
        Log::shouldReceive('info')
            ->once()
            ->with(
                'TestService: Record created',
                ['id' => $expectedModel->id]
            );

        $result = $this->service->create($data);

        $this->assertEquals($expectedModel, $result);
    }

    /** @test */
    public function it_rolls_back_transaction_on_create_failure()
    {
        $data = ['name' => 'Test Name'];
        $exception = new \Exception('Test exception');
        
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        
        $this->mockRepository
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andThrow($exception);
            
        Log::shouldReceive('error')
            ->once()
            ->with(
                'TestService: Failed to create record',
                ['error' => 'Test exception']
            );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->service->create($data);
    }

    /** @test */
    public function it_can_update_a_record_with_transaction_and_cache_clearing()
    {
        $id = 1;
        $data = ['name' => 'Updated Name'];
        
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        
        $this->mockRepository
            ->shouldReceive('update')
            ->with($id, $data)
            ->once()
            ->andReturn(true);
            
        Cache::shouldReceive('forget')->times(3); // clearCache + clearCacheKey
        
        Log::shouldReceive('info')
            ->once()
            ->with(
                'TestService: Record updated',
                ['id' => $id]
            );

        $result = $this->service->update($id, $data);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_delete_a_record_with_transaction()
    {
        $id = 1;
        
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        
        $this->mockRepository
            ->shouldReceive('delete')
            ->with($id)
            ->once()
            ->andReturn(true);
            
        Cache::shouldReceive('forget')->times(3); // clearCache + clearCacheKey
        
        Log::shouldReceive('info')
            ->once()
            ->with(
                'TestService: Record deleted',
                ['id' => $id]
            );

        $result = $this->service->delete($id);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_get_all_records_with_caching()
    {
        $expectedCollection = new Collection([
            new TestModel(['id' => 1]),
            new TestModel(['id' => 2])
        ]);
        
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($expectedCollection);

        $result = $this->service->all();

        $this->assertEquals($expectedCollection, $result);
    }

    /** @test */
    public function it_can_paginate_records()
    {
        $perPage = 10;
        $mockPaginator = Mockery::mock();
        
        $this->mockRepository
            ->shouldReceive('paginate')
            ->with($perPage)
            ->once()
            ->andReturn($mockPaginator);

        $result = $this->service->paginate($perPage);

        $this->assertEquals($mockPaginator, $result);
    }

    /** @test */
    public function it_generates_correct_cache_keys()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getCacheKey');
        $method->setAccessible(true);

        $key = $method->invoke($this->service, 'find', 1, 'test');

        $this->assertEquals('TestService:find:1:test', $key);
    }
}

// Тестовый сервис для тестирования BaseService
class TestService extends BaseService
{
    // Конкретная реализация для тестов
}

// Тестовая модель для тестов
class TestModel extends Model
{
    protected $fillable = ['id', 'name', 'email'];
    
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        // Устанавливаем id если он передан в атрибутах
        if (isset($attributes['id'])) {
            $this->id = $attributes['id'];
        }
    }
}