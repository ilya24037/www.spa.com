<?php

namespace Tests\Feature\Filament;

use App\Domain\User\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_access_admin_panel()
    {
        // Создаем администратора
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
        ]);

        // Аутентифицируемся
        $this->actingAs($admin);

        // Проверяем доступ к админ-панели
        $response = $this->get('/admin');
        $response->assertStatus(200);
    }

    /** @test */
    public function moderator_can_access_admin_panel()
    {
        // Создаем модератора
        $moderator = User::factory()->create([
            'role' => UserRole::MODERATOR->value,
        ]);

        // Аутентифицируемся
        $this->actingAs($moderator);

        // Проверяем доступ к админ-панели
        $response = $this->get('/admin');
        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_cannot_access_admin_panel()
    {
        // Создаем обычного пользователя
        $user = User::factory()->create([
            'role' => UserRole::CLIENT->value,
        ]);

        // Аутентифицируемся
        $this->actingAs($user);

        // Проверяем что доступ запрещен
        $response = $this->get('/admin');
        $response->assertStatus(403);
    }

    /** @test */
    public function guest_redirected_to_login_from_admin_panel()
    {
        // Проверяем что неаутентифицированный пользователь перенаправляется на логин
        $response = $this->get('/admin');
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function admin_can_view_admin_login_page()
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
        $response->assertSee('Email');
        $response->assertSee('Password');
    }

    /** @test */
    public function admin_can_authenticate_with_valid_credentials()
    {
        // Создаем администратора
        $admin = User::factory()->create([
            'email' => 'test.admin@spa.com',
            'password' => bcrypt('admin123'),
            'role' => UserRole::ADMIN->value,
        ]);

        // Пытаемся войти
        $response = $this->post('/admin/login', [
            'email' => 'test.admin@spa.com',
            'password' => 'admin123',
        ]);

        // Проверяем что мы аутентифицированы
        $this->assertAuthenticated();
    }
}