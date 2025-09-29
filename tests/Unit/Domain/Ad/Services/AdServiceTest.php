<?php

namespace Tests\Unit\Domain\Ad\Services;

use Tests\TestCase;
use App\Domain\Ad\Services\AdService;
use App\Domain\Ad\Services\AdMediaService;
use App\Domain\Ad\Repositories\AdRepository;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Models\AdPricing;
use App\Domain\Ad\DTOs\CreateAdDTO;
use App\Domain\Ad\Enums\AdStatus;
use App\Domain\User\Models\User;
use Tests\Traits\SafeRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;

class AdServiceTest extends TestCase
{
    use SafeRefreshDatabase;

    private AdService $service;
    private AdRepository $repository;
    private AdMediaService $mediaService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(AdRepository::class);
        $this->mediaService = Mockery::mock(AdMediaService::class);
        
        $this->service = new AdService(
            $this->repository,
            $this->mediaService
        );

        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Тест создания объявления из DTO
     */
    public function test_create_from_dto_creates_ad_successfully()
    {
        // Arrange
        $dto = new CreateAdDTO([
            'user_id' => $this->user->id,
            'title' => 'Test Ad',
            'specialty' => 'massage',
            'description' => 'Test description',
            'price' => 1000,
            'price_unit' => 'hour',
            'services' => ['релакс', 'классический'],
            'photos' => [
                ['url' => 'photo1.jpg', 'thumbnail' => 'thumb1.jpg'],
                ['url' => 'photo2.jpg', 'thumbnail' => 'thumb2.jpg']
            ]
        ]);

        $ad = new Ad([
            'id' => 1,
            'user_id' => $this->user->id,
            'title' => 'Test Ad',
            'specialty' => 'massage',
            'status' => AdStatus::DRAFT->value
        ]);

        $this->repository->shouldReceive('create')
            ->once()
            ->andReturn($ad);

        $this->mediaService->shouldReceive('syncPhotos')
            ->once()
            ->with($ad, $dto->toArray()['photos']);

        // Act
        $result = $this->service->createFromDTO($dto);

        // Assert
        $this->assertInstanceOf(Ad::class, $result);
        $this->assertEquals($ad->id, $result->id);
        $this->assertEquals(AdStatus::DRAFT->value, $result->status);
    }

    /**
     * Тест создания черновика объявления
     */
    public function test_create_draft_with_minimal_data()
    {
        // Arrange
        $data = ['title' => 'Draft Ad'];

        $ad = new Ad([
            'id' => 1,
            'user_id' => $this->user->id,
            'title' => 'Draft Ad',
            'specialty' => '',
            'description' => '',
            'status' => AdStatus::DRAFT->value
        ]);

        $this->repository->shouldReceive('create')
            ->once()
            ->andReturn($ad);

        // Act
        $result = $this->service->createDraft($data, $this->user);

        // Assert
        $this->assertInstanceOf(Ad::class, $result);
        $this->assertEquals(AdStatus::DRAFT->value, $result->status);
        $this->assertEquals('Draft Ad', $result->title);
    }

    /**
     * Тест публикации объявления
     */
    public function test_publish_ad_successfully()
    {
        // Arrange
        $ad = Mockery::mock(Ad::class)->makePartial();
        $ad->shouldReceive('canBePublished')->once()->andReturn(true);

        $publishedAd = new Ad([
            'id' => 1,
            'status' => AdStatus::WAITING_PAYMENT->value,
            'published_at' => now()
        ]);

        $this->repository->shouldReceive('updateAd')
            ->once()
            ->with($ad, Mockery::on(function ($data) {
                return $data['status'] === AdStatus::WAITING_PAYMENT->value
                    && isset($data['published_at']);
            }))
            ->andReturn($publishedAd);

        // Act
        $result = $this->service->publish($ad);

        // Assert
        $this->assertEquals(AdStatus::WAITING_PAYMENT->value, $result->status);
        $this->assertNotNull($result->published_at);
    }

    /**
     * Тест публикации неготового объявления
     */
    public function test_publish_ad_throws_exception_when_not_ready()
    {
        // Arrange
        $ad = Mockery::mock(Ad::class)->makePartial();
        $ad->shouldReceive('canBePublished')->once()->andReturn(false);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Объявление не готово к публикации');

        // Act
        $this->service->publish($ad);
    }

