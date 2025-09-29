<?php

namespace Tests\Feature\Filament;

use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Review\Models\Review;
use App\Domain\Complaint\Models\Complaint;
use App\Filament\Resources\AdResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\MasterProfileResource;
use App\Filament\Resources\ReviewResource;
use App\Filament\Resources\ComplaintResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Tests\Traits\SafeRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentResourcesTest extends TestCase
{
    use SafeRefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
        ]);
    }

    /** @test */
    public function test_ad_resource_loads_correctly()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/ads');

        $response->assertStatus(200);
        $response->assertSee('Объявления');
    }

    /** @test */
    public function test_ad_resource_can_view_record()
    {
        $this->actingAs($this->admin);

        $ad = Ad::factory()->create();

        $response = $this->get("/admin/ads/{$ad->id}");

        $response->assertStatus(200);
        $response->assertSee($ad->title);
    }

    /** @test */
    public function test_ad_resource_can_edit_record()
    {
        $this->actingAs($this->admin);

        $ad = Ad::factory()->create();

        $response = $this->get("/admin/ads/{$ad->id}/edit");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_user_resource_loads_correctly()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee('Пользователи');
    }

    /** @test */
    public function test_master_profile_resource_loads_correctly()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/master-profiles');

        $response->assertStatus(200);
        $response->assertSee('Мастера');
    }

    /** @test */
    public function test_review_resource_loads_correctly()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/reviews');

        $response->assertStatus(200);
        $response->assertSee('Отзывы');
    }

    /** @test */
    public function test_complaint_resource_loads_correctly()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/complaints');

        $response->assertStatus(200);
        $response->assertSee('Жалобы');
    }

    /** @test */
    public function test_ad_resource_table_actions_exist()
    {
        $this->actingAs($this->admin);

        $table = AdResource::table(new \Filament\Tables\Table());

        $actions = $table->getActions();
        $actionClasses = array_map(fn($action) => get_class($action), $actions);

        $this->assertContains(\Filament\Tables\Actions\ViewAction::class, $actionClasses);
        $this->assertContains(\Filament\Tables\Actions\EditAction::class, $actionClasses);
    }

    /** @test */
    public function test_ad_resource_bulk_actions_exist()
    {
        $this->actingAs($this->admin);

        $table = AdResource::table(new \Filament\Tables\Table());

        $bulkActions = $table->getBulkActions();

        $this->assertNotEmpty($bulkActions);

        // Check for BulkActionGroup
        $hasBulkActionGroup = false;
        foreach ($bulkActions as $action) {
            if ($action instanceof \Filament\Tables\Actions\BulkActionGroup) {
                $hasBulkActionGroup = true;
                break;
            }
        }

        $this->assertTrue($hasBulkActionGroup, 'BulkActionGroup not found in bulk actions');
    }

    /** @test */
    public function test_review_resource_table_actions_exist()
    {
        $this->actingAs($this->admin);

        $table = ReviewResource::table(new \Filament\Tables\Table());

        $actions = $table->getActions();
        $actionClasses = array_map(fn($action) => get_class($action), $actions);

        $this->assertContains(\Filament\Tables\Actions\ViewAction::class, $actionClasses);
        $this->assertContains(\Filament\Tables\Actions\EditAction::class, $actionClasses);
    }

    /** @test */
    public function test_master_profile_resource_table_actions_exist()
    {
        $this->actingAs($this->admin);

        $table = MasterProfileResource::table(new \Filament\Tables\Table());

        $actions = $table->getActions();
        $actionClasses = array_map(fn($action) => get_class($action), $actions);

        $this->assertContains(\Filament\Tables\Actions\ViewAction::class, $actionClasses);
        $this->assertContains(\Filament\Tables\Actions\EditAction::class, $actionClasses);
    }

    /** @test */
    public function test_complaint_resource_table_actions_exist()
    {
        $this->actingAs($this->admin);

        $table = ComplaintResource::table(new \Filament\Tables\Table());

        $actions = $table->getActions();
        $actionClasses = array_map(fn($action) => get_class($action), $actions);

        $this->assertContains(\Filament\Tables\Actions\ViewAction::class, $actionClasses);
    }

    /** @test */
    public function test_ad_resource_page_actions_have_correct_namespace()
    {
        $this->actingAs($this->admin);

        // Test ViewAd page
        $viewPage = new \App\Filament\Resources\AdResource\Pages\ViewAd();
        $this->assertInstanceOf(\Filament\Resources\Pages\ViewRecord::class, $viewPage);

        // Test EditAd page
        $editPage = new \App\Filament\Resources\AdResource\Pages\EditAd();
        $this->assertInstanceOf(\Filament\Resources\Pages\EditRecord::class, $editPage);
    }

    /** @test */
    public function test_filament_actions_are_properly_imported()
    {
        $resourceFiles = glob(app_path('Filament/Resources/**/*.php'));

        foreach ($resourceFiles as $file) {
            $content = file_get_contents($file);

            // Check that ViewAction, EditAction, DeleteAction are not imported incorrectly
            $this->assertStringNotContainsString('use Filament\Tables\Actions\ViewAction;', $content);
            $this->assertStringNotContainsString('use Filament\Tables\Actions\EditAction;', $content);
            $this->assertStringNotContainsString('use Filament\Tables\Actions\DeleteAction;', $content);

            // Check that Actions are used with proper namespace
            if (strpos($content, 'ViewAction::make()') !== false) {
                $this->assertStringContainsString('Tables\Actions\ViewAction::make()', $content);
            }

            if (strpos($content, 'EditAction::make()') !== false) {
                $this->assertStringContainsString('Tables\Actions\EditAction::make()', $content);
            }

            // For Page files, check proper Actions import
            if (strpos($file, '/Pages/') !== false && strpos($content, 'Actions\\') !== false) {
                $this->assertStringContainsString('use Filament\Actions;', $content);
            }
        }
    }

    /** @test */
    public function test_admin_dashboard_loads()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('MASSAGIST Admin');
    }

    /** @test */
    public function test_non_admin_cannot_access_admin_panel()
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user);

        $response = $this->get('/admin');

        $response->assertStatus(302); // Redirect to login
    }
}