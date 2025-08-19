<?php

namespace Tests\Unit\Domain\Ad\Services;

use Tests\TestCase;
use App\Domain\Ad\Services\AdTransformService;
use App\Domain\Ad\Services\AdGeoService;
use App\Domain\Ad\Services\AdPricingService;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\DTOs\AdHomePageDTO;
use App\Domain\User\Models\User;
use Illuminate\Support\Collection;
use Mockery;

/**
 * Unit-тесты для AdTransformService
 */
class AdTransformServiceTest extends TestCase
{
    private AdTransformService $service;
    private $geoServiceMock;
    private $pricingServiceMock;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем моки для зависимостей
        $this->geoServiceMock = Mockery::mock(AdGeoService::class);
        $this->pricingServiceMock = Mockery::mock(AdPricingService::class);
        
        $this->service = new AdTransformService(
            $this->geoServiceMock,
            $this->pricingServiceMock
        );
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    /**
     * Тест трансформации коллекции объявлений для главной страницы
     */
    public function test_transform_collection_for_home_page()
    {
        // Создаем тестовое объявление
        $ad = Mockery::mock(Ad::class);
        $ad->id = 1;
        $ad->title = 'Массаж профессиональный';
        $ad->address = 'ул. Пушкина, д. 10';
        $ad->geo = ['lat' => 55.7558, 'lng' => 37.6173];
        $ad->prices = ['Массаж' => 3000];
        $ad->price = 3000;
        $ad->photos = '[{"url": "/photo1.jpg"}]';
        $ad->services = '{"Классический массаж": 3000}';
        $ad->experience = 5;
        $ad->status = 'active';
        $ad->moderated_at = now();
        
        // Мокаем связанного пользователя
        $user = Mockery::mock(User::class);
        $user->name = 'Мастер Иван';
        $user->is_verified = true;
        $ad->user = $user;
        
        // Настраиваем моки
        $this->geoServiceMock->shouldReceive('extractCoordinates')
            ->andReturn(['lat' => 55.7558, 'lng' => 37.6173, 'district' => 'Центральный']);
        
        $this->geoServiceMock->shouldReceive('hasValidCoordinates')
            ->andReturn(true);
            
        $this->pricingServiceMock->shouldReceive('extractPricing')
            ->andReturn(['min' => 3000, 'max' => 3000, 'unit' => 'за услугу', 'services' => []]);
        
        // Мокаем методы связей
        $user->shouldReceive('getAttribute')->with('receivedReviews')->andReturn(null);
        $user->shouldReceive('getAttribute')->with('subscription')->andReturn(null);
        $user->shouldReceive('getAttribute')->with('created_at')->andReturn(now()->subYears(5));
        
        $ads = collect([$ad]);
        
        $result = $this->service->transformForHomePage($ads);
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(AdHomePageDTO::class, $result->first());
    }
    