    /**
     * Тест модерации объявления - одобрение
     */
    public function test_moderate_ad_approved()
    {
        // Arrange
        $ad = new Ad([
            'id' => 1,
            'status' => AdStatus::WAITING_PAYMENT->value
        ]);

        $moderatedAd = new Ad([
            'id' => 1,
            'status' => AdStatus::ACTIVE->value,
            'moderated_at' => now(),
            'expires_at' => now()->addDays(30)
        ]);

        $this->repository->shouldReceive('updateAd')
            ->once()
            ->with($ad, Mockery::on(function ($data) {
                return $data['status'] === AdStatus::ACTIVE->value
                    && isset($data['moderated_at'])
                    && isset($data['expires_at'])
                    && $data['moderation_reason'] === null;
            }))
            ->andReturn($moderatedAd);

        // Act
        $result = $this->service->moderate($ad, true);

        // Assert
        $this->assertEquals(AdStatus::ACTIVE->value, $result->status);
        $this->assertNotNull($result->moderated_at);
        $this->assertNotNull($result->expires_at);
    }

    /**
     * Тест модерации объявления - отклонение
     */
    public function test_moderate_ad_rejected()
    {
        // Arrange
        $ad = new Ad([
            'id' => 1,
            'status' => AdStatus::WAITING_PAYMENT->value
        ]);

        $reason = 'Неприемлемый контент';

        $moderatedAd = new Ad([
            'id' => 1,
            'status' => AdStatus::REJECTED->value,
            'moderated_at' => now(),
            'moderation_reason' => $reason
        ]);

        $this->repository->shouldReceive('updateAd')
            ->once()
            ->with($ad, Mockery::on(function ($data) use ($reason) {
                return $data['status'] === AdStatus::REJECTED->value
                    && isset($data['moderated_at'])
                    && $data['moderation_reason'] === $reason;
            }))
            ->andReturn($moderatedAd);

        // Act
        $result = $this->service->moderate($ad, false, $reason);

        // Assert
        $this->assertEquals(AdStatus::REJECTED->value, $result->status);
        $this->assertEquals($reason, $result->moderation_reason);
    }

    /**
     * Тест архивирования объявления
     */
    public function test_archive_ad()
    {
        // Arrange
        $ad = new Ad(['id' => 1]);
        $archivedAd = new Ad([
            'id' => 1,
            'status' => AdStatus::ARCHIVED->value
        ]);

        $this->repository->shouldReceive('updateAd')
            ->once()
            ->with($ad, ['status' => AdStatus::ARCHIVED->value])
            ->andReturn($archivedAd);

        // Act
        $result = $this->service->archive($ad);

        // Assert
        $this->assertEquals(AdStatus::ARCHIVED->value, $result->status);
    }

    /**
     * Тест добавления услуги к объявлению
     */
    public function test_add_service_to_ad()
    {
        // Arrange
        $ad = Mockery::mock(Ad::class)->makePartial();
        $service = 'Тайский массаж';

        $ad->shouldReceive('appendToJsonField')
            ->once()
            ->with('services', $service);
        
        $ad->shouldReceive('save')->once();

        // Act
        $result = $this->service->addService($ad, $service);

        // Assert
        $this->assertInstanceOf(Ad::class, $result);
    }

    /**
     * Тест удаления услуги из объявления
     */
    public function test_remove_service_from_ad()
    {
        // Arrange
        $ad = Mockery::mock(Ad::class)->makePartial();
        $service = 'Тайский массаж';

        $ad->shouldReceive('removeFromJsonField')
            ->once()
            ->with('services', $service);
        
        $ad->shouldReceive('save')->once();

        // Act
        $result = $this->service->removeService($ad, $service);

        // Assert
        $this->assertInstanceOf(Ad::class, $result);
    }

    /**
     * Тест установки геолокации
     */
    public function test_set_location()
    {
        // Arrange
        $ad = Mockery::mock(Ad::class)->makePartial();
        $lat = 55.7558;
        $lng = 37.6173;
        $address = 'Москва, Красная площадь';

        $ad->shouldReceive('setJsonFieldKey')
            ->once()
            ->with('geo', 'lat', $lat);
        
        $ad->shouldReceive('setJsonFieldKey')
            ->once()
            ->with('geo', 'lng', $lng);
        
        $ad->shouldReceive('setJsonFieldKey')
            ->once()
            ->with('geo', 'address', $address);
        
        $ad->shouldReceive('save')->once();

        // Act
        $result = $this->service->setLocation($ad, $lat, $lng, $address);

        // Assert
        $this->assertInstanceOf(Ad::class, $result);
    }

