<?php

namespace Tests\Unit\Domain\Ad\Actions;

use Tests\TestCase;
use App\Domain\Ad\Actions\PublishAdAction;
use App\Domain\Ad\Repositories\AdRepository;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Models\AdContent;
use App\Domain\Ad\Models\AdPricing;
use App\Domain\Ad\Models\AdLocation;
use App\Domain\Ad\Models\AdMedia;
use App\Domain\Ad\Enums\AdStatus;
use Tests\Traits\SafeRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;

class PublishAdActionTest extends TestCase
{
    use SafeRefreshDatabase;

    private PublishAdAction $action;
    private AdRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(AdRepository::class);
        $this->action = new PublishAdAction($this->repository);

        Log::shouldReceive('info', 'error')->andReturnNull();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Тест успешной публикации объявления из черновика
     */
    public function test_execute_publishes_draft_ad_successfully()
    {
        // Arrange
        $ad = $this->createValidAd(AdStatus::DRAFT);
        
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        $this->repository->shouldReceive('updateAd')
            ->once()
            ->with($ad, Mockery::on(function ($data) {
                return $data['status'] === AdStatus::WAITING_PAYMENT->value
                    && isset($data['published_at']);
            }))
            ->andReturn($ad);

        // Act
        $result = $this->action->execute(1);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Объявление отправлено на оплату', $result['message']);
        $this->assertArrayHasKey('ad', $result);
    }

    /**
     * Тест успешной публикации архивированного объявления
     */
    public function test_execute_publishes_archived_ad_successfully()
    {
        // Arrange
        $ad = $this->createValidAd(AdStatus::ARCHIVED);
        
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        $this->repository->shouldReceive('updateAd')
            ->once()
            ->with($ad, Mockery::any())
            ->andReturn($ad);

        // Act
        $result = $this->action->execute(1);

        // Assert
        $this->assertTrue($result['success']);
    }

    /**
     * Тест неудачной публикации активного объявления
     */
    public function test_execute_fails_when_ad_is_already_active()
    {
        // Arrange
        $ad = $this->createValidAd(AdStatus::ACTIVE);
        
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        // Act
        $result = $this->action->execute(1);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Объявление не может быть опубликовано в текущем статусе', $result['message']);
    }

    /**
     * Тест неудачной публикации без заголовка
     */
    public function test_execute_fails_without_title()
    {
        // Arrange
        $ad = $this->createValidAd(AdStatus::DRAFT);
        $ad->content->title = null;
        
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        // Act
        $result = $this->action->execute(1);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Заполните все обязательные поля', $result['message']);
        $this->assertContains('Не указан заголовок объявления', $result['errors']);
    }

    /**
     * Тест неудачной публикации без описания
     */
    public function test_execute_fails_without_description()
    {
        // Arrange
        $ad = $this->createValidAd(AdStatus::DRAFT);
        $ad->content->description = '';
        
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        // Act
        $result = $this->action->execute(1);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertContains('Не указано описание объявления', $result['errors']);
    }

    /**
     * Тест неудачной публикации без цены
     */
    public function test_execute_fails_without_price()
    {
        // Arrange
        $ad = $this->createValidAd(AdStatus::DRAFT);
        $ad->pricing->price = null;
        
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        // Act
        $result = $this->action->execute(1);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertContains('Не указана цена', $result['errors']);
    }

    /**
     * Тест неудачной публикации без адреса
     */
    public function test_execute_fails_without_address()
    {
        // Arrange
        $ad = $this->createValidAd(AdStatus::DRAFT);
        $ad->location->address = '';
        
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        // Act
        $result = $this->action->execute(1);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertContains('Не указан адрес', $result['errors']);
    }

    /**
     * Тест неудачной публикации без фотографий
     */
    public function test_execute_fails_without_photos()
    {
        // Arrange
        $ad = $this->createValidAd(AdStatus::DRAFT);
        $ad->media->photos = [];
        
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        // Act
        $result = $this->action->execute(1);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertContains('Добавьте хотя бы одну фотографию', $result['errors']);
    }

    /**
     * Тест неудачной публикации без связанных компонентов
     */
    public function test_execute_fails_without_content()
    {
        // Arrange
        $ad = Mockery::mock(Ad::class)->makePartial();
        $ad->id = 1;
        $ad->status = AdStatus::DRAFT->value;
        $ad->shouldReceive('getAttribute')->with('content')->andReturn(null);
        $ad->shouldReceive('getAttribute')->with('pricing')->andReturn(new AdPricing(['price' => 1000]));
        $ad->shouldReceive('getAttribute')->with('location')->andReturn(new AdLocation(['address' => 'Test']));
        $ad->shouldReceive('getAttribute')->with('media')->andReturn(new AdMedia(['photos' => ['photo.jpg']]));
        
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        // Act
        $result = $this->action->execute(1);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertContains('Не указан заголовок объявления', $result['errors']);
        $this->assertContains('Не указано описание объявления', $result['errors']);
    }

    /**
     * Тест обработки исключения при публикации
     */
    public function test_execute_handles_exception()
    {
        // Arrange
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andThrow(new \Exception('Database error'));

        // Act
        $result = $this->action->execute(1);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Ошибка при публикации объявления', $result['message']);
    }

    /**
     * Тест множественных ошибок валидации
     */
    public function test_execute_returns_multiple_validation_errors()
    {
        // Arrange
        $ad = Mockery::mock(Ad::class)->makePartial();
        $ad->id = 1;
        $ad->status = AdStatus::DRAFT->value;
        $ad->shouldReceive('getAttribute')->with('content')->andReturn(null);
        $ad->shouldReceive('getAttribute')->with('pricing')->andReturn(null);
        $ad->shouldReceive('getAttribute')->with('location')->andReturn(null);
        $ad->shouldReceive('getAttribute')->with('media')->andReturn(null);
        
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        // Act
        $result = $this->action->execute(1);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertCount(5, $result['errors']); // title, description, price, address, photos
    }

    /**
     * Тест транзакционности операции
     */
    public function test_execute_uses_transaction()
    {
        // Arrange
        $ad = $this->createValidAd(AdStatus::DRAFT);
        
        $this->repository->shouldReceive('findOrFail')
            ->once()
            ->with(1)
            ->andReturn($ad);

        $this->repository->shouldReceive('updateAd')
            ->once()
            ->andThrow(new \Exception('Update failed'));

        // Act
        $result = $this->action->execute(1);

        // Assert
        $this->assertFalse($result['success']);
        // Если бы транзакция не работала, статус объявления мог бы измениться
        $this->assertEquals(AdStatus::DRAFT->value, $ad->status);
    }

    /**
     * Создать валидное объявление для тестов
     */
    private function createValidAd($status): Ad
    {
        $ad = Mockery::mock(Ad::class)->makePartial();
        $ad->id = 1;
        $ad->user_id = 1;
        $ad->status = $status->value;

        $content = new AdContent([
            'title' => 'Test Ad',
            'description' => 'Test Description'
        ]);

        $pricing = new AdPricing([
            'price' => 1000
        ]);

        $location = new AdLocation([
            'address' => 'Test Address'
        ]);

        $media = new AdMedia([
            'photos' => ['photo1.jpg', 'photo2.jpg']
        ]);

        $ad->shouldReceive('getAttribute')->with('content')->andReturn($content);
        $ad->shouldReceive('getAttribute')->with('pricing')->andReturn($pricing);
        $ad->shouldReceive('getAttribute')->with('location')->andReturn($location);
        $ad->shouldReceive('getAttribute')->with('media')->andReturn($media);

        return $ad;
    }
}