    /**
     * Тест трансформации одного объявления с полными данными
     */
    public function test_transform_single_ad_with_full_data()
    {
        $ad = Mockery::mock(Ad::class);
        $ad->id = 1;
        $ad->title = 'СПА массаж';
        $ad->address = 'метро Арбатская, ул. Новый Арбат';
        $ad->geo = '{"lat": 55.7522, "lng": 37.5956, "district": "Арбат", "metro": "Арбатская"}';
        $ad->prices = ['СПА' => 5000, 'Массаж' => 3000];
        $ad->price = 3000;
        $ad->photos = '[{"preview": "/preview1.jpg", "url": "/photo1.jpg"}]';
        $ad->services = '["СПА массаж", "Классический массаж"]';
        $ad->experience = 7;
        $ad->status = 'active';
        $ad->moderated_at = now();
        
        $user = Mockery::mock(User::class);
        $user->name = 'Елена';
        $user->is_verified = true;
        $user->created_at = now()->subYears(7);
        
        // Мокаем отзывы
        $reviewsMock = Mockery::mock();
        $reviewsMock->shouldReceive('avg')->with('rating')->andReturn(4.8);
        $reviewsMock->shouldReceive('count')->andReturn(25);
        
        $user->receivedReviews = $reviewsMock;
        $user->shouldReceive('getAttribute')->with('receivedReviews')->andReturn($reviewsMock);
        
        // Мокаем подписку
        $subscriptionMock = Mockery::mock();
        $subscriptionMock->plan = 'premium';
        $subscriptionMock->expires_at = now()->addMonth();
        $user->subscription = $subscriptionMock;
        $user->shouldReceive('getAttribute')->with('subscription')->andReturn($subscriptionMock);
        $user->shouldReceive('getAttribute')->with('created_at')->andReturn(now()->subYears(7));
        
        $ad->user = $user;
        
        // Настраиваем моки сервисов
        $this->geoServiceMock->shouldReceive('extractCoordinates')
            ->andReturn(['lat' => 55.7522, 'lng' => 37.5956, 'district' => 'Арбат']);
            
        $this->pricingServiceMock->shouldReceive('extractPricing')
            ->andReturn([
                'min' => 3000,
                'max' => 5000,
                'unit' => 'за услугу',
                'services' => [
                    ['name' => 'СПА', 'price' => 5000],
                    ['name' => 'Массаж', 'price' => 3000]
                ]
            ]);
        
        $ads = collect([$ad]);
        $result = $this->service->transformForHomePage($ads);
        $dto = $result->first();
        
        $this->assertEquals(1, $dto->id);
        $this->assertEquals('СПА массаж', $dto->name);
        $this->assertEquals('/preview1.jpg', $dto->photo);
        $this->assertEquals(4.8, $dto->rating);
        $this->assertEquals(25, $dto->reviews_count);
        $this->assertEquals(3000, $dto->price_from);
        $this->assertEquals('Арбат', $dto->district);
        $this->assertEquals('Арбатская', $dto->metro);
        $this->assertEquals(7, $dto->experience_years);
        $this->assertTrue($dto->is_verified);
        $this->assertTrue($dto->is_premium);
    }
    
    /**
     * Тест трансформации объявления без пользователя
     */
    public function test_transform_ad_without_user()
    {
        $ad = Mockery::mock(Ad::class);
        $ad->id = 2;
        $ad->title = null;
        $ad->address = 'Центр города';
        $ad->geo = null;
        $ad->prices = null;
        $ad->price = 2500;
        $ad->photos = null;
        $ad->services = null;
        $ad->experience = null;
        $ad->status = 'draft';
        $ad->moderated_at = null;
        $ad->user = null;
        
        $this->geoServiceMock->shouldReceive('extractCoordinates')
            ->andReturn(['lat' => null, 'lng' => null, 'district' => null]);
            
        $this->pricingServiceMock->shouldReceive('extractPricing')
            ->andReturn(['min' => 2500, 'max' => 2500, 'unit' => 'за услугу', 'services' => []]);
        
        $ads = collect([$ad]);
        $result = $this->service->transformForHomePage($ads);
        $dto = $result->first();
        
        $this->assertEquals('Мастер', $dto->name); // Дефолтное имя
        $this->assertEquals('/images/no-photo.svg', $dto->photo); // Дефолтное фото
        $this->assertEquals(4.5, $dto->rating); // Дефолтный рейтинг
        $this->assertEquals(0, $dto->reviews_count);
        $this->assertEquals(1, $dto->experience_years); // Минимальный опыт
        $this->assertFalse($dto->is_verified);
        $this->assertFalse($dto->is_premium);
    }
    