    /**
     * Тест добавления фотографии
     */
    public function test_add_photo()
    {
        // Arrange
        $ad = Mockery::mock(Ad::class)->makePartial();
        $photoData = [
            'url' => 'photo.jpg',
            'thumbnail' => 'thumb.jpg',
            'size' => 1024
        ];

        $ad->shouldReceive('appendToJsonField')
            ->once()
            ->with('photos', $photoData);
        
        $ad->shouldReceive('save')->once();

        // Act
        $result = $this->service->addPhoto($ad, $photoData);

        // Assert
        $this->assertInstanceOf(Ad::class, $result);
    }

    /**
     * Тест добавления фотографии без обязательных полей
     */
    public function test_add_photo_throws_exception_without_required_fields()
    {
        // Arrange
        $ad = new Ad();
        $photoData = ['url' => 'photo.jpg']; // Отсутствует thumbnail

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Photo data must contain url and thumbnail');

        // Act
        $this->service->addPhoto($ad, $photoData);
    }

    /**
     * Тест обновления расписания
     */
    public function test_update_schedule()
    {
        // Arrange
        $ad = Mockery::mock(Ad::class)->makePartial();
        $scheduleData = [
            'monday' => ['09:00', '18:00'],
            'tuesday' => ['10:00', '19:00']
        ];

        $ad->shouldReceive('mergeJsonField')
            ->once()
            ->with('schedule', $scheduleData);
        
        $ad->shouldReceive('save')->once();

        // Act
        $result = $this->service->updateSchedule($ad, $scheduleData);

        // Assert
        $this->assertInstanceOf(Ad::class, $result);
    }

    /**
     * Тест получения количества услуг
     */
    public function test_get_services_count()
    {
        // Arrange
        $ad = Mockery::mock(Ad::class)->makePartial();
        $services = ['Релакс', 'Классический', 'Тайский'];

        $ad->shouldReceive('getJsonField')
            ->once()
            ->with('services', [])
            ->andReturn($services);

        // Act
        $result = $this->service->getServicesCount($ad);

        // Assert
        $this->assertEquals(3, $result);
    }

