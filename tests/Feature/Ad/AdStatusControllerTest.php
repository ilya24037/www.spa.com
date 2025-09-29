<?php

namespace Tests\Feature\Ad;

use Tests\TestCase;
use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Enums\AdStatus;
use Tests\Traits\SafeRefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class AdStatusControllerTest extends TestCase
{
    use SafeRefreshDatabase;

    private User $user;
    private Ad $ad;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->ad = Ad::factory()->create([
            'user_id' => $this->user->id,
            'status' => AdStatus::ACTIVE->value
        ]);
    }

    /**
     * Тест успешной архивации объявления владельцем
     */
    public function test_archive_ad_successfully_by_owner()
    {
        // Act
        $response = $this->actingAs($this->user)
            ->post("/ads/{$this->ad->id}/archive");

        // Assert
        $response->assertRedirect(route('profile.items.archive'));
        $response->assertSessionHas('success', 'Объявление перемещено в архив');

        $this->assertDatabaseHas('ads', [
            'id' => $this->ad->id,
            'status' => AdStatus::ARCHIVED->value
        ]);

        $this->assertDatabaseHas('ads', [
            'id' => $this->ad->id,
            'archived_at' => now()->toDateString() // Приблизительная проверка даты
        ]);
    }

    /**
     * Тест отказа архивации - пользователь не владелец
     */
    public function test_archive_ad_fails_when_not_owner()
    {
        // Arrange
        $otherUser = User::factory()->create();

        // Act
        $response = $this->actingAs($otherUser)
            ->post("/ads/{$this->ad->id}/archive");

        // Assert
        $response->assertStatus(403); // Forbidden

        $this->assertDatabaseHas('ads', [
            'id' => $this->ad->id,
            'status' => AdStatus::ACTIVE->value // Статус не изменился
        ]);
    }

    /**
     * Тест отказа архивации - объявление уже архивировано
     */
    public function test_archive_ad_fails_when_already_archived()
    {
        // Arrange
        $this->ad->update(['status' => AdStatus::ARCHIVED->value]);

        // Act
        $response = $this->actingAs($this->user)
            ->post("/ads/{$this->ad->id}/archive");

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors(['error' => 'Объявление уже в архиве']);

        $this->assertDatabaseHas('ads', [
            'id' => $this->ad->id,
            'status' => AdStatus::ARCHIVED->value
        ]);
    }

    /**
     * Тест отказа архивации - неподходящий статус объявления
     */
    public function test_archive_ad_fails_when_invalid_status()
    {
        // Arrange
        $this->ad->update(['status' => AdStatus::BLOCKED->value]);

        // Act
        $response = $this->actingAs($this->user)
            ->post("/ads/{$this->ad->id}/archive");

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors();

        $this->assertDatabaseHas('ads', [
            'id' => $this->ad->id,
            'status' => AdStatus::BLOCKED->value // Статус не изменился
        ]);
    }

    /**
     * Тест архивации черновика
     */
    public function test_archive_draft_ad_successfully()
    {
        // Arrange
        $draft = Ad::factory()->create([
            'user_id' => $this->user->id,
            'status' => AdStatus::DRAFT->value
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->post("/ads/{$draft->id}/archive");

        // Assert
        $response->assertRedirect(route('profile.items.archive'));
        $response->assertSessionHas('success', 'Объявление перемещено в архив');

        $this->assertDatabaseHas('ads', [
            'id' => $draft->id,
            'status' => AdStatus::ARCHIVED->value
        ]);
    }

    /**
     * Тест требования авторизации
     */
    public function test_archive_requires_authentication()
    {
        // Act
        $response = $this->post("/ads/{$this->ad->id}/archive");

        // Assert
        $response->assertRedirect('/login');
    }

    /**
     * Тест с несуществующим объявлением
     */
    public function test_archive_nonexistent_ad_returns_404()
    {
        // Act
        $response = $this->actingAs($this->user)
            ->post("/ads/99999/archive");

        // Assert
        $response->assertStatus(404);
    }
}