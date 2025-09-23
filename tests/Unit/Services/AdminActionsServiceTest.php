<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Domain\Admin\Services\AdminActionsService;
use App\Domain\Ad\Services\AdModerationService;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Enums\AdStatus;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class AdminActionsServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminActionsService $service;
    private User $admin;
    private Ad $testAd;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаём сервис
        $this->service = new AdminActionsService(
            app(AdModerationService::class)
        );

        // Создаём админа
        $this->admin = User::factory()->create([
            'role' => 'admin'
        ]);

        // Аутентифицируемся как админ
        Auth::login($this->admin);

        // Создаём тестовое объявление
        $this->testAd = Ad::factory()->create([
            'status' => AdStatus::PENDING_MODERATION->value
        ]);
    }

    /**
     * Тест массового одобрения
     */
    public function test_perform_bulk_action_approves_ads(): void
    {
        $ads = Ad::factory()->count(3)->create([
            'status' => AdStatus::PENDING_MODERATION->value
        ]);

        $adIds = $ads->pluck('id')->toArray();

        $result = $this->service->performBulkAction($adIds, 'approve');

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['processed']);
        $this->assertEquals(0, $result['failed']);

        // Проверяем, что все объявления одобрены
        foreach ($adIds as $id) {
            $ad = Ad::find($id);
            $this->assertEquals(AdStatus::ACTIVE->value, $ad->status);
            $this->assertTrue($ad->is_published);
        }
    }

    /**
     * Тест обновления объявления админом
     */
    public function test_update_ad_as_admin_logs_changes(): void
    {
        $oldTitle = $this->testAd->title;
        $newData = [
            'title' => 'Updated Title by Admin',
            'description' => 'Updated description'
        ];

        $result = $this->service->updateAdAsAdmin($this->testAd, $newData);

        $this->assertTrue($result);

        // Проверяем обновление
        $this->testAd->refresh();
        $this->assertEquals('Updated Title by Admin', $this->testAd->title);

        // Проверяем логирование
        $this->assertDatabaseHas('admin_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'edit',
            'model_type' => Ad::class,
            'model_id' => $this->testAd->id
        ]);
    }

    /**
     * Тест массового отклонения с причиной
     */
    public function test_bulk_reject_with_reason(): void
    {
        $ads = Ad::factory()->count(2)->create([
            'status' => AdStatus::PENDING_MODERATION->value
        ]);

        $adIds = $ads->pluck('id')->toArray();
        $reason = 'Violation of community guidelines';

        $result = $this->service->performBulkAction($adIds, 'reject', $reason);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['processed']);

        // Проверяем причину отклонения
        foreach ($adIds as $id) {
            $this->assertDatabaseHas('ads', [
                'id' => $id,
                'status' => AdStatus::REJECTED->value,
                'moderation_reason' => $reason
            ]);
        }
    }

    /**
     * Тест обработки ошибок
     */
    public function test_handles_errors_gracefully(): void
    {
        // Создаём несуществующие ID
        $invalidIds = [99999, 99998];

        $result = $this->service->performBulkAction($invalidIds, 'approve');

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['processed']);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * Тест форматирования сообщений
     */
    public function test_formats_result_message_correctly(): void
    {
        $ads = Ad::factory()->count(5)->create([
            'status' => AdStatus::DRAFT->value
        ]);

        $result = $this->service->performBulkAction(
            $ads->pluck('id')->toArray(),
            'archive'
        );

        $this->assertStringContainsString('5 объявлений', $result['message']);
        $this->assertStringContainsString('архивировано', $result['message']);
    }

    /**
     * Тест массового удаления
     */
    public function test_bulk_delete_removes_ads(): void
    {
        $ads = Ad::factory()->count(3)->create();
        $adIds = $ads->pluck('id')->toArray();

        $result = $this->service->performBulkAction($adIds, 'delete');

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['processed']);

        // Проверяем удаление
        foreach ($adIds as $id) {
            $this->assertDatabaseMissing('ads', ['id' => $id]);
        }
    }

    /**
     * Тест логирования массовых действий
     */
    public function test_logs_bulk_actions(): void
    {
        $ads = Ad::factory()->count(2)->create();
        $adIds = $ads->pluck('id')->toArray();

        $this->service->performBulkAction($adIds, 'block', 'Test blocking');

        $this->assertDatabaseHas('admin_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'bulk_block'
        ]);

        // Проверяем metadata
        $log = \App\Domain\Admin\Models\AdminLog::latest()->first();
        $this->assertArrayHasKey('model_ids', $log->metadata);
        $this->assertEquals($adIds, $log->metadata['model_ids']);
    }

    /**
     * Тест статистики действий
     */
    public function test_get_statistics_returns_correct_data(): void
    {
        // Выполняем несколько действий
        $ads = Ad::factory()->count(3)->create();
        $this->service->performBulkAction(
            $ads->pluck('id')->toArray(),
            'approve'
        );

        $stats = $this->service->getStatistics(
            now()->subDay(),
            now()->addDay()
        );

        $this->assertArrayHasKey('total_actions', $stats);
        $this->assertArrayHasKey('approvals', $stats);
        $this->assertArrayHasKey('critical_actions', $stats);
        $this->assertGreaterThan(0, $stats['total_actions']);
    }
}