    /**
     * Тест трансформации для карты (фильтрация по координатам)
     */
    public function test_transform_for_map_filters_invalid_coordinates()
    {
        $ad1 = Mockery::mock(Ad::class);
        $ad1->id = 1;
        $ad1->geo = ['lat' => 55.7558, 'lng' => 37.6173];
        $ad1->title = 'Массаж 1';
        $ad1->address = 'Адрес 1';
        $ad1->price = 3000;
        $ad1->photos = null;
        $ad1->services = null;
        $ad1->prices = null;
        $ad1->experience = 5;
        $ad1->status = 'active';
        $ad1->moderated_at = now();
        $ad1->user = null;
        
        $ad2 = Mockery::mock(Ad::class);
        $ad2->id = 2;
        $ad2->geo = null; // Нет координат
        $ad2->title = 'Массаж 2';
        $ad2->address = 'Адрес 2';
        $ad2->price = 4000;
        $ad2->photos = null;
        $ad2->services = null;
        $ad2->prices = null;
        $ad2->experience = 3;
        $ad2->status = 'active';
        $ad2->moderated_at = now();
        $ad2->user = null;
        
        // Настраиваем моки для проверки координат
        $this->geoServiceMock->shouldReceive('hasValidCoordinates')
            ->with($ad1->geo)->andReturn(true);
        $this->geoServiceMock->shouldReceive('hasValidCoordinates')
            ->with($ad2->geo)->andReturn(false);
            
        $this->geoServiceMock->shouldReceive('extractCoordinates')
            ->with($ad1->geo)->andReturn(['lat' => 55.7558, 'lng' => 37.6173, 'district' => 'Центр']);
            
        $this->pricingServiceMock->shouldReceive('extractPricing')
            ->andReturn(['min' => 3000, 'max' => 3000, 'unit' => 'за услугу', 'services' => []]);
        
        $ads = collect([$ad1, $ad2]);
        $result = $this->service->transformForMap($ads);
        
        // Должно остаться только одно объявление с валидными координатами
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result->first()['id']);
        $this->assertEquals(55.7558, $result->first()['lat']);
        $this->assertEquals(37.6173, $result->first()['lng']);
    }
    
    /**
     * Тест извлечения метро из адреса
     */
    public function test_extract_metro_from_address()
    {
        $ad = Mockery::mock(Ad::class);
        $ad->id = 1;
        $ad->title = 'Массаж';
        $ad->address = 'метро Сокольники, ул. Русаковская';
        $ad->geo = '{"lat": 55.7892, "lng": 37.6798}';
        $ad->prices = null;
        $ad->price = 3500;
        $ad->photos = null;
        $ad->services = null;
        $ad->experience = null;
        $ad->status = 'active';
        $ad->moderated_at = now();
        $ad->user = null;
        
        $this->geoServiceMock->shouldReceive('extractCoordinates')
            ->andReturn(['lat' => 55.7892, 'lng' => 37.6798, 'district' => null]);
            
        $this->pricingServiceMock->shouldReceive('extractPricing')
            ->andReturn(['min' => 3500, 'max' => 3500, 'unit' => 'за услугу', 'services' => []]);
        
        $ads = collect([$ad]);
        $result = $this->service->transformForHomePage($ads);
        $dto = $result->first();
        
        $this->assertEquals('Сокольники', $dto->metro);
    }
    
    /**
     * Тест обработки различных форматов фотографий
     */
    public function test_handle_different_photo_formats()
    {
        // Тест 1: Массив с preview
        $ad1 = $this->createMockAd(1);
        $ad1->photos = '[{"preview": "/preview.jpg", "url": "/full.jpg"}]';
        
        // Тест 2: Массив с url
        $ad2 = $this->createMockAd(2);
        $ad2->photos = '[{"url": "/photo.jpg"}]';
        
        // Тест 3: Простой массив строк
        $ad3 = $this->createMockAd(3);
        $ad3->photos = '["/simple-photo.jpg"]';
        
        // Тест 4: Пустые фото
        $ad4 = $this->createMockAd(4);
        $ad4->photos = null;
        
        $this->setupDefaultMocks();
        
        $ads = collect([$ad1, $ad2, $ad3, $ad4]);
        $result = $this->service->transformForHomePage($ads);
        
        $this->assertEquals('/preview.jpg', $result[0]->photo);
        $this->assertEquals('/photo.jpg', $result[1]->photo);
        $this->assertEquals('/simple-photo.jpg', $result[2]->photo);
        $this->assertEquals('/images/no-photo.svg', $result[3]->photo);
    }
    
    /**
     * Создание мок объявления для тестов
     */
    private function createMockAd($id)
    {
        $ad = Mockery::mock(Ad::class);
        $ad->id = $id;
        $ad->title = "Test Ad $id";
        $ad->address = 'Test Address';
        $ad->geo = null;
        $ad->prices = null;
        $ad->price = 3000;
        $ad->services = null;
        $ad->experience = null;
        $ad->status = 'active';
        $ad->moderated_at = now();
        $ad->user = null;
        
        return $ad;
    }
    
    /**
     * Настройка дефолтных моков
     */
    private function setupDefaultMocks()
    {
        $this->geoServiceMock->shouldReceive('extractCoordinates')
            ->andReturn(['lat' => null, 'lng' => null, 'district' => null]);
            
        $this->pricingServiceMock->shouldReceive('extractPricing')
            ->andReturn(['min' => 3000, 'max' => 3000, 'unit' => 'за услугу', 'services' => []]);
    }
}