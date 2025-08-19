<?php

namespace Tests\Unit\Application\Http\Controllers;

use Tests\TestCase;
use App\Application\Http\Controllers\HomeController;
use App\Domain\Ad\Services\AdService;
use App\Domain\Ad\Services\AdTransformService;
use App\Domain\Service\Services\CategoryService;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\DTOs\AdHomePageDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Response as InertiaResponse;
use Mockery;

/**
 * Unit-тесты для HomeController
 */
class HomeControllerTest extends TestCase
{
    private $adServiceMock;
    private $transformerMock;
    private $categoryServiceMock;
    private $controller;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем моки для зависимостей
        $this->adServiceMock = Mockery::mock(AdService::class);
        $this->transformerMock = Mockery::mock(AdTransformService::class);
        $this->categoryServiceMock = Mockery::mock(CategoryService::class);
        
        // Создаем контроллер с моками
        $this->controller = new HomeController(
            $this->adServiceMock,
            $this->transformerMock,
            $this->categoryServiceMock
        );
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    /**
     * Тест успешной загрузки главной страницы с объявлениями
     */
    public function test_index_loads_successfully_with_ads()
    {
        // Подготовка данных
        $ads = collect([
            new Ad(['id' => 1]),
            new Ad(['id' => 2]),
        ]);
        
        $transformedData = collect([
            new AdHomePageDTO([
                'id' => 1,
                'name' => 'Мастер 1',
                'price_from' => 2500,
                'rating' => 4.5,
                'reviews_count' => 10,
                'services' => ['Массаж'],
                'district' => 'Центр',
                'is_verified' => true,
                'is_premium' => false,
            ]),
            new AdHomePageDTO([
                'id' => 2,
                'name' => 'Мастер 2',
                'price_from' => 3000,
                'rating' => 4.8,
                'reviews_count' => 20,
                'services' => ['СПА'],
                'district' => 'Арбат',
                'is_verified' => true,
                'is_premium' => true,
            ])
        ]);
        
        // Настройка моков
        $this->adServiceMock->shouldReceive('getActiveAdsForHome')
            ->once()
            ->with(12)
            ->andReturn($ads);
            
        $this->transformerMock->shouldReceive('transformForHomePage')
            ->once()
            ->with($ads)
            ->andReturn($transformedData);
            
        $this->categoryServiceMock->shouldReceive('getActiveCategories')
            ->once()
            ->andReturn(['Массаж', 'СПА']);
            
        $this->categoryServiceMock->shouldReceive('getDistricts')
            ->once()
            ->andReturn(['Центр', 'Арбат']);
            
        $this->categoryServiceMock->shouldReceive('getPriceRange')
            ->once()
            ->andReturn(['min' => 1000, 'max' => 10000]);
        
        // Выполнение
        $request = new Request();
        $response = $this->controller->index($request);
        
        // Проверка
        $this->assertInstanceOf(InertiaResponse::class, $response);
        
        // Для Inertia response используем другой подход
        $viewData = $response->toResponse($request)->original->getData();
        
        $this->assertArrayHasKey('page', $viewData);
        $this->assertArrayHasKey('props', $viewData['page']);
        $pageData = $viewData['page']['props'];
        
        $this->assertArrayHasKey('masters', $pageData);
        $this->assertArrayHasKey('data', $pageData['masters']);
        $this->assertCount(2, $pageData['masters']['data']);
        $this->assertEquals(2, $pageData['masters']['meta']['total']);
    }
    
    /**
     * Тест загрузки главной страницы без объявлений
     */
    public function test_index_loads_empty_when_no_ads()
    {
        // Настройка моков
        $this->adServiceMock->shouldReceive('getActiveAdsForHome')
            ->once()
            ->with(12)
            ->andReturn(collect([]));
            
        $this->transformerMock->shouldReceive('transformForHomePage')
            ->once()
            ->andReturn(collect([]));
            
        $this->categoryServiceMock->shouldReceive('getActiveCategories')
            ->once()
            ->andReturn([]);
            
        $this->categoryServiceMock->shouldReceive('getDistricts')
            ->once()
            ->andReturn([]);
            
        $this->categoryServiceMock->shouldReceive('getPriceRange')
            ->once()
            ->andReturn(['min' => 1000, 'max' => 10000]);
        
        // Выполнение
        $request = new Request();
        $response = $this->controller->index($request);
        
        // Проверка
        $this->assertInstanceOf(InertiaResponse::class, $response);
        
        $viewData = $response->toResponse($request)->original->getData();
        $pageData = $viewData['page']['props'];
        
        $this->assertArrayHasKey('masters', $pageData);
        // После упрощения всегда возвращаем пустой массив
        $this->assertCount(0, $pageData['masters']['data']);
    }
    
