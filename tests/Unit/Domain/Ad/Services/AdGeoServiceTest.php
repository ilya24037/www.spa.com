<?php

namespace Tests\Unit\Domain\Ad\Services;

use Tests\TestCase;
use App\Domain\Ad\Services\AdGeoService;

/**
 * Unit-тесты для AdGeoService
 */
class AdGeoServiceTest extends TestCase
{
    private AdGeoService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdGeoService();
    }
    
    /**
     * Тест извлечения координат из формата {"lat": 58.0, "lng": 56.0}
     */
    public function test_extract_coordinates_from_simple_format()
    {
        $geo = ['lat' => 58.0, 'lng' => 56.0, 'district' => 'Центральный'];
        
        $result = $this->service->extractCoordinates($geo);
        
        $this->assertEquals(58.0, $result['lat']);
        $this->assertEquals(56.0, $result['lng']);
        $this->assertEquals('Центральный', $result['district']);
    }
    
    /**
     * Тест извлечения координат из формата {"coordinates": {"lat": 58.0, "lng": 56.0}}
     */
    public function test_extract_coordinates_from_nested_format()
    {
        $geo = [
            'coordinates' => ['lat' => 55.7558, 'lng' => 37.6173],
            'district' => 'Арбат'
        ];
        
        $result = $this->service->extractCoordinates($geo);
        
        $this->assertEquals(55.7558, $result['lat']);
        $this->assertEquals(37.6173, $result['lng']);
        $this->assertEquals('Арбат', $result['district']);
    }
    
    /**
     * Тест извлечения координат из JSON строки
     */
    public function test_extract_coordinates_from_json_string()
    {
        $geo = '{"lat": 59.9311, "lng": 30.3609}';
        
        $result = $this->service->extractCoordinates($geo);
        
        $this->assertEquals(59.9311, $result['lat']);
        $this->assertEquals(30.3609, $result['lng']);
        $this->assertNull($result['district']);
    }
    
    /**
     * Тест обработки пустых данных
     */
    public function test_extract_coordinates_from_empty_data()
    {
        $result = $this->service->extractCoordinates(null);
        
        $this->assertNull($result['lat']);
        $this->assertNull($result['lng']);
        $this->assertNull($result['district']);
    }
    
    /**
     * Тест обработки невалидных данных
     */
    public function test_extract_coordinates_from_invalid_data()
    {
        $geo = 'invalid json string';
        
        $result = $this->service->extractCoordinates($geo);
        
        $this->assertNull($result['lat']);
        $this->assertNull($result['lng']);
        $this->assertNull($result['district']);
    }
    
    /**
     * Тест валидации координат (невалидный диапазон)
     */
    public function test_validate_invalid_coordinates()
    {
        $geo = ['lat' => 200, 'lng' => -300]; // Невалидные координаты
        
        $result = $this->service->extractCoordinates($geo);
        
        $this->assertNull($result['lat']);
        $this->assertNull($result['lng']);
    }
    
    /**
     * Тест проверки наличия валидных координат
     */
    public function test_has_valid_coordinates()
    {
        $geoValid = ['lat' => 55.7558, 'lng' => 37.6173];
        $geoInvalid = ['lat' => null, 'lng' => null];
        $geoEmpty = null;
        
        $this->assertTrue($this->service->hasValidCoordinates($geoValid));
        $this->assertFalse($this->service->hasValidCoordinates($geoInvalid));
        $this->assertFalse($this->service->hasValidCoordinates($geoEmpty));
    }
    
    /**
     * Тест обработки координат на границе диапазона
     */
    public function test_boundary_coordinates()
    {
        $geo1 = ['lat' => -180, 'lng' => 180];
        $geo2 = ['lat' => 180, 'lng' => -180];
        
        $result1 = $this->service->extractCoordinates($geo1);
        $result2 = $this->service->extractCoordinates($geo2);
        
        $this->assertEquals(-180, $result1['lat']);
        $this->assertEquals(180, $result1['lng']);
        $this->assertEquals(180, $result2['lat']);
        $this->assertEquals(-180, $result2['lng']);
    }
    
    /**
     * Тест обработки координат как строк
     */
    public function test_coordinates_as_strings()
    {
        $geo = ['lat' => '55.7558', 'lng' => '37.6173'];
        
        $result = $this->service->extractCoordinates($geo);
        
        $this->assertIsFloat($result['lat']);
        $this->assertIsFloat($result['lng']);
        $this->assertEquals(55.7558, $result['lat']);
        $this->assertEquals(37.6173, $result['lng']);
    }
}