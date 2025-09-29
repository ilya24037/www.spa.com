<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Enums\AdStatus;
use Tests\Traits\SafeRefreshDatabase;

class BulkActionsTest extends TestCase
{
    use SafeRefreshDatabase;

    private User $admin;
    private User $regularUser;
    private array $testAds;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаём админа
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com'
        ]);

        // Создаём обычного пользователя
        $this->regularUser = User::factory()->create([
            'role' => 'client',
            'email' => 'user@test.com'
        ]);

        // Создаём тестовые объявления
        $this->testAds = Ad::factory()->count(5)->create([
            'status' => AdStatus::PENDING_MODERATION->value,
            'user_id' => $this->regularUser->id
        ])->toArray();
    }

    /**
     * Админ может выполнять массовое одобрение
     */
    public function test_admin_can_bulk_approve_ads(): void
    {
        $adIds = array_column($this->testAds, 'id');

        $response = $this->actingAs($this->admin)
            ->post('/profile/admin/ads/bulk', [
                'ids' => $adIds,
                'action' => 'approve'
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        // Проверяем, что все объявления одобрены
        foreach ($adIds as $id) {
            $this->assertDatabaseHas('ads', [
                'id' => $id,
                'status' => AdStatus::ACTIVE->value,
                'is_published' => true
            ]);
        }

        // Проверяем логирование
        $this->assertDatabaseHas('admin_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'bulk_approve'
        ]);
    }

    /**
     * Обычный пользователь не может выполнять массовые действия
     */
    public function test_non_admin_cannot_bulk_action(): void
    {
        $adIds = array_column($this->testAds, 'id');

        $response = $this->actingAs($this->regularUser)
            ->post('/profile/admin/ads/bulk', [
                'ids' => $adIds,
                'action' => 'approve'
            ]);

        $response->assertForbidden();

        // Проверяем, что объявления не изменились
        foreach ($adIds as $id) {
            $this->assertDatabaseHas('ads', [
                'id' => $id,
                'status' => AdStatus::PENDING_MODERATION->value
            ]);
        }
    }

    /**
     * Массовое действие с несуществующими ID должно провалиться
     */
    public function test_bulk_action_with_invalid_ids_fails(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/profile/admin/ads/bulk', [
                'ids' => [99999, 99998],
                'action' => 'approve'
            ]);

        $response->assertSessionHasErrors('ids.0')
            ->assertSessionHasErrors('ids.1');
    }

    /**
     * Массовое отклонение с причиной
     */
    public function test_bulk_reject_with_reason(): void
    {
        $adIds = array_column($this->testAds, 'id');
        $reason = 'Нарушение правил сообщества';

        $response = $this->actingAs($this->admin)
            ->post('/profile/admin/ads/bulk', [
                'ids' => $adIds,
                'action' => 'reject',
                'reason' => $reason
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        // Проверяем, что все объявления отклонены с причиной
        foreach ($adIds as $id) {
            $this->assertDatabaseHas('ads', [
                'id' => $id,
                'status' => AdStatus::REJECTED->value,
                'moderation_reason' => $reason
            ]);
        }
    }

    /**
     * Массовое удаление объявлений
     */
    public function test_bulk_delete_ads(): void
    {
        $adIds = array_column($this->testAds, 'id');

        $response = $this->actingAs($this->admin)
            ->post('/profile/admin/ads/bulk', [
                'ids' => $adIds,
                'action' => 'delete'
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        // Проверяем, что все объявления удалены
        foreach ($adIds as $id) {
            $this->assertDatabaseMissing('ads', ['id' => $id]);
        }
    }

    /**
     * Проверка rate limiting
     */
    public function test_bulk_actions_are_rate_limited(): void
    {
        $adIds = array_column($this->testAds, 'id');

        // Выполняем 10 запросов (лимит)
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->admin)
                ->post('/profile/admin/ads/bulk', [
                    'ids' => $adIds,
                    'action' => 'approve'
                ]);
        }

        // 11-й запрос должен быть отклонён
        $response = $this->actingAs($this->admin)
            ->post('/profile/admin/ads/bulk', [
                'ids' => $adIds,
                'action' => 'approve'
            ]);

        $response->assertStatus(429); // Too Many Requests
    }

    /**
     * Проверка валидации action
     */
    public function test_invalid_action_is_rejected(): void
    {
        $adIds = array_column($this->testAds, 'id');

        $response = $this->actingAs($this->admin)
            ->post('/profile/admin/ads/bulk', [
                'ids' => $adIds,
                'action' => 'invalid_action'
            ]);

        $response->assertSessionHasErrors('action');
    }

    /**
     * Транзакция откатывается при ошибке
     */
    public function test_transaction_rollback_on_error(): void
    {
        // Создаём объявление с блокировкой
        $lockedAd = Ad::factory()->create([
            'status' => AdStatus::BLOCKED->value
        ]);

        $adIds = array_merge(
            array_column($this->testAds, 'id'),
            [$lockedAd->id]
        );

        $initialCount = Ad::whereIn('id', $adIds)
            ->where('status', AdStatus::ACTIVE->value)
            ->count();

        // Пытаемся одобрить все, включая заблокированное
        $response = $this->actingAs($this->admin)
            ->post('/profile/admin/ads/bulk', [
                'ids' => $adIds,
                'action' => 'approve'
            ]);

        // Проверяем, что количество активных не изменилось из-за отката
        $finalCount = Ad::whereIn('id', $adIds)
            ->where('status', AdStatus::ACTIVE->value)
            ->count();

        $this->assertEquals($initialCount, $finalCount);
    }
}