<?php

namespace Tests\Unit\Domain\Ad\Services;

use Tests\TestCase;
use App\Domain\Ad\Services\AdPricingService;

/**
 * Unit-тесты для AdPricingService
 */
class AdPricingServiceTest extends TestCase
{
    private AdPricingService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdPricingService();
    }
    
    /**
     * Тест извлечения цен из простого массива
     */
    public function test_extract_pricing_from_simple_array()
    {
        $prices = [
            'Классический массаж' => 3000,
            'Тайский массаж' => 4000,
            'СПА программа' => 5000
        ];
        
        $result = $this->service->extractPricing($prices);
        
        $this->assertEquals(3000, $result['min']);
        $this->assertEquals(5000, $result['max']);
        $this->assertEquals('за услугу', $result['unit']);
        $this->assertCount(3, $result['services']);
    }
    
    /**
     * Тест извлечения цен из вложенной структуры с полем services
     */
    public function test_extract_pricing_from_nested_services()
    {
        $prices = [
            'services' => [
                'Массаж 30 мин' => 2000,
                'Массаж 60 мин' => 3500,
                'Массаж 90 мин' => 5000
            ]
        ];
        
        $result = $this->service->extractPricing($prices);
        
        $this->assertEquals(2000, $result['min']);
        $this->assertEquals(5000, $result['max']);
        $this->assertEquals('за услугу', $result['unit']);
    }
    
    /**
     * Тест извлечения цен из структуры с объектами услуг
     */
    public function test_extract_pricing_from_service_objects()
    {
        $prices = [
            ['name' => 'Массаж спины', 'price' => 2500, 'duration' => 30],
            ['name' => 'Полный массаж', 'price' => 4500, 'duration' => 60],
            ['name' => 'СПА комплекс', 'price' => 7000, 'duration' => 120]
        ];
        
        $result = $this->service->extractPricing($prices);
        
        $this->assertEquals(2500, $result['min']);
        $this->assertEquals(7000, $result['max']);
        $this->assertCount(3, $result['services']);
        $this->assertEquals('Массаж спины', $result['services'][0]['name']);
        $this->assertEquals(2500, $result['services'][0]['price']);
    }
    
    /**
     * Тест определения единицы измерения цены по структуре price_info
     */
    public function test_detect_price_unit_from_price_info()
    {
        $prices = [
            'price_info' => [
                'unit' => 'за час',
                'min' => 3000,
                'max' => 5000
            ]
        ];
        
        $result = $this->service->extractPricing($prices);
        
        $this->assertEquals(3000, $result['min']);
        $this->assertEquals(5000, $result['max']);
        $this->assertEquals('за час', $result['unit']);
    }
    
    /**
     * Тест использования fallback цены когда prices пустой
     */
    public function test_use_fallback_price_when_prices_empty()
    {
        $result = $this->service->extractPricing(null, 3500);
        
        $this->assertEquals(3500, $result['min']);
        $this->assertEquals(3500, $result['max']);
        $this->assertEquals('за услугу', $result['unit']);
    }
    
    /**
     * Тест обработки JSON строки с ценами
     */
    public function test_extract_pricing_from_json_string()
    {
        $prices = '{"Массаж": 3000, "СПА": 5000}';
        
        $result = $this->service->extractPricing($prices);
        
        $this->assertEquals(3000, $result['min']);
        $this->assertEquals(5000, $result['max']);
    }
    
    /**
     * Тест обработки невалидного JSON
     */
    public function test_handle_invalid_json()
    {
        $prices = 'invalid json string';
        
        $result = $this->service->extractPricing($prices, 2000);
        
        $this->assertEquals(2000, $result['min']);
        $this->assertEquals(2000, $result['max']);
        $this->assertEquals('за услугу', $result['unit']);
    }
    
    /**
     * Тест извлечения цен с разными единицами измерения
     */
    public function test_extract_pricing_with_different_units()
    {
        $prices1 = [
            'price' => 5000,
            'unit' => 'за ночь'
        ];
        
        $prices2 = [
            'price' => 3000,
            'unit' => 'за час'
        ];
        
        $result1 = $this->service->extractPricing($prices1);
        $result2 = $this->service->extractPricing($prices2);
        
        $this->assertEquals('за ночь', $result1['unit']);
        $this->assertEquals('за час', $result2['unit']);
    }
    
    /**
     * Тест обработки смешанных типов данных в ценах
     */
    public function test_handle_mixed_price_types()
    {
        $prices = [
            'Массаж' => '3000', // строка
            'СПА' => 4500,      // число
            'Комплекс' => null  // null
        ];
        
        $result = $this->service->extractPricing($prices);
        
        $this->assertEquals(3000, $result['min']);
        $this->assertEquals(4500, $result['max']);
        $this->assertCount(2, $result['services']); // null игнорируется
    }
    
    /**
     * Тест извлечения цен из сложной вложенной структуры
     */
    public function test_extract_pricing_from_complex_structure()
    {
        $prices = [
            'categories' => [
                'Массаж' => [
                    'services' => [
                        ['name' => 'Классический', 'price' => 2500],
                        ['name' => 'Расслабляющий', 'price' => 3500]
                    ]
                ],
                'СПА' => [
                    'services' => [
                        ['name' => 'Обертывание', 'price' => 4000],
                        ['name' => 'Комплекс', 'price' => 6000]
                    ]
                ]
            ]
        ];
        
        $result = $this->service->extractPricing($prices);
        
        // Проверяем что сервис может обработать сложную структуру
        $this->assertIsArray($result);
        $this->assertArrayHasKey('min', $result);
        $this->assertArrayHasKey('max', $result);
        $this->assertArrayHasKey('unit', $result);
    }
    
    /**
     * Тест обработки пустого массива
     */
    public function test_handle_empty_array()
    {
        $result = $this->service->extractPricing([]);
        
        $this->assertEquals(2000, $result['min']); // дефолтная цена
        $this->assertEquals(2000, $result['max']);
        $this->assertEquals('за услугу', $result['unit']);
        $this->assertEmpty($result['services']);
    }
    
    /**
     * Тест извлечения цен только из числовых значений
     */
    public function test_extract_only_numeric_prices()
    {
        $prices = [
            'service1' => 3000,
            'service2' => 'бесплатно',
            'service3' => 4500,
            'service4' => '',
            'service5' => 0
        ];
        
        $result = $this->service->extractPricing($prices);
        
        // Должны учитываться только числовые значения больше 0
        $this->assertEquals(3000, $result['min']);
        $this->assertEquals(4500, $result['max']);
        $this->assertCount(2, $result['services']);
    }
}