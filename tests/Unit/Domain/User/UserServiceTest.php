<?php

namespace Tests\Unit\Domain\User;

use Tests\UnitTestCase;
use App\Domain\User\Services\UserService;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Mockery;

/**
 * ЧИСТЫЕ Unit тесты для UserService БЕЗ Laravel фасадов
 * 
 * Тестируем ТОЛЬКО новые методы:
 * - register()
 * - updateProfile()  
 * - changePassword()
 * - deactivate()
 * 
 * КРИТИЧЕСКИ ВАЖНО: Проверяем безопасность и валидацию!
 */
class UserServiceTest extends UnitTestCase
{
    private UserService $userService;
    private $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = Mockery::mock(UserRepository::class);
        $this->userService = new UserService($this->mockRepository);
    }

    /**
     * Тест 1: Валидация email при регистрации
     */
    public function test_register_validates_email_format()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Некорректный email');

        // Приватный метод validateRegistrationData выбросит исключение
        $this->userService->register([
            'email' => 'invalid-email',
            'password' => 'ValidPass123!'
        ]);
    }

    /**
     * Тест 2: Валидация пароля при регистрации
     */
    public function test_register_validates_password_length()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Пароль должен содержать минимум 8 символов');

        $this->userService->register([
            'email' => 'test@example.com',
            'password' => 'short'
        ]);
    }

    /**
     * Тест 3: Проверка уникальности email
     */
    public function test_register_checks_email_uniqueness()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Пользователь с таким email уже существует');

        // Мокаем, что пользователь с таким email существует
        $existingUser = Mockery::mock(User::class);
        $this->mockRepository->shouldReceive('findByEmail')
            ->once()
            ->with('existing@example.com')
            ->andReturn($existingUser);

        $this->userService->register([
            'email' => 'existing@example.com',
            'password' => 'ValidPass123!'
        ]);
    }

    /**
     * Тест 4: Валидация телефона при обновлении профиля
     */
    public function test_updateProfile_validates_phone_format()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Некорректный формат телефона');

        $user = Mockery::mock(User::class);
        
        $this->userService->updateProfile($user, [
            'phone' => 'invalid phone'
        ]);
    }

    /**
     * Тест 5: Валидация email в профиле
     */
    public function test_updateProfile_validates_email_in_profile()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Некорректный email в профиле');

        $user = Mockery::mock(User::class);
        
        $this->userService->updateProfile($user, [
            'email' => 'not-an-email'
        ]);
    }

    /**
     * Тест 6: Валидация имени в профиле
     */
    public function test_updateProfile_validates_name_not_empty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Имя не может быть пустым или больше 255 символов');

        $user = Mockery::mock(User::class);
        
        $this->userService->updateProfile($user, [
            'name' => ''
        ]);
    }

    /**
     * Тест 7: Валидация длины нового пароля
     */
    public function test_changePassword_validates_new_password_length()
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->password = password_hash('OldPass123!', PASSWORD_DEFAULT);

        $result = $this->userService->changePassword($user, 'OldPass123!', 'short');

        $this->assertFalse($result['success']);
        $this->assertEquals('Пароль должен содержать минимум 8 символов', $result['error']);
    }

    /**
     * Тест 8: Валидация сложности нового пароля
     */
    public function test_changePassword_validates_password_complexity()
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->password = password_hash('OldPass123!', PASSWORD_DEFAULT);

        $result = $this->userService->changePassword($user, 'OldPass123!', 'onlylowercase');

        $this->assertFalse($result['success']);
        $this->assertEquals('Пароль должен содержать строчные, заглавные буквы и цифры', $result['error']);
    }

    /**
     * Тест 9: Новый пароль не должен совпадать со старым
     */
    public function test_changePassword_checks_password_not_same()
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->password = password_hash('SamePass123!', PASSWORD_DEFAULT);

        $result = $this->userService->changePassword($user, 'SamePass123!', 'SamePass123!');

        $this->assertFalse($result['success']);
        $this->assertEquals('Новый пароль должен отличаться от текущего', $result['error']);
    }

    /**
     * Тест 10: Успешный вызов deactivate
     */
    public function test_deactivate_validates_method_call()
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('deactivate')
            ->once()
            ->andReturn(true);

        $result = $this->userService->deactivate($user);

        $this->assertTrue($result);
    }

}