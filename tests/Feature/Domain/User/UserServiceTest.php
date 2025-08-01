<?php

namespace Tests\Feature\Domain\User;

use Tests\TestCase;
use App\Domain\User\Services\UserService;
use App\Domain\User\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userService = app(UserService::class);
        Storage::fake('public');
    }

    public function test_can_create_user()
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'name' => 'Test User',
            'role' => UserRole::CLIENT->value,
        ];

        $user = $this->userService->create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userData['email'], $user->email);
        $this->assertEquals(UserRole::CLIENT, $user->role);
        $this->assertEquals(UserStatus::PENDING, $user->status);
    }

    public function test_can_update_user_profile()
    {
        $user = User::factory()->create();
        
        $profileData = [
            'name' => 'Updated Name',
            'phone' => '+7 999 999 99 99',
            'city' => 'Москва',
        ];

        $result = $this->userService->updateProfile($user, $profileData);

        $this->assertTrue($result);
        $this->assertEquals('Updated Name', $user->profile->name);
        $this->assertEquals('+7 999 999 99 99', $user->profile->phone);
        $this->assertEquals('Москва', $user->profile->city);
    }

    public function test_can_change_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('old_password')
        ]);

        $result = $this->userService->changePassword(
            $user,
            'old_password',
            'new_password'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('Пароль успешно изменен', $result['message']);
    }

    public function test_cannot_change_password_with_wrong_current()
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct_password')
        ]);

        $result = $this->userService->changePassword(
            $user,
            'wrong_password',
            'new_password'
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('Неверный текущий пароль', $result['error']);
    }

    public function test_can_upload_avatar()
    {
        $user = User::factory()->create();
        
        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $result = $this->userService->uploadAvatar($user, $file);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('avatar_url', $result);
        Storage::disk('public')->assertExists('avatars/' . basename($result['avatar_url']));
    }

    public function test_cannot_upload_invalid_avatar_type()
    {
        $user = User::factory()->create();
        
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $result = $this->userService->uploadAvatar($user, $file);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Разрешены только изображения', $result['error']);
    }

    public function test_can_activate_user()
    {
        $user = User::factory()->create([
            'status' => UserStatus::PENDING
        ]);

        $result = $this->userService->activate($user);

        $this->assertTrue($result);
        $this->assertEquals(UserStatus::ACTIVE, $user->fresh()->status);
    }

    public function test_can_change_user_role()
    {
        $user = User::factory()->create([
            'role' => UserRole::CLIENT
        ]);

        $result = $this->userService->changeRole($user, UserRole::MASTER);

        $this->assertTrue($result);
        $this->assertEquals(UserRole::MASTER, $user->fresh()->role);
        $this->assertNotNull($user->fresh()->masterProfile);
    }

    public function test_can_get_user_stats()
    {
        $user = User::factory()->create();

        $stats = $this->userService->getUserStats($user);

        $this->assertArrayHasKey('profile_completion', $stats);
        $this->assertArrayHasKey('registration_date', $stats);
        $this->assertArrayHasKey('last_activity', $stats);
        $this->assertArrayHasKey('email_verified', $stats);
    }
}