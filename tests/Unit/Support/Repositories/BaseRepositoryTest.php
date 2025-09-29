<?php

namespace Tests\Unit\Support\Repositories;

use Tests\TestCase;
use App\Support\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Tests\Traits\SafeRefreshDatabase;
use Mockery;

class BaseRepositoryTest extends TestCase
{
    use SafeRefreshDatabase;

    private $mockModel;
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockModel = Mockery::mock(Model::class);
        $this->repository = new TestRepository($this->mockModel);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_find_a_record_by_id()
    {
        $id = 1;
        $expectedModel = new TestModel(['id' => $id]);
        
        $this->mockModel
            ->shouldReceive('find')
            ->with($id)
            ->once()
            ->andReturn($expectedModel);

        $result = $this->repository->find($id);

        $this->assertEquals($expectedModel, $result);
    }

    /** @test */
    public function it_returns_null_when_record_not_found()
    {
        $id = 999;
        
        $this->mockModel
            ->shouldReceive('find')
            ->with($id)
            ->once()
            ->andReturn(null);

        $result = $this->repository->find($id);

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_find_or_fail_a_record()
    {
        $id = 1;
        $expectedModel = new TestModel(['id' => $id]);
        
        $this->mockModel
            ->shouldReceive('findOrFail')
            ->with($id)
            ->once()
            ->andReturn($expectedModel);

        $result = $this->repository->findOrFail($id);

        $this->assertEquals($expectedModel, $result);
    }

    /** @test */
    public function it_can_get_all_records()
    {
        $expectedCollection = new Collection([
            new TestModel(['id' => 1]),
            new TestModel(['id' => 2])
        ]);
        
        $this->mockModel
            ->shouldReceive('all')
            ->once()
            ->andReturn($expectedCollection);

        $result = $this->repository->all();

        $this->assertEquals($expectedCollection, $result);
    }

    /** @test */
    public function it_can_create_a_record()
    {
        $data = ['name' => 'Test Name', 'email' => 'test@example.com'];
        $expectedModel = new TestModel($data);
        
        $this->mockModel
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andReturn($expectedModel);

        $result = $this->repository->create($data);

        $this->assertEquals($expectedModel, $result);
    }

    /** @test */
    public function it_can_update_a_record()
    {
        $id = 1;
        $data = ['name' => 'Updated Name'];
        $model = Mockery::mock(Model::class);
        
        $this->mockModel
            ->shouldReceive('findOrFail')
            ->with($id)
            ->once()
            ->andReturn($model);
            
        $model
            ->shouldReceive('update')
            ->with($data)
            ->once()
            ->andReturn(true);

        $result = $this->repository->update($id, $data);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_delete_a_record()
    {
        $id = 1;
        $model = Mockery::mock(Model::class);
        
        $this->mockModel
            ->shouldReceive('findOrFail')
            ->with($id)
            ->once()
            ->andReturn($model);
            
        $model
            ->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        $result = $this->repository->delete($id);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_count_records()
    {
        $expectedCount = 5;
        
        $this->mockModel
            ->shouldReceive('count')
            ->once()
            ->andReturn($expectedCount);

        $result = $this->repository->count();

        $this->assertEquals($expectedCount, $result);
    }

    /** @test */
    public function it_can_check_if_record_exists()
    {
        $id = 1;
        
        $this->mockModel
            ->shouldReceive('where')
            ->with('id', $id)
            ->once()
            ->andReturnSelf();
            
        $this->mockModel
            ->shouldReceive('exists')
            ->once()
            ->andReturn(true);

        $result = $this->repository->exists($id);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_find_records_by_field()
    {
        $field = 'status';
        $value = 'active';
        $expectedCollection = new Collection([new TestModel(['status' => 'active'])]);
        
        $this->mockModel
            ->shouldReceive('where')
            ->with($field, $value)
            ->once()
            ->andReturnSelf();
            
        $this->mockModel
            ->shouldReceive('get')
            ->once()
            ->andReturn($expectedCollection);

        $result = $this->repository->findBy($field, $value);

        $this->assertEquals($expectedCollection, $result);
    }

    /** @test */
    public function it_can_find_one_record_by_field()
    {
        $field = 'email';
        $value = 'test@example.com';
        $expectedModel = new TestModel(['email' => $value]);
        
        $this->mockModel
            ->shouldReceive('where')
            ->with($field, $value)
            ->once()
            ->andReturnSelf();
            
        $this->mockModel
            ->shouldReceive('first')
            ->once()
            ->andReturn($expectedModel);

        $result = $this->repository->findOneBy($field, $value);

        $this->assertEquals($expectedModel, $result);
    }
}

// Тестовый репозиторий для тестирования BaseRepository
class TestRepository extends BaseRepository
{
    // Конкретная реализация для тестов
    
    public function paginate(int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }
    
    public function findBy(string $field, $value): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->where($field, $value)->get();
    }
    
    public function findOneBy(string $field, $value): ?Model
    {
        return $this->model->where($field, $value)->first();
    }
    
    public function whereIn(string $field, array $values): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->whereIn($field, $values)->get();
    }
    
    public function whereBetween(string $field, array $values): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->whereBetween($field, $values)->get();
    }
    
    public function orderBy(string $field, string $direction = 'asc'): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->orderBy($field, $direction)->get();
    }
    
    public function with($relations): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->with($relations)->get();
    }
    
    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }
    
    public function count(): int
    {
        return $this->model->count();
    }
    
    public function where(string $field, $operator, $value = null): \Illuminate\Database\Eloquent\Collection
    {
        // Если value не передан, то operator является значением для сравнения '='
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        return $this->model->where($field, $operator, $value)->get();
    }
    
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->updateOrCreate($attributes, $values);
    }
}

// Тестовая модель для тестов
class TestModel extends Model
{
    protected $fillable = ['id', 'name', 'email', 'status'];
}