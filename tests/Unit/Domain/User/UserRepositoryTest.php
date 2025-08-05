<?php

namespace Tests\Unit\Domain\User;

use Tests\UnitTestCase;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Mockery;

/**
 * ЧИСТЫЕ Unit тесты для UserRepository БЕЗ базы данных
 * 
 * Тестируем ТОЛЬКО методы из плана:
 * - findByEmail()
 * - findActive() 
 * - findWithProfile()
 */
class UserRepositoryTest extends UnitTestCase
{
    private UserRepository $repository;
    private $mockModel;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockModel = Mockery::mock(User::class);
        $this->repository = new UserRepository($this->mockModel);
    }

    /**
     * Тест 1: findByEmail вызывает правильный запрос
     */
    public function test_findByEmail_calls_correct_query()
    {
        $email = 'test@example.com';
        $mockBuilder = Mockery::mock(Builder::class);
        $mockUser = Mockery::mock(User::class);

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
            ->andReturn($mockUser);

        $result = $this->repository->findByEmail($email);

        $this->assertEquals($mockUser, $result);
    }

    /**
     * Тест 2: findByEmail без relations
     */
    public function test_findByEmail_without_relations()
    {
        $email = 'test@example.com';
        $mockBuilder = Mockery::mock(Builder::class);
        $mockUser = Mockery::mock(User::class);

        $this->mockModel->shouldReceive('where')
            ->once()
            ->with('email', $email)
            ->andReturn($mockBuilder);

        $mockBuilder->shouldReceive('first')
            ->once()
            ->andReturn($mockUser);

        $result = $this->repository->findByEmail($email, false);

        $this->assertEquals($mockUser, $result);
    }

    /**
     * Тест 3: findActive вызывает правильный запрос
     */
    public function test_findActive_calls_correct_query()
    {
        $mockBuilder = Mockery::mock(Builder::class);
        $mockCollection = Mockery::mock(Collection::class);

        $this->mockModel->shouldReceive('where')
            ->once()
            ->with('status', 'active')
            ->andReturn($mockBuilder);

        $mockBuilder->shouldReceive('limit')
            ->once()
            ->with(1000)
            ->andReturn($mockBuilder);

        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn($mockCollection);

        $result = $this->repository->findActive();

        $this->assertEquals($mockCollection, $result);
    }

    /**
     * Тест 4: findActive с кастомным лимитом
     */
    public function test_findActive_with_custom_limit()
    {
        $customLimit = 500;
        $mockBuilder = Mockery::mock(Builder::class);
        $mockCollection = Mockery::mock(Collection::class);

        $this->mockModel->shouldReceive('where')
            ->once()
            ->with('status', 'active')
            ->andReturn($mockBuilder);

        $mockBuilder->shouldReceive('limit')
            ->once()
            ->with($customLimit)
            ->andReturn($mockBuilder);

        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn($mockCollection);

        $result = $this->repository->findActive($customLimit);

        $this->assertEquals($mockCollection, $result);
    }

    /**
     * Тест 5: findWithProfile вызывает правильный запрос
     */
    public function test_findWithProfile_calls_correct_query()
    {
        $mockBuilder = Mockery::mock(Builder::class);
        $mockCollection = Mockery::mock(Collection::class);

        $this->mockModel->shouldReceive('with')
            ->once()
            ->with(['profile'])
            ->andReturn($mockBuilder);

        $mockBuilder->shouldReceive('whereHas')
            ->once()
            ->with('profile')
            ->andReturn($mockBuilder);

        $mockBuilder->shouldReceive('limit')
            ->once()
            ->with(1000)
            ->andReturn($mockBuilder);

        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn($mockCollection);

        $result = $this->repository->findWithProfile();

        $this->assertEquals($mockCollection, $result);
    }

    /**
     * Тест 6: findWithProfile с кастомным лимитом
     */
    public function test_findWithProfile_with_custom_limit()
    {
        $customLimit = 250;
        $mockBuilder = Mockery::mock(Builder::class);
        $mockCollection = Mockery::mock(Collection::class);

        $this->mockModel->shouldReceive('with')
            ->once()
            ->with(['profile'])
            ->andReturn($mockBuilder);

        $mockBuilder->shouldReceive('whereHas')
            ->once()
            ->with('profile')
            ->andReturn($mockBuilder);

        $mockBuilder->shouldReceive('limit')
            ->once()
            ->with($customLimit)
            ->andReturn($mockBuilder);

        $mockBuilder->shouldReceive('get')
            ->once()
            ->andReturn($mockCollection);

        $result = $this->repository->findWithProfile($customLimit);

        $this->assertEquals($mockCollection, $result);
    }

}