<?php

namespace App\Application\Http\Controllers\Api;

use App\Application\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeoLocationController extends Controller
{
    /**
     * Определение страны пользователя по IP
     */
    public function detectCountry(Request $request): JsonResponse
    {
        try {
            // Получаем IP пользователя
            $ip = $request->ip();
            
            // Для локальной разработки возвращаем дефолтную страну
            if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
                return response()->json([
                    'country' => 'RU',
                    'country_name' => 'Russia',
                    'city' => 'Moscow',
                    'region' => 'Moscow',
                    'cached' => false
                ]);
            }
            
            // Проверяем кеш (храним 24 часа)
            $cacheKey = 'geo_location_' . $ip;
            $cached = Cache::get($cacheKey);
            
            if ($cached) {
                $cached['cached'] = true;
                return response()->json($cached);
            }
            
            // Пробуем несколько сервисов по очереди
            $locationData = $this->tryMultipleServices($ip);
            
            if ($locationData) {
                // Сохраняем в кеш на 24 часа
                Cache::put($cacheKey, $locationData, 86400);
                $locationData['cached'] = false;
                return response()->json($locationData);
            }
            
            // Если все сервисы недоступны, возвращаем дефолт
            return response()->json([
                'country' => 'RU',
                'country_name' => 'Russia',
                'city' => null,
                'region' => null,
                'error' => 'Could not detect location',
                'cached' => false
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'country' => 'RU',
                'country_name' => 'Russia',
                'city' => null,
                'region' => null,
                'error' => $e->getMessage(),
                'cached' => false
            ], 200); // Возвращаем 200, чтобы не ломать фронтенд
        }
    }
    
    /**
     * Пробуем несколько сервисов геолокации
     */
    private function tryMultipleServices(string $ip): ?array
    {
        // 1. Пробуем ip-api.com (без HTTPS в бесплатной версии)
        try {
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}");
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'success') {
                    return [
                        'country' => $data['countryCode'] ?? 'RU',
                        'country_name' => $data['country'] ?? 'Russia',
                        'city' => $data['city'] ?? null,
                        'region' => $data['regionName'] ?? null,
                        'service' => 'ip-api'
                    ];
                }
            }
        } catch (\Exception $e) {
            // Продолжаем со следующим сервисом
        }
        
        // 2. Пробуем ipinfo.io
        try {
            $response = Http::timeout(3)->get("https://ipinfo.io/{$ip}/json");
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['country'])) {
                    return [
                        'country' => $data['country'] ?? 'RU',
                        'country_name' => $this->getCountryName($data['country'] ?? 'RU'),
                        'city' => $data['city'] ?? null,
                        'region' => $data['region'] ?? null,
                        'service' => 'ipinfo'
                    ];
                }
            }
        } catch (\Exception $e) {
            // Продолжаем
        }
        
        return null;
    }
    
    /**
     * Получение названия страны по коду
     */
    private function getCountryName(string $code): string
    {
        $countries = [
            'RU' => 'Russia',
            'US' => 'United States',
            'UA' => 'Ukraine',
            'BY' => 'Belarus',
            'KZ' => 'Kazakhstan',
            'DE' => 'Germany',
            'FR' => 'France',
            'GB' => 'United Kingdom',
            // Добавьте другие страны по необходимости
        ];
        
        return $countries[$code] ?? $code;
    }
}