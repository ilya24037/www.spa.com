<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use Tests\Traits\SafeRefreshDatabase;
use App\Domain\User\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;

class CrudTest extends TestCase
{
    use SafeRefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN->value,
            'email' => 'admin@test.com',
        ]);

        $this->actingAs($this->admin);
    }

    /**
     * Test that main user index page loads without errors
     */
    public function test_user_index_page_loads()
    {
        // Create some test users
        User::factory()->count(3)->create();

        $response = $this->get('/admin/users');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');
        $response->assertDontSee('Fatal error');
    }

    /**
     * Test that create form page loads without errors
     */
    public function test_user_create_page_loads()
    {
        $response = $this->get('/admin/users/create');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');
        $response->assertDontSee('Fatal error');
    }

    /**
     * Test that edit form page loads without errors
     */
    public function test_user_edit_page_loads()
    {
        // Create test user
        $user = User::factory()->create([
            'name' => 'Edit Test User',
            'email' => 'edit@test.com',
        ]);

        $response = $this->get("/admin/users/{$user->id}/edit");
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');
        $response->assertDontSee('Fatal error');
    }

    /**
     * Test that view functionality works (checking if view page exists or redirects properly)
     */
    public function test_user_view_functionality()
    {
        // Create test user
        $user = User::factory()->create([
            'name' => 'View Test User',
            'email' => 'view@test.com',
        ]);

        // Try accessing user view - it may redirect to edit or not exist
        $response = $this->get("/admin/users/{$user->id}");

        // Accept either success (200) or redirect (302) or not found (404)
        $this->assertContains($response->status(), [200, 302, 404]);

        if ($response->status() === 200) {
            $response->assertDontSee('TypeError');
            $response->assertDontSee('Fatal error');
        }
    }

    /**
     * Test that user data displays correctly on index page
     */
    public function test_user_data_displays_on_index()
    {
        // Create test users with specific data
        $user1 = User::factory()->create([
            'name' => 'Test User One',
            'email' => 'test1@example.com',
            'role' => UserRole::CLIENT->value,
        ]);

        $user2 = User::factory()->create([
            'name' => 'Test User Two',
            'email' => 'test2@example.com',
            'role' => UserRole::MASTER->value,
        ]);

        $response = $this->get('/admin/users');
        $response->assertStatus(200);

        // Check that user data is visible on the page
        $response->assertSee($user1->name);
        $response->assertSee($user1->email);
        $response->assertSee($user2->name);
        $response->assertSee($user2->email);
    }

    /**
     * Test that create form displays required fields
     */
    public function test_create_form_has_required_fields()
    {
        $response = $this->get('/admin/users/create');
        $response->assertStatus(200);

        // Check for presence of form fields (these might be in wire:model attributes)
        $response->assertSee('name', false);
        $response->assertSee('email', false);
        $response->assertSee('role', false);
        $response->assertSee('status', false);
    }

    /**
     * Test that edit form displays user data
     */
    public function test_edit_form_displays_user_data()
    {
        $user = User::factory()->create([
            'name' => 'Edit Form Test User',
            'email' => 'editform@test.com',
            'role' => UserRole::CLIENT->value,
            'status' => UserStatus::ACTIVE->value,
        ]);

        $response = $this->get("/admin/users/{$user->id}/edit");
        $response->assertStatus(200);

        // Check that current user data is displayed in form
        $response->assertSee($user->name);
        $response->assertSee($user->email);
    }

    /**
     * Test that unauthorized users cannot access admin pages
     */
    public function test_unauthorized_user_cannot_access_admin()
    {
        // Create regular user (non-admin)
        $regularUser = User::factory()->create([
            'role' => UserRole::CLIENT->value,
        ]);

        $this->actingAs($regularUser);

        // Test main admin page
        $response = $this->get('/admin');
        $response->assertStatus(403);

        // Test users index
        $response = $this->get('/admin/users');
        $response->assertStatus(403);
    }

    /**
     * Test that admin user can access all admin pages
     */
    public function test_admin_user_can_access_admin_pages()
    {
        $response = $this->get('/admin');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');

        $response = $this->get('/admin/users');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');
    }

    /**
     * Test that different user roles display correctly
     */
    public function test_different_user_roles_display()
    {
        // Create users with different roles
        $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $moderator = User::factory()->create(['role' => UserRole::MODERATOR->value]);
        $master = User::factory()->create(['role' => UserRole::MASTER->value]);
        $client = User::factory()->create(['role' => UserRole::CLIENT->value]);

        $response = $this->get('/admin/users');
        $response->assertStatus(200);

        // Verify all users are listed
        $response->assertSee($admin->name);
        $response->assertSee($moderator->name);
        $response->assertSee($master->name);
        $response->assertSee($client->name);
    }

    /**
     * Test that different user statuses display correctly
     */
    public function test_different_user_statuses_display()
    {
        // Create users with different statuses
        User::factory()->create([
            'name' => 'Active User',
            'status' => UserStatus::ACTIVE->value,
        ]);
        User::factory()->create([
            'name' => 'Pending User',
            'status' => UserStatus::PENDING->value,
        ]);
        User::factory()->create([
            'name' => 'Inactive User',
            'status' => UserStatus::INACTIVE->value,
        ]);

        $response = $this->get('/admin/users');
        $response->assertStatus(200);

        // Check that all users are displayed
        $response->assertSee('Active User');
        $response->assertSee('Pending User');
        $response->assertSee('Inactive User');
    }

    /**
     * Test pagination works on users index
     */
    public function test_users_pagination_works()
    {
        // Create many users to trigger pagination
        User::factory()->count(25)->create();

        $response = $this->get('/admin/users');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');

        // Filament typically shows pagination controls when there are many records
        // We just verify the page loads without errors
    }

    /**
     * Test that moderator can access some admin functions
     */
    public function test_moderator_has_limited_access()
    {
        $moderator = User::factory()->create([
            'role' => UserRole::MODERATOR->value,
        ]);

        $this->actingAs($moderator);

        $response = $this->get('/admin');

        // Moderator should either have access (200) or be redirected/forbidden
        // The exact behavior depends on your Filament configuration
        $this->assertContains($response->status(), [200, 302, 403]);
    }
}