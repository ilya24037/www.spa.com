<?php

namespace Tests\Unit\Domain\User;

use Tests\TestCase;
use App\Domain\User\Services\UserService;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserProfile;
use App\Domain\User\Events\UserRegistered;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Чистые Unit тесты для UserService БЕЗ базы данных
 * 
 * Тестируем ТОЛЬКО логику сервиса с использованием моков
 */
class UserServiceUnitTest extends TestCase
{
    private UserService $userService;
    private $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем мок репозитория
        $this->mockRepository = Mockery::mock(UserRepository::class);
        $this->userService = new UserService($this->mockRepository);
        
        // Фейковые события
        Event::fake();
    }

    /**
     * Тест: Успешная регистрация пользователя
     */
    public function test_register_creates_user_successfully()
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'SecurePass123!',
            'name' => 'Test User',
        ];

        // Мокаем проверку уникальности email
        $this->mockRepository->shouldReceive('findByEmail')
            ->once()
            ->with($data['email'])
            ->andReturn(null);

        // Мокаем создание пользователя
        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->id = 1;
        $mockUser->email = $data['email'];
        $mockUser->role = UserRole::CLIENT;
        $mockUser->status = UserStatus::PENDING;
        
        $this->mockRepository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($args) {
                return isset($args['email']) && 
                       isset($args['password']) &&
                       Hash::check('SecurePass123!', $args['password']);
            }))
            ->andReturn($mockUser);

        // Мокаем updateProfile
        $mockUser->shouldReceive('updateProfile')
            ->once()
            ->with(['name' => 'Test User'])
            ->andReturn(true);

        $result = $this->userService->register($data);

        $this->assertEquals($data['email'], $result->email);
        
        // Проверяем, что событие было отправлено
        Event::assertDispatched(UserRegistered::class);
    }

    /**
     * Тест: Регистрация с невалидным email
     */
    public function test_register_throws_exception_for_invalid_email()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Некорректный email');

        $data = [
            'email' => 'invalid-email',
            'password' => 'SecurePass123!',
        ];

        $this->userService->register($data);
    }

    /**
     * Тест: Регистрация со слабым паролем
     */
    public function test_register_throws_exception_for_weak_password()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Пароль должен содержать минимум 8 символов');

        $data = [
            'email' => 'test@example.com',
            'password' => 'weak',
        ];

        $this->userService->register($data);
    }

    /**
     * Тест: Регистрация с существующим email
     */
    public function test_register_throws_exception_for_existing_email()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Пользователь с таким email уже существует');

        $data = [
            'email' => 'existing@example.com',
            'password' => 'SecurePass123!',
        ];

        // Мокаем, что пользователь найден
        $existingUser = Mockery::mock(User::class);
        $this->mockRepository->shouldReceive('findByEmail')
            ->once()
            ->with($data['email'])
            ->andReturn($existingUser);

        $this->userService->register($data);
    }

    /**
     * Тест: Обновление профиля
     */
    public function test_updateProfile_calls_user_method()
    {
        $user = Mockery::mock(User::class);
        $profileData = [
            'name' => 'Updated Name',
            'phone' => '+79991234567',
        ];

        // Мокаем вызов updateProfile на модели
        $user->shouldReceive('updateProfile')
            ->once()
            ->with($profileData)
            ->andReturn(true);

        $result = $this->userService->updateProfile($user, $profileData);

        $this->assertTrue($result);
        
        // Проверяем событие
        Event::assertDispatched(UserProfileUpdated::class);
    }

    /**
     * Тест: Обновление профиля с невалидным телефоном
     */
    public function test_updateProfile_throws_exception_for_invalid_phone()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Некорректный формат телефона');

        $user = Mockery::mock(User::class);
        $profileData = [
            'phone' => 'invalid',
        ];

        $this->userService->updateProfile($user, $profileData);
    }

    /**
     * Тест: Изменение пароля с правильным текущим
     */
    public function test_changePassword_succeeds_with_correct_current()
    {
        $currentPassword = 'CurrentPass123!';
        $newPassword = 'NewSecurePass456!';
        
        $user = Mockery::mock(User::class)->makePartial();
        $user->password = Hash::make($currentPassword);
        
        // Мокаем save
        $user->shouldReceive('save')
            ->once()
            ->andReturn(true);

        $result = $this->userService->changePassword($user, $currentPassword, $newPassword);

        $this->assertTrue($result['success']);
        $this->assertEquals('Пароль успешно изменен', $result['message']);
    }

    /**
     * Тест: Изменение пароля с неправильным текущим
     */
    public function test_changePassword_fails_with_incorrect_current()
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->password = Hash::make('CorrectPass123!');

        $result = $this->userService->changePassword($user, 'WrongPass123!', 'NewPass456!');

        $this->assertFalse($result['success']);
        $this->assertEquals('Неверный текущий пароль', $result['error']);
    }

    /**
     * Тест: Деактивация пользователя
     */
    public function test_deactivate_calls_user_method()
    {
        $user = Mockery::mock(User::class);
        
        $user->shouldReceive('deactivate')
            ->once()
            ->andReturn(true);

        $result = $this->userService->deactivate($user);

        $this->assertTrue($result);
    }

    /**
     * Тест: Логирование ошибок
     */
    public function test_logs_errors_on_profile_update_failure()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Profile update failed', Mockery::any());

        $user = Mockery::mock(User::class);
        $user->id = 1;
        
        $user->shouldReceive('updateProfile')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $result = $this->userService->updateProfile($user, ['name' => 'Test']);

        $this->assertFalse($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}