    /**
     * Тест проверки наличия услуги
     */
    public function test_has_service()
    {
        // Arrange
        $ad = Mockery::mock(Ad::class)->makePartial();
        $service = 'Релакс';

        $ad->shouldReceive('hasInJsonField')
            ->once()
            ->with('services', $service)
            ->andReturn(true);

        // Act
        $result = $this->service->hasService($ad, $service);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Тест получения координат
     */
    public function test_get_coordinates()
    {
        // Arrange
        $ad = Mockery::mock(Ad::class)->makePartial();
        $lat = 55.7558;
        $lng = 37.6173;

        $ad->shouldReceive('getJsonFieldKey')
            ->once()
            ->with('geo', 'lat')
            ->andReturn($lat);
        
        $ad->shouldReceive('getJsonFieldKey')
            ->once()
            ->with('geo', 'lng')
            ->andReturn($lng);

        // Act
        $result = $this->service->getCoordinates($ad);

        // Assert
        $this->assertEquals(['lat' => $lat, 'lng' => $lng], $result);
    }

    /**
     * Тест получения координат когда их нет
     */
    public function test_get_coordinates_returns_null_when_missing()
    {
        // Arrange
        $ad = Mockery::mock(Ad::class)->makePartial();

        $ad->shouldReceive('getJsonFieldKey')
            ->once()
            ->with('geo', 'lat')
            ->andReturn(null);
        
        $ad->shouldReceive('getJsonFieldKey')
            ->once()
            ->with('geo', 'lng')
            ->andReturn(null);

        // Act
        $result = $this->service->getCoordinates($ad);

        // Assert
        $this->assertNull($result);
    }

    /**
     * Тест пометки объявления как оплаченного
     */
    public function test_mark_as_paid()
    {
        // Arrange
        $ad = new Ad(['id' => 1]);
        $paidAd = new Ad([
            'id' => 1,
            'status' => AdStatus::ACTIVE->value,
            'is_paid' => true,
            'paid_at' => now(),
            'expires_at' => now()->addDays(30)
        ]);

        $this->repository->shouldReceive('updateAd')
            ->once()
            ->with($ad, Mockery::on(function ($data) {
                return $data['status'] === AdStatus::ACTIVE->value
                    && $data['is_paid'] === true
                    && isset($data['paid_at'])
                    && isset($data['expires_at']);
            }))
            ->andReturn($paidAd);

        // Act
        $result = $this->service->markAsPaid($ad);

        // Assert
        $this->assertEquals(AdStatus::ACTIVE->value, $result->status);
        $this->assertTrue($result->is_paid);
        $this->assertNotNull($result->paid_at);
        $this->assertNotNull($result->expires_at);
    }

    /**
     * Тест удаления черновика
     */
    public function test_delete_draft()
    {
        // Arrange
        $ad = new Ad([
            'id' => 1,
            'status' => AdStatus::DRAFT->value
        ]);

        // Мокаем связанные модели
        $ad->setRelation('content', Mockery::mock()->shouldReceive('delete')->once()->getMock());
        $ad->setRelation('pricing', Mockery::mock()->shouldReceive('delete')->once()->getMock());
        $ad->setRelation('schedule', Mockery::mock()->shouldReceive('delete')->once()->getMock());
        $ad->setRelation('media', Mockery::mock()->shouldReceive('delete')->once()->getMock());

        $this->repository->shouldReceive('delete')
            ->once()
            ->with($ad)
            ->andReturn(true);

        // Act
        $result = $this->service->deleteDraft($ad);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Тест удаления не черновика выбрасывает исключение
     */
    public function test_delete_non_draft_throws_exception()
    {
        // Arrange
        $ad = new Ad([
            'id' => 1,
            'status' => AdStatus::ACTIVE->value
        ]);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Можно удалять только черновики');

        // Act
        $this->service->deleteDraft($ad);
    }

    /**
     * Тест подготовки данных для отображения
     */
    public function test_prepare_ad_data_for_view()
    {
        // Arrange
        $ad = new Ad([
            'id' => 1,
            'title' => 'Test Ad',
            'price' => 1000,
            'services' => null,
            'photos' => null,
            'geo' => null
        ]);

        // Act
        $result = $this->service->prepareAdDataForView($ad);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('Test Ad', $result['title']);
        $this->assertEquals(1000, $result['price']);
        $this->assertIsArray($result['services']);
        $this->assertIsArray($result['photos']);
        $this->assertIsArray($result['geo']);
        $this->assertEmpty($result['services']);
    }

    /**
     * Тест проверки возможности редактирования
     */
    public function test_can_edit_draft()
    {
        // Arrange
        $ad = new Ad(['status' => AdStatus::DRAFT->value]);

        // Act
        $result = $this->service->canEdit($ad);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Тест проверки невозможности редактирования активного объявления
     */
    public function test_cannot_edit_active_ad()
    {
        // Arrange
        $ad = new Ad(['status' => AdStatus::ACTIVE->value]);

        // Act
        $result = $this->service->canEdit($ad);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Тест валидации черновика для публикации
     */
    public function test_validate_draft_for_publication_when_ready()
    {
        // Arrange
        $ad = Mockery::mock(Ad::class)->makePartial();
        $ad->status = AdStatus::DRAFT->value;
        $ad->shouldReceive('canBePublished')->once()->andReturn(true);

        // Act
        $result = $this->service->validateDraftForPublication($ad);

        // Assert
        $this->assertTrue($result['ready']);
        $this->assertEmpty($result['missing_fields']);
    }

    /**
     * Тест валидации черновика для публикации когда не готов
     */
    public function test_validate_draft_for_publication_when_not_ready()
    {
        // Arrange
        $ad = Mockery::mock(Ad::class)->makePartial();
        $ad->status = AdStatus::DRAFT->value;
        $missingFields = ['title', 'description', 'price'];
        
        $ad->shouldReceive('canBePublished')->once()->andReturn(false);
        $ad->shouldReceive('getMissingFieldsForPublication')->once()->andReturn($missingFields);

        // Act
        $result = $this->service->validateDraftForPublication($ad);

        // Assert
        $this->assertFalse($result['ready']);
        $this->assertEquals($missingFields, $result['missing_fields']);
    }
}