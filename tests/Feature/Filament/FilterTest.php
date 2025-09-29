<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use Tests\Traits\SafeRefreshDatabase;
use App\Domain\User\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;

class FilterTest extends TestCase
{
    use SafeRefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@test.com',
        ]);

        $this->actingAs($this->admin);
    }

    /**
     * Test that role filters work correctly in UserResource
     */
    public function test_user_resource_role_filters_work()
    {
        // Create users with different roles
        User::factory()->create(['role' => UserRole::CLIENT->value]);
        User::factory()->create(['role' => UserRole::MASTER->value]);
        User::factory()->create(['role' => UserRole::MODERATOR->value]);

        // Test filter by CLIENT role
        $response = $this->get('/admin/users?tableFilters[role][value]=client');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');

        // Test filter by MASTER role
        $response = $this->get('/admin/users?tableFilters[role][value]=master');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');

        // Test filter by MODERATOR role
        $response = $this->get('/admin/users?tableFilters[role][value]=moderator');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');

        // Test filter by ADMIN role
        $response = $this->get('/admin/users?tableFilters[role][value]=admin');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');
    }

    /**
     * Test that status filters work correctly in UserResource
     */
    public function test_user_resource_status_filters_work()
    {
        // Create users with different statuses
        User::factory()->create(['status' => UserStatus::ACTIVE->value]);
        User::factory()->create(['status' => UserStatus::INACTIVE->value]);
        User::factory()->create(['status' => UserStatus::SUSPENDED->value]);
        User::factory()->create(['status' => UserStatus::BANNED->value]);
        User::factory()->create(['status' => UserStatus::PENDING->value]);
        User::factory()->create(['status' => UserStatus::DELETED->value]);

        // Test filter by ACTIVE status
        $response = $this->get('/admin/users?tableFilters[status][value]=active');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');

        // Test filter by INACTIVE status
        $response = $this->get('/admin/users?tableFilters[status][value]=inactive');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');

        // Test filter by SUSPENDED status
        $response = $this->get('/admin/users?tableFilters[status][value]=suspended');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');

        // Test filter by BANNED status
        $response = $this->get('/admin/users?tableFilters[status][value]=banned');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');

        // Test filter by PENDING status
        $response = $this->get('/admin/users?tableFilters[status][value]=pending');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');

        // Test filter by DELETED status
        $response = $this->get('/admin/users?tableFilters[status][value]=deleted');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');
    }

    /**
     * Test that multiple filters can be applied together
     */
    public function test_user_resource_multiple_filters_work()
    {
        // Create test users
        User::factory()->create([
            'role' => UserRole::MASTER->value,
            'status' => UserStatus::ACTIVE->value
        ]);

        // Test multiple filters applied together
        $response = $this->get('/admin/users?tableFilters[role][value]=master&tableFilters[status][value]=active');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');
    }

    /**
     * Test that filters work with empty results
     */
    public function test_user_resource_filters_with_empty_results()
    {
        // Clear all users except admin
        User::where('id', '!=', $this->admin->id)->delete();

        // Test filter that should return no results
        $response = $this->get('/admin/users?tableFilters[role][value]=client');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');
    }

    /**
     * Test that invalid filter values are handled gracefully
     */
    public function test_user_resource_handles_invalid_filter_values()
    {
        // Test invalid role filter
        $response = $this->get('/admin/users?tableFilters[role][value]=invalid_role');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');

        // Test invalid status filter
        $response = $this->get('/admin/users?tableFilters[status][value]=invalid_status');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');
    }

    /**
     * Test that filters work correctly with pagination
     */
    public function test_user_resource_filters_work_with_pagination()
    {
        // Create many users with same role
        for ($i = 0; $i < 20; $i++) {
            User::factory()->create(['role' => UserRole::CLIENT->value]);
        }

        // Test filter with pagination
        $response = $this->get('/admin/users?tableFilters[role][value]=client&page=1');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');
    }

    /**
     * Test that search works together with filters
     */
    public function test_user_resource_search_with_filters()
    {
        // Create test user
        User::factory()->create([
            'name' => 'Test Master User',
            'role' => UserRole::MASTER->value,
            'status' => UserStatus::ACTIVE->value
        ]);

        // Test search with filter
        $response = $this->get('/admin/users?tableFilters[role][value]=master&tableSearch=Test');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');
    }
}