    /**
     * Тест обработки ошибки при загрузке объявлений
     */
    public function test_index_handles_exception_gracefully()
    {
        // Настройка моков с выбросом исключения
        $this->adServiceMock->shouldReceive('getActiveAdsForHome')
            ->once()
            ->andThrow(new \Exception('Database connection error'));
            
        $this->categoryServiceMock->shouldReceive('getActiveCategories')
            ->once()
            ->andReturn([]);
            
        $this->categoryServiceMock->shouldReceive('getDistricts')
            ->once()
            ->andReturn([]);
            
        $this->categoryServiceMock->shouldReceive('getPriceRange')
            ->once()
            ->andReturn(['min' => 1000, 'max' => 10000]);
        
        // Выполнение
        $request = new Request();
        $response = $this->controller->index($request);
        
        // Проверка
        $this->assertInstanceOf(InertiaResponse::class, $response);
        
        $viewData = $response->toResponse($request)->original->getData();
        $pageData = $viewData['page']['props'];
        
        $this->assertArrayHasKey('masters', $pageData);
        // После упрощения всегда возвращаем пустой массив при ошибке
        $this->assertCount(0, $pageData['masters']['data']);
    }
    
    /**
     * Тест передачи фильтров в response
     */
    public function test_index_passes_filters_to_response()
    {
        // Подготовка данных
        $ads = collect([new Ad(['id' => 1])]);
        $transformedData = collect([
            new AdHomePageDTO([
                'id' => 1,
                'name' => 'Test',
                'price_from' => 2500,
                'services' => ['Test'],
                'district' => 'Test',
            ])
        ]);
        
        // Настройка моков
        $this->adServiceMock->shouldReceive('getActiveAdsForHome')
            ->once()
            ->andReturn($ads);
            
        $this->transformerMock->shouldReceive('transformForHomePage')
            ->once()
            ->andReturn($transformedData);
            
        $this->categoryServiceMock->shouldReceive('getActiveCategories')
            ->once()
            ->andReturn([]);
            
        $this->categoryServiceMock->shouldReceive('getDistricts')
            ->once()
            ->andReturn([]);
            
        $this->categoryServiceMock->shouldReceive('getPriceRange')
            ->once()
            ->andReturn(['min' => 1000, 'max' => 10000]);
        
        // Выполнение с фильтрами
        $request = new Request([
            'price_min' => 2000,
            'price_max' => 5000,
            'services' => ['Массаж', 'СПА'],
            'districts' => ['Центр'],
            'city' => 'Санкт-Петербург'
        ]);
        
        $response = $this->controller->index($request);
        
        // Проверка
        $viewData = $response->toResponse($request)->original->getData();
        $pageData = $viewData['page']['props'];
        
        $this->assertEquals(2000, $pageData['filters']['price_min']);
        $this->assertEquals(5000, $pageData['filters']['price_max']);
        $this->assertEquals(['Массаж', 'СПА'], $pageData['filters']['services']);
        $this->assertEquals(['Центр'], $pageData['filters']['districts']);
        $this->assertEquals('Санкт-Петербург', $pageData['currentCity']);
    }
    
    /**
     * Тест что контроллер использует правильные сервисы
     */
    public function test_controller_uses_correct_services()
    {
        // Проверяем что контроллер правильно инжектит зависимости
        $reflection = new \ReflectionClass($this->controller);
        
        $adServiceProperty = $reflection->getProperty('adService');
        $adServiceProperty->setAccessible(true);
        $this->assertInstanceOf(AdService::class, $adServiceProperty->getValue($this->controller));
        
        $transformerProperty = $reflection->getProperty('transformer');
        $transformerProperty->setAccessible(true);
        $this->assertInstanceOf(AdTransformService::class, $transformerProperty->getValue($this->controller));
        
        $categoryServiceProperty = $reflection->getProperty('categoryService');
        $categoryServiceProperty->setAccessible(true);
        $this->assertInstanceOf(CategoryService::class, $categoryServiceProperty->getValue($this->controller));
    }
    
    /**
     * Тест что контроллер не содержит бизнес-логики
     */
    public function test_controller_has_no_business_logic()
    {
        $reflection = new \ReflectionClass(HomeController::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        // Контроллер должен иметь только index метод и конструктор
        $publicMethods = array_filter($methods, function($method) {
            return !$method->isConstructor() && 
                   $method->getDeclaringClass()->getName() === HomeController::class;
        });
        
        // Преобразуем в массив с числовыми ключами
        $publicMethods = array_values($publicMethods);
        
        $this->assertCount(1, $publicMethods);
        $this->assertEquals('index', $publicMethods[0]->getName());
        
        // Проверяем размер контроллера
        $fileName = $reflection->getFileName();
        $lines = file($fileName);
        $this->assertLessThan(100, count($lines), 'Controller should be less than 100 lines');
    }
}