<?php

namespace Tests\Unit\Domain\Ad\Actions;

use Tests\TestCase;
use App\Domain\Ad\Actions\PublishAdAction;
use App\Domain\Ad\Repositories\AdRepository;
use App\Domain\Ad\Models\Ad;
use App\Enums\AdStatus;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PublishAdActionTest extends TestCase
{
    use RefreshDatabase;

    private PublishAdAction $action;
    private $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = Mockery::mock(AdRepository::class);
        $this->action = new PublishAdAction($this->mockRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_publish_draft_ad()
    {
        $ad = new Ad([
            'id' => 1,
            'user_id' => 1,
            'status' => AdStatus::DRAFT,
        ]);
        
        // Mock content, pricing, location, and media relationships
        $ad->setRelation('content', (object)['title' => 'Test', 'description' => 'Test desc']);
        $ad->setRelation('pricing', (object)['price' => 1000]);
        $ad->setRelation('location', (object)['address' => 'Test address']);
        $ad->setRelation('media', (object)['photos' => ['test.jpg']]);

        $this->mockRepository
            ->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        $result = $this->action->execute(1);

        $this->assertTrue($result['success']);
        $this->assertEquals('Объявление отправлено на оплату', $result['message']);
        $this->assertEquals(AdStatus::WAITING_PAYMENT, $result['ad']->status);
    }

    public function test_cannot_publish_active_ad()
    {
        $ad = new Ad([
            'id' => 1,
            'user_id' => 1,
            'status' => AdStatus::ACTIVE,
        ]);

        $this->mockRepository
            ->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        $result = $this->action->execute(1);

        $this->assertFalse($result['success']);
        $this->assertEquals('Объявление не может быть опубликовано в текущем статусе', $result['message']);
    }

    public function test_cannot_publish_ad_without_required_fields()
    {
        $ad = new Ad([
            'id' => 1,
            'user_id' => 1,
            'status' => AdStatus::DRAFT,
        ]);
        
        // Mock empty relationships
        $ad->setRelation('content', null);
        $ad->setRelation('pricing', null);
        $ad->setRelation('location', null);
        $ad->setRelation('media', null);

        $this->mockRepository
            ->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        $result = $this->action->execute(1);

        $this->assertFalse($result['success']);
        $this->assertEquals('Заполните все обязательные поля', $result['message']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertNotEmpty($result['errors']);
    }
}