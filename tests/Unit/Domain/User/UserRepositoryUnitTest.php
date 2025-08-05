<?php

namespace Tests\Unit\Domain\User;

use Tests\TestCase;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\Models\User;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Mockery;

/**
 * Чистые Unit тесты для UserRepository БЕЗ базы данных
 * 
 * Тестируем логику построения запросов с использованием моков
 */
class UserRepositoryUnitTest extends TestCase
{
    private UserRepository $repository;
    private $mockModel;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем мок модели
        $this->mockModel = Mockery::mock(User::class);
        $this->repository = new UserRepository($this->mockModel);
    }

    /**
     * Тест: findByEmail строит правильный запрос
     */
    public function test_findByEmail_builds_correct_query()
    {
        $email = 'test@example.com';
        $mockBuilder = Mockery::mock(Builder::class);
        
        // Мокаем цепочку вызовов
        $this->mockModel->shouldReceive('where')
            ->once()
            ->with('email', $email)
            ->andReturn($mockBuilder);
            
        $mockBuilder->shouldReceive('with')
            ->once()
            ->with(['profile', 'settings'])
            ->andReturn($mockBuilder);
            
        $mockBuilder->shouldReceive('first')
            ->once()
            ->andReturn(null);

        $result = $this->repository->findByEmail($email);

        $this->assertNull($result);
    }

    /**
     * Тест: findByEmail без отношений
     */
    public function test_findByEmail_without_relations()
    {
        $email = 'test@example.com';
        $mockBuilder = Mockery::mock(Builder::class);
        
        $this->mockModel->shouldReceive('where')
            ->once()
            ->with('email', $email)
            ->andReturn($mockBuilder);
            
        $mockBuilder->shouldReceive('first')
            ->once()
            ->andReturn(null);

        $result = $this->repository->findByEmail($email, false);

        $this->assertNull($result);
    }

    /**
     * Тест: findActive строит правильный запрос
     */
    public function test_findActive_builds_correct_query()
    {
        $mockBuilder = Mockery::mock(Builder::class);
        $mockCollection = new Collection();
        
        // Мокаем цепочку вызовов
        $this->mockModel->shouldReceive('where')
            ->once()
            ->with('status', UserStatus::ACTIVE)
            ->andReturn($mockBuilder);
            
        $mockBuilder->shouldReceive('with')
            ->once()
            ->with(['profile', 'settings'])
            ->andReturn($mockBuilder);
            
        $mockBuilder->shouldReceive('orderBy')
            ->once()
            ->with('created_at', 'desc')
            ->andReturn($mockBuilder);
            
        $mockBuilder->shouldReceive('limit')
            ->once()
            ->with(1000)
            ->andReturn($mockBuilder);
            
        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn($mockCollection);

        $result = $this->repository->findActive();

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * Тест: findActive с кастомным лимитом
     */
    public function test_findActive_with_custom_limit()
    {
        $mockBuilder = Mockery::mock(Builder::class);
        $mockCollection = new Collection();
        $customLimit = 50;
        
        $this->mockModel->shouldReceive('where')
            ->once()
            ->with('status', UserStatus::ACTIVE)
            ->andReturn($mockBuilder);
            
        $mockBuilder->shouldReceive('with')
            ->once()
            ->with(['profile', 'settings'])
            ->andReturn($mockBuilder);
            
        $mockBuilder->shouldReceive('orderBy')
            ->once()
            ->with('created_at', 'desc')
            ->andReturn($mockBuilder);
            
        $mockBuilder->shouldReceive('limit')
            ->once()
            ->with($customLimit)
            ->andReturn($mockBuilder);
            
        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn($mockCollection);

        $result = $this->repository->findActive($customLimit);

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * Тест: findWithProfile использует whereHas
     */
    public function test_findWithProfile_uses_whereHas()
    {
        $mockBuilder = Mockery::mock(Builder::class);
        $mockCollection = new Collection();
        
        $this->mockModel->shouldReceive('whereHas')
            ->once()
            ->with('profile')
            ->andReturn($mockBuilder);
            
        $mockBuilder->shouldReceive('with')
            ->once()
            ->with(['profile', 'settings'])
            ->andReturn($mockBuilder);
            
        $mockBuilder->shouldReceive('orderBy')
            ->once()
            ->with('created_at', 'desc')
            ->andReturn($mockBuilder);
            
        $mockBuilder->shouldReceive('limit')
            ->once()
            ->with(1000)
            ->andReturn($mockBuilder);
            
        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn($mockCollection);

        $result = $this->repository->findWithProfile();

        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * Тест: Все методы возвращают правильные типы
     */
    public function test_methods_return_correct_types()
    {
        // Настройка для findByEmail (возвращает null)
        $mockBuilder = Mockery::mock(Builder::class);
        $this->mockModel->shouldReceive('where')->andReturn($mockBuilder);
        $mockBuilder->shouldReceive('with')->andReturn($mockBuilder);
        $mockBuilder->shouldReceive('first')->andReturn(null);

        $result = $this->repository->findByEmail('test@example.com');
        $this->assertNull($result);

        // Настройка для findActive (возвращает Collection)
        $mockBuilder2 = Mockery::mock(Builder::class);
        $this->mockModel->shouldReceive('where')->andReturn($mockBuilder2);
        $mockBuilder2->shouldReceive('with')->andReturn($mockBuilder2);
        $mockBuilder2->shouldReceive('orderBy')->andReturn($mockBuilder2);
        $mockBuilder2->shouldReceive('limit')->andReturn($mockBuilder2);
        $mockBuilder2->shouldReceive('get')->andReturn(new Collection());

        $result = $this->repository->findActive();
        $this->assertInstanceOf(Collection::class, $result);
    }

    /**
     * Тест: Проверка безопасности от SQL инъекций
     */
    public function test_findByEmail_sanitizes_input()
    {
        $maliciousEmail = "test@example.com' OR '1'='1";
        $mockBuilder = Mockery::mock(Builder::class);
        
        // Проверяем, что email передается как параметр, а не конкатенируется
        $this->mockModel->shouldReceive('where')
            ->once()
            ->with('email', $maliciousEmail) // Laravel автоматически экранирует
            ->andReturn($mockBuilder);
            
        $mockBuilder->shouldReceive('with')
            ->once()
            ->andReturn($mockBuilder);
            
        $mockBuilder->shouldReceive('first')
            ->once()
            ->andReturn(null);

        $result = $this->repository->findByEmail($maliciousEmail);

        $this->assertNull($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}