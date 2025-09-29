<?php

namespace Tests\Feature\Filament;

use Tests\TestCase;
use Tests\Traits\SafeRefreshDatabase;
use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Review\Models\Review;

class ResourceValidationTest extends TestCase
{
    use SafeRefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
        ]);

        $this->actingAs($this->admin);
    }

    /**
     * Test that AdResource loads without errors
     */
    public function test_ad_resource_loads_without_errors()
    {
        $response = $this->get('/admin/ads');

        $response->assertStatus(200);
        $response->assertDontSee('TypeError');
        $response->assertDontSee('duplicate');
        $response->assertSee('Объявления');
    }

    /**
     * Test that all tabs work on Ad resource
     */
    public function test_ad_resource_all_tabs_work()
    {
        // Test "All" tab
        $response = $this->get('/admin/ads?tab=all');
        $response->assertStatus(200);
        $response->assertDontSee('TypeError');

        // Test other tabs
        $tabs = ['pending', 'active', 'rejected', 'blocked', 'archived'];

        foreach ($tabs as $tab) {
            $response = $this->get("/admin/ads?tab={$tab}");
            $response->assertStatus(200);
            $response->assertDontSee('TypeError');
            $response->assertDontSee('Argument #1');
        }
    }

    /**
     * Test UserResource loads without errors
     */
    public function test_user_resource_loads_without_errors()
    {
        $response = $this->get('/admin/users');

        $response->assertStatus(200);
        $response->assertDontSee('TypeError');
        $response->assertDontSee('duplicate');
    }

    /**
     * Test MasterProfileResource loads without errors
     */
    public function test_master_profile_resource_loads_without_errors()
    {
        $response = $this->get('/admin/master-profiles');

        $response->assertStatus(200);
        $response->assertDontSee('TypeError');
        $response->assertDontSee('duplicate');
    }

    /**
     * Test ComplaintResource loads without errors
     */
    public function test_complaint_resource_loads_without_errors()
    {
        $response = $this->get('/admin/complaints');

        $response->assertStatus(200);
        $response->assertDontSee('TypeError');
        $response->assertDontSee('duplicate');
    }

    /**
     * Test ReviewResource loads without errors
     */
    public function test_review_resource_loads_without_errors()
    {
        $response = $this->get('/admin/reviews');

        $response->assertStatus(200);
        $response->assertDontSee('TypeError');
        $response->assertDontSee('duplicate');
    }

    /**
     * Test that enum values are not duplicated
     */
    public function test_no_duplicate_enum_values_in_resources()
    {
        $resourceFiles = [
            app_path('Filament/Resources/AdResource.php'),
            app_path('Filament/Resources/UserResource.php'),
            app_path('Filament/Resources/MasterProfileResource.php'),
        ];

        foreach ($resourceFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $content = file_get_contents($file);

            // Check for duplicate enum patterns in arrays
            $this->assertNoDuplicateEnumValuesInArrays($content, basename($file));

            // Check for duplicate keys in colors arrays
            $this->assertNoDuplicateColorKeys($content, basename($file));
        }
    }

    private function assertNoDuplicateEnumValuesInArrays($content, $fileName)
    {
        // Extract array definitions with enum values
        preg_match_all('/\[[\s\S]*?((?:AdStatus|UserStatus|MasterStatus)::\w+->value\s*=>\s*[\'"][^\'"]+([\'"]))[\s\S]*?\]/', $content, $matches);

        foreach ($matches[0] as $arrayContent) {
            // Extract enum values from this array
            preg_match_all('/((?:AdStatus|UserStatus|MasterStatus)::\w+)->value/', $arrayContent, $enumMatches);

            $enumValues = $enumMatches[1];
            $uniqueValues = array_unique($enumValues);

            $this->assertEquals(
                count($uniqueValues),
                count($enumValues),
                "Duplicate enum values found in {$fileName}: " . implode(', ', array_diff_assoc($enumValues, $uniqueValues))
            );
        }
    }

    private function assertNoDuplicateColorKeys($content, $fileName)
    {
        // Extract colors array definitions
        preg_match_all('/->colors\(\[([^\]]+)\]\)/', $content, $matches);

        foreach ($matches[1] as $colorsContent) {
            // Extract color keys
            preg_match_all("/['\"](\w+)['\"]\s*=>/", $colorsContent, $keyMatches);

            $keys = $keyMatches[1];
            $uniqueKeys = array_unique($keys);

            $this->assertEquals(
                count($uniqueKeys),
                count($keys),
                "Duplicate color keys found in {$fileName}: " . implode(', ', array_diff_assoc($keys, $uniqueKeys))
            );
        }
    }
}