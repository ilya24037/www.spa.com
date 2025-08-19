<?php

namespace App\Domain\Ad\Services;

/**
 * Сервис для работы с ценами объявлений
 * Извлекает и обрабатывает цены из разных форматов
 */
class AdPricingService
{
    private const DEFAULT_PRICE = 2000;
    private const DEFAULT_UNIT = 'за услугу';
    
    /**
     * Извлечение цен из разных форматов
     * 
     * @param mixed $prices JSON строка или массив с ценами
     * @param mixed $fallbackPrice Запасная цена если prices пусто
     * @return array ['min' => float, 'max' => float, 'unit' => string, 'services' => array]
     */
    public function extractPricing($prices, $fallbackPrice = null): array
    {
        // Если нет цен вообще
        if (!$prices && !$fallbackPrice) {
            return $this->getDefaultPricing();
        }
        
        // Пробуем извлечь из поля prices
        if ($prices) {
            $pricing = $this->extractFromPricesField($prices);
            if ($pricing) {
                return $pricing;
            }
        }
        
        // Используем fallback цену
        if ($fallbackPrice) {
            return [
                'min' => (float) $fallbackPrice,
                'max' => (float) $fallbackPrice,
                'unit' => self::DEFAULT_UNIT,
                'services' => []
            ];
        }
        
        return $this->getDefaultPricing();
    }
    
    /**
     * Извлечение цен из поля prices
     * 
     * @param mixed $prices
     * @return array|null
     */
    private function extractFromPricesField($prices): ?array
    {
        $pricesData = $this->parsePricesData($prices);
        
        if (!is_array($pricesData) || empty($pricesData)) {
            return null;
        }
        
        // Проверяем различные форматы данных
        
        // Формат с price_info
        if (isset($pricesData['price_info'])) {
            return [
                'min' => $pricesData['price_info']['min'] ?? self::DEFAULT_PRICE,
                'max' => $pricesData['price_info']['max'] ?? $pricesData['price_info']['min'] ?? self::DEFAULT_PRICE,
                'unit' => $pricesData['price_info']['unit'] ?? self::DEFAULT_UNIT,
                'services' => []
            ];
        }
        
        // Формат с вложенным полем services
        if (isset($pricesData['services']) && is_array($pricesData['services'])) {
            return $this->extractFromServicesFormat($pricesData['services']);
        }
        
        // Формат массива объектов услуг
        if (isset($pricesData[0]) && is_array($pricesData[0])) {
            return $this->extractFromServiceObjects($pricesData);
        }
        
        // Простой ассоциативный массив услуга => цена
        return $this->extractFromSimpleArray($pricesData);
    }
    
    /**
     * Извлечение числовых значений цен
     * 
     * @param array $pricesData
     * @return array
     */
    private function extractNumericPrices(array $pricesData): array
    {
        $values = [];
        
        // Если это массив с ключом 'price'
        if (isset($pricesData[0]['price'])) {
            $values = array_column($pricesData, 'price');
        } else {
            // Иначе извлекаем все числовые значения
            foreach ($pricesData as $key => $value) {
                // Пропускаем служебные поля
                if ($key === 'taxi_included' || $key === 'is_starting_price') {
                    continue;
                }
                
                if (!empty($value) && is_numeric($value)) {
                    $values[] = (float) $value;
                }
            }
        }
        
        return array_filter($values, function($v) {
            return $v > 0;
        });
    }
    
    /**
     * Определение единицы измерения цены
     * 
     * @param array $pricesData
     * @return string
     */
    private function detectPriceUnit(array $pricesData): string
    {
        foreach ($pricesData as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            
            // Проверяем ключи на наличие временных маркеров
            if (strpos($key, '_1h') !== false || strpos($key, '_2h') !== false) {
                return 'за час';
            }
            
            if (strpos($key, '_night') !== false) {
                return 'за ночь';
            }
            
            if (strpos($key, '_30min') !== false) {
                return 'за 30 мин';
            }
        }
        
        return self::DEFAULT_UNIT;
    }
    
    /**
     * Парсинг данных о ценах
     * 
     * @param mixed $prices
     * @return mixed
     */
    private function parsePricesData($prices)
    {
        if (is_string($prices)) {
            try {
                return json_decode($prices, true);
            } catch (\Exception $e) {
                return null;
            }
        }
        
        return $prices;
    }
    
    /**
     * Извлечение из простого ассоциативного массива
     * 
     * @param array $pricesData
     * @return array
     */
    private function extractFromSimpleArray(array $pricesData): array
    {
        $services = [];
        $priceValues = [];
        $unit = self::DEFAULT_UNIT;
        
        // Проверяем наличие единицы измерения в данных
        if (isset($pricesData['unit'])) {
            $unit = $pricesData['unit'];
        }
        
        // Если есть единственное поле price и unit
        if (isset($pricesData['price']) && is_numeric($pricesData['price'])) {
            return [
                'min' => (float) $pricesData['price'],
                'max' => (float) $pricesData['price'],
                'unit' => $unit,
                'services' => []
            ];
        }
        
        foreach ($pricesData as $name => $price) {
            // Пропускаем служебные поля
            if (in_array($name, ['unit', 'price', 'taxi_included', 'is_starting_price'])) {
                continue;
            }
            
            if (is_numeric($price) && $price > 0) {
                $priceValues[] = (float) $price;
                $services[] = [
                    'name' => $name,
                    'price' => (float) $price
                ];
            }
        }
        
        if (empty($priceValues)) {
            return $this->getDefaultPricing();
        }
        
        // Если единица не была явно указана, пытаемся определить по ключам
        if ($unit === self::DEFAULT_UNIT) {
            $unit = $this->detectPriceUnit($pricesData);
        }
        
        return [
            'min' => min($priceValues),
            'max' => max($priceValues),
            'unit' => $unit,
            'services' => $services
        ];
    }
    
    /**
     * Извлечение из формата с вложенными services
     * 
     * @param array $servicesData
     * @return array
     */
    private function extractFromServicesFormat(array $servicesData): array
    {
        return $this->extractFromSimpleArray($servicesData);
    }
    
    /**
     * Извлечение из массива объектов услуг
     * 
     * @param array $pricesData
     * @return array
     */
    private function extractFromServiceObjects(array $pricesData): array
    {
        $services = [];
        $priceValues = [];
        
        foreach ($pricesData as $service) {
            if (isset($service['price']) && is_numeric($service['price']) && $service['price'] > 0) {
                $priceValues[] = (float) $service['price'];
                $services[] = [
                    'name' => $service['name'] ?? 'Услуга',
                    'price' => (float) $service['price']
                ];
            }
        }
        
        if (empty($priceValues)) {
            return $this->getDefaultPricing();
        }
        
        return [
            'min' => min($priceValues),
            'max' => max($priceValues),
            'unit' => self::DEFAULT_UNIT,
            'services' => $services
        ];
    }
    
    /**
     * Возвращает дефолтные значения цен
     * 
     * @return array
     */
    private function getDefaultPricing(): array
    {
        return [
            'min' => self::DEFAULT_PRICE,
            'max' => self::DEFAULT_PRICE,
            'unit' => self::DEFAULT_UNIT,
            'services' => []
        ];
    }
    
    /**
     * Форматирование цены для отображения
     * 
     * @param float $price
     * @return string
     */
    public function formatPrice(float $price): string
    {
        return number_format($price, 0, ',', ' ');
    }
}