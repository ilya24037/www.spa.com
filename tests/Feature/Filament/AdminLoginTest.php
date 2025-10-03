<?php

namespace Tests\Feature\Filament;

use App\Domain\User\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаём тестового админа
        User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'role' => UserRole::ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function admin_can_access_login_page()
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('Sign in');
        $response->assertSee('Email address');
        $response->assertSee('Password');
    }

    /** @test */
    public function admin_can_login_with_correct_credentials()
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs(User::where('email', 'admin@test.com')->first());
    }

    /** @test */
    public function admin_cannot_login_with_wrong_password()
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    /** @test */
    public function regular_user_cannot_access_admin_panel()
    {
        $user = User::factory()->create([
            'email' => 'user@test.com',
            'role' => UserRole::CLIENT,
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_admin_dashboard_after_login()
    {
        $admin = User::where('email', 'admin@test.com')->first();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    /** @test */
    public function csrf_token_is_present_on_login_page()
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('_token');
    }

    /** @test */
    public function session_is_working()
    {
        $admin = User::where('email', 'admin@test.com')->first();

        // Логинимся
        $this->actingAs($admin);

        // Проверяем что сессия работает
        $response = $this->get('/admin');
        $response->assertStatus(200);

        // Проверяем что можем сделать второй запрос
        $response2 = $this->get('/admin');
        $response2->assertStatus(200);
    }
}
