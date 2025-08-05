<?php

namespace Tests\Unit\Domain\User;

use Tests\TestCase;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Unit тесты для UserRepository согласно плану рефакторинга
 * 
 * Тестируем ТОЛЬКО новые методы:
 * - findByEmail()
 * - findActive()
 * - findWithProfile()
 * 
 * КРИТИЧЕСКИ ВАЖНО: Проверяем корректность запросов и безопасность!
 */
class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new UserRepository(new User());
    }

    /**
     * Тест 1: findByEmail находит пользователя по email
     */
    public function test_findByEmail_returns_user_with_relations()
    {
        // Создаем пользователя с профилем и настройками
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'role' => UserRole::CLIENT,
            'status' => UserStatus::ACTIVE,
        ]);
        
        $user->ensureProfile(['name' => 'Test User']);
        $user->ensureSettings(['theme' => 'dark']);

        // Ищем пользователя
        $foundUser = $this->repository->findByEmail('test@example.com');

        // Проверяем результат
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals('test@example.com', $foundUser->email);
        $this->assertEquals($user->id, $foundUser->id);
        
        // Проверяем загрузку отношений
        $this->assertTrue($foundUser->relationLoaded('profile'));
        $this->assertTrue($foundUser->relationLoaded('settings'));
        $this->assertEquals('Test User', $foundUser->profile->name);
        $this->assertEquals('dark', $foundUser->settings->theme);
    }

    /**
     * Тест 2: findByEmail без загрузки отношений
     */
    public function test_findByEmail_without_relations()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Ищем без отношений
        $foundUser = $this->repository->findByEmail('test@example.com', false);

        // Проверяем результат
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals('test@example.com', $foundUser->email);
        
        // Проверяем, что отношения НЕ загружены
        $this->assertFalse($foundUser->relationLoaded('profile'));
        $this->assertFalse($foundUser->relationLoaded('settings'));
    }

    /**
     * Тест 3: findByEmail возвращает null для несуществующего email
     */
    public function test_findByEmail_returns_null_for_nonexistent_email()
    {
        $result = $this->repository->findByEmail('nonexistent@example.com');

        $this->assertNull($result);
    }

    /**
     * Тест 4: findByEmail регистронезависимый поиск
     */
    public function test_findByEmail_is_case_insensitive()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Ищем с разным регистром
        $foundUser1 = $this->repository->findByEmail('TEST@EXAMPLE.COM');
        $foundUser2 = $this->repository->findByEmail('Test@Example.Com');

        $this->assertNotNull($foundUser1);
        $this->assertNotNull($foundUser2);
        $this->assertEquals($user->id, $foundUser1->id);
        $this->assertEquals($user->id, $foundUser2->id);
    }

    /**
     * Тест 5: findActive возвращает только активных пользователей
     */
    public function test_findActive_returns_only_active_users()
    {
        // Создаем пользователей с разными статусами
        $activeUser1 = User::factory()->create(['status' => UserStatus::ACTIVE]);
        $activeUser2 = User::factory()->create(['status' => UserStatus::ACTIVE]);
        $pendingUser = User::factory()->create(['status' => UserStatus::PENDING]);
        $inactiveUser = User::factory()->create(['status' => UserStatus::INACTIVE]);
        $suspendedUser = User::factory()->create(['status' => UserStatus::SUSPENDED]);
        $bannedUser = User::factory()->create(['status' => UserStatus::BANNED]);

        // Получаем активных
        $activeUsers = $this->repository->findActive();

        // Проверяем результат
        $this->assertCount(2, $activeUsers);
        $this->assertTrue($activeUsers->contains('id', $activeUser1->id));
        $this->assertTrue($activeUsers->contains('id', $activeUser2->id));
        $this->assertFalse($activeUsers->contains('id', $pendingUser->id));
        $this->assertFalse($activeUsers->contains('id', $inactiveUser->id));
        $this->assertFalse($activeUsers->contains('id', $suspendedUser->id));
        $this->assertFalse($activeUsers->contains('id', $bannedUser->id));
    }

    /**
     * Тест 6: findActive с лимитом
     */
    public function test_findActive_respects_limit()
    {
        // Создаем 10 активных пользователей
        User::factory()->count(10)->create(['status' => UserStatus::ACTIVE]);

        // Получаем с лимитом
        $activeUsers = $this->repository->findActive(5);

        // Проверяем количество
        $this->assertCount(5, $activeUsers);
    }

    /**
     * Тест 7: findActive загружает отношения
     */
    public function test_findActive_loads_relations()
    {
        $user = User::factory()->create(['status' => UserStatus::ACTIVE]);
        $user->ensureProfile(['name' => 'Active User']);
        $user->ensureSettings();

        $activeUsers = $this->repository->findActive();

        $foundUser = $activeUsers->first();
        $this->assertTrue($foundUser->relationLoaded('profile'));
        $this->assertTrue($foundUser->relationLoaded('settings'));
        $this->assertEquals('Active User', $foundUser->profile->name);
    }

    /**
     * Тест 8: findActive сортирует по дате создания (новые первые)
     */
    public function test_findActive_orders_by_created_at_desc()
    {
        $oldUser = User::factory()->create([
            'status' => UserStatus::ACTIVE,
            'created_at' => now()->subDays(10),
        ]);
        
        $newUser = User::factory()->create([
            'status' => UserStatus::ACTIVE,
            'created_at' => now()->subDays(1),
        ]);
        
        $middleUser = User::factory()->create([
            'status' => UserStatus::ACTIVE,
            'created_at' => now()->subDays(5),
        ]);

        $activeUsers = $this->repository->findActive();

        // Проверяем порядок
        $this->assertEquals($newUser->id, $activeUsers[0]->id);
        $this->assertEquals($middleUser->id, $activeUsers[1]->id);
        $this->assertEquals($oldUser->id, $activeUsers[2]->id);
    }

    /**
     * Тест 9: findWithProfile возвращает только пользователей с профилями
     */
    public function test_findWithProfile_returns_only_users_with_profiles()
    {
        // Создаем пользователей
        $userWithProfile1 = User::factory()->create();
        $userWithProfile1->ensureProfile(['name' => 'User 1']);
        
        $userWithProfile2 = User::factory()->create();
        $userWithProfile2->ensureProfile(['name' => 'User 2']);
        
        $userWithoutProfile = User::factory()->create();
        // Удаляем профиль, если он был создан автоматически
        DB::table('user_profiles')->where('user_id', $userWithoutProfile->id)->delete();

        // Получаем пользователей с профилями
        $usersWithProfiles = $this->repository->findWithProfile();

        // Проверяем результат
        $this->assertCount(2, $usersWithProfiles);
        $this->assertTrue($usersWithProfiles->contains('id', $userWithProfile1->id));
        $this->assertTrue($usersWithProfiles->contains('id', $userWithProfile2->id));
        $this->assertFalse($usersWithProfiles->contains('id', $userWithoutProfile->id));
    }

    /**
     * Тест 10: findWithProfile с лимитом
     */
    public function test_findWithProfile_respects_limit()
    {
        // Создаем 10 пользователей с профилями
        for ($i = 0; $i < 10; $i++) {
            $user = User::factory()->create();
            $user->ensureProfile(['name' => "User $i"]);
        }

        // Получаем с лимитом
        $users = $this->repository->findWithProfile(3);

        // Проверяем количество
        $this->assertCount(3, $users);
    }

    /**
     * Тест 11: findWithProfile загружает профиль и настройки
     */
    public function test_findWithProfile_loads_profile_and_settings()
    {
        $user = User::factory()->create();
        $user->ensureProfile(['name' => 'Test User', 'city' => 'Moscow']);
        $user->ensureSettings(['theme' => 'light']);

        $users = $this->repository->findWithProfile();

        $foundUser = $users->first();
        $this->assertTrue($foundUser->relationLoaded('profile'));
        $this->assertTrue($foundUser->relationLoaded('settings'));
        $this->assertEquals('Test User', $foundUser->profile->name);
        $this->assertEquals('Moscow', $foundUser->profile->city);
        $this->assertEquals('light', $foundUser->settings->theme);
    }

    /**
     * Тест 12: Проверка защиты от SQL инъекций в findByEmail
     */
    public function test_findByEmail_is_safe_from_sql_injection()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Попытка SQL инъекции
        $maliciousEmail = "test@example.com' OR '1'='1";
        $result = $this->repository->findByEmail($maliciousEmail);

        // Должен вернуть null, а не все записи
        $this->assertNull($result);
    }

    /**
     * Тест 13: Проверка производительности - нет N+1 проблем
     */
    public function test_no_n_plus_one_queries_in_findActive()
    {
        // Создаем пользователей с профилями
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create(['status' => UserStatus::ACTIVE]);
            $user->ensureProfile();
            $user->ensureSettings();
        }

        // Включаем подсчет запросов
        DB::enableQueryLog();
        
        // Выполняем запрос
        $users = $this->repository->findActive();
        
        // Обращаемся к отношениям
        foreach ($users as $user) {
            $user->profile->name;
            $user->settings->theme;
        }
        
        // Получаем количество запросов
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Должно быть 3 запроса: users, profiles, settings
        // А не 1 + 2*N (где N = количество пользователей)
        $this->assertLessThanOrEqual(3, $queries);
    }

    /**
     * Тест 14: findActive не возвращает удаленных пользователей
     */
    public function test_findActive_excludes_soft_deleted_users()
    {
        $activeUser = User::factory()->create(['status' => UserStatus::ACTIVE]);
        $deletedUser = User::factory()->create(['status' => UserStatus::ACTIVE]);
        $deletedUser->delete(); // Soft delete

        $activeUsers = $this->repository->findActive();

        $this->assertCount(1, $activeUsers);
        $this->assertEquals($activeUser->id, $activeUsers->first()->id);
    }

    /**
     * Тест 15: Методы репозитория возвращают правильные типы
     */
    public function test_repository_methods_return_correct_types()
    {
        // findByEmail возвращает User или null
        $result = $this->repository->findByEmail('nonexistent@example.com');
        $this->assertNull($result);

        $user = User::factory()->create(['email' => 'test@example.com']);
        $result = $this->repository->findByEmail('test@example.com');
        $this->assertInstanceOf(User::class, $result);

        // findActive возвращает Collection
        $result = $this->repository->findActive();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);

        // findWithProfile возвращает Collection
        $result = $this->repository->findWithProfile();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * Тест 16: Проверка дефолтного лимита в 1000 записей
     */
    public function test_default_limit_prevents_memory_issues()
    {
        // Создаем 1500 активных пользователей
        // Используем чанки для избежания проблем с памятью при создании
        for ($i = 0; $i < 15; $i++) {
            User::factory()->count(100)->create(['status' => UserStatus::ACTIVE]);
        }

        // Получаем без указания лимита
        $activeUsers = $this->repository->findActive();

        // Должно вернуть только 1000 (дефолтный лимит)
        $this->assertCount(1000, $activeUsers);
    }

    /**
     * Тест 17: Проверка корректности работы с пустыми email
     */
    public function test_findByEmail_handles_empty_email()
    {
        $result = $this->repository->findByEmail('');
        $this->assertNull($result);

        $result = $this->repository->findByEmail(' ');
        $this->assertNull($result);
    }

    /**
     * Тест 18: findWithProfile правильно фильтрует по наличию профиля
     */
    public function test_findWithProfile_uses_whereHas_correctly()
    {
        // Создаем пользователя с пустым профилем (все поля null)
        $user = User::factory()->create();
        DB::table('user_profiles')->insert([
            'user_id' => $user->id,
            'name' => null,
            'phone' => null,
            'city' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Пользователь должен быть найден, т.к. запись профиля существует
        $users = $this->repository->findWithProfile();
        $this->assertTrue($users->contains('id', $user->id));
    }

    /**
     * Тест 19: Проверка корректной работы с различными email форматами
     */
    public function test_findByEmail_handles_various_email_formats()
    {
        $user = User::factory()->create(['email' => 'test+tag@example.com']);

        $found = $this->repository->findByEmail('test+tag@example.com');
        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);

        // Проверяем с пробелами
        $found = $this->repository->findByEmail(' test+tag@example.com ');
        $this->assertNotNull($found);
    }

    /**
     * Тест 20: Все методы используют правильную модель
     */
    public function test_repository_uses_injected_model()
    {
        // Создаем мок модели для проверки вызовов
        $mockModel = $this->createMock(User::class);
        
        $mockModel->expects($this->once())
            ->method('where')
            ->with('email', 'test@example.com')
            ->willReturn($mockModel);
            
        $mockModel->expects($this->once())
            ->method('with')
            ->with(['profile', 'settings'])
            ->willReturn($mockModel);
            
        $mockModel->expects($this->once())
            ->method('first')
            ->willReturn(null);

        $repository = new UserRepository($mockModel);
        $repository->findByEmail('test@example.com');
    }
}