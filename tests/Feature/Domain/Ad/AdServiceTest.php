<?php

namespace Tests\Feature\Domain\Ad;

use Tests\TestCase;
use App\Domain\Ad\Services\AdService;
use App\Domain\Ad\DTOs\AdData;
use App\Domain\User\Models\User;
use App\Enums\AdStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdService $adService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adService = app(AdService::class);
        $this->user = User::factory()->create();
    }

    public function test_can_create_ad_draft()
    {
        $adData = AdData::fromArray([
            'user_id' => $this->user->id,
            'category' => 'beauty',
            'status' => AdStatus::DRAFT->value,
            'content' => [
                'title' => 'Тестовое объявление',
                'description' => 'Описание тестового объявления',
            ],
            'pricing' => [
                'price' => 1000,
            ],
        ]);

        $result = $this->adService->createDraft($adData);

        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('ad', $result);
        $this->assertEquals(AdStatus::DRAFT, $result['ad']->status);
    }

    public function test_can_publish_ad()
    {
        // Создаем черновик
        $adData = AdData::fromArray([
            'user_id' => $this->user->id,
            'category' => 'beauty',
            'status' => AdStatus::DRAFT->value,
            'content' => [
                'title' => 'Тестовое объявление',
                'description' => 'Описание тестового объявления',
            ],
            'pricing' => [
                'price' => 1000,
            ],
            'location' => [
                'address' => 'Москва, ул. Тестовая, 1',
            ],
            'media' => [
                'photos' => ['test.jpg'],
            ],
        ]);

        $createResult = $this->adService->createDraft($adData);
        $ad = $createResult['ad'];

        // Публикуем
        $publishResult = $this->adService->publish($ad->id);

        $this->assertTrue($publishResult['success']);
        $this->assertEquals(AdStatus::WAITING_PAYMENT, $publishResult['ad']->status);
    }

    public function test_cannot_publish_incomplete_ad()
    {
        // Создаем неполный черновик (без фото)
        $adData = AdData::fromArray([
            'user_id' => $this->user->id,
            'category' => 'beauty',
            'status' => AdStatus::DRAFT->value,
            'content' => [
                'title' => 'Тестовое объявление',
                'description' => 'Описание тестового объявления',
            ],
        ]);

        $createResult = $this->adService->createDraft($adData);
        $ad = $createResult['ad'];

        // Пытаемся опубликовать
        $publishResult = $this->adService->publish($ad->id);

        $this->assertFalse($publishResult['success']);
        $this->assertArrayHasKey('errors', $publishResult);
    }

    public function test_can_get_user_ads()
    {
        // Создаем несколько объявлений
        for ($i = 0; $i < 3; $i++) {
            $adData = AdData::fromArray([
                'user_id' => $this->user->id,
                'category' => 'beauty',
                'status' => AdStatus::ACTIVE->value,
                'content' => [
                    'title' => "Объявление {$i}",
                    'description' => 'Описание',
                ],
                'pricing' => [
                    'price' => 1000,
                ],
            ]);
            
            $this->adService->createDraft($adData);
        }

        // Получаем объявления пользователя
        $ads = $this->adService->getUserAds($this->user->id);

        $this->assertCount(3, $ads);
    }
}