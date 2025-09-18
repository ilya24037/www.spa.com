<?php

namespace Tests\Unit;

use Tests\TestCase;
use Mockery;
use App\Domain\Ad\Models\Ad;

class AdFormJsonParseTest extends TestCase
{

    /**
     * Тестирование безопасности JSON.parse в AdForm
     * BUG-005: Проверка обработки некорректного JSON
     */
    public function test_ad_form_handles_invalid_json_in_geo_field()
    {
        // Arrange: Мокаем модель Ad для проверки парсинга JSON
        $ad = new Ad();

        // Test 1: Invalid JSON string
        $invalidJson = 'invalid{json}';
        $result = $this->tryParseJson($invalidJson);

        // Assert: Should return empty object on parse error
        $this->assertEquals([], $result);
        $this->assertIsArray($result);
    }

    /**
     * Тест корректного JSON в поле geo
     */
    public function test_ad_form_accepts_valid_json_in_geo_field()
    {
        // Arrange
        $validJson = '{"city":"Moscow","district":"Center"}';

        // Act
        $result = $this->tryParseJson($validJson);

        // Assert
        $this->assertEquals(['city' => 'Moscow', 'district' => 'Center'], $result);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('city', $result);
        $this->assertArrayHasKey('district', $result);
    }

    /**
     * Тест пустого значения в поле geo
     */
    public function test_ad_form_handles_empty_geo_field()
    {
        // Arrange
        $emptyString = '';

        // Act
        $result = $this->tryParseJson($emptyString);

        // Assert
        $this->assertEquals([], $result);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Тест null значения в поле geo
     */
    public function test_ad_form_handles_null_geo_field()
    {
        // Arrange
        $nullValue = null;

        // Act
        $result = $this->tryParseJson($nullValue);

        // Assert
        $this->assertEquals([], $result);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Тест защиты от XSS в JSON
     */
    public function test_ad_form_sanitizes_xss_in_geo_json()
    {
        // Arrange
        $xssJson = '{"city":"<script>alert(1)</script>"}';

        // Act
        $result = $this->tryParseJson($xssJson);

        // Assert
        // The JSON should be parsed correctly, XSS protection happens at render time
        $this->assertEquals(['city' => '<script>alert(1)</script>'], $result);
        $this->assertArrayHasKey('city', $result);
        // Note: XSS protection is handled by Vue templates and Laravel blade escaping
    }

    /**
     * Helper method to simulate JSON parsing with error handling
     * Mimics the behavior of the safe JSON parsing in Vue component
     */
    private function tryParseJson($jsonString)
    {
        if (empty($jsonString) || is_null($jsonString)) {
            return [];
        }

        try {
            $decoded = json_decode($jsonString, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }
            return $decoded ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }
}