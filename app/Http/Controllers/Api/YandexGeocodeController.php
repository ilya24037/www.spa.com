<?php

namespace App\Http\Controllers\Api;

use App\Application\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class YandexGeocodeController extends Controller
{
    private string $apiKey;
    private string $geocodeUrl = 'https://geocode-maps.yandex.ru/1.x/';
    
    public function __construct()
    {
        $this->apiKey = env('YANDEX_MAPS_API_KEY', '23ff8acc-835f-4e99-8b19-d33c5d346e18');
    }
    
    /**
     * Получить подсказки адресов через Geocoder API
     * Используем Geocoder вместо платного Suggest API
     */
    public function suggest(Request $request)
    {
        $request->validate([
            'text' => 'required|string|min:2|max:255',
            'limit' => 'nullable|integer|min:1|max:10'
        ]);
        
        $query = $request->input('text');
        $limit = $request->input('limit', 5);
        
        // Создаем ключ для кеша
        $cacheKey = 'geocode_suggest_' . md5($query . '_' . $limit);
        
        // Проверяем кеш (5 минут)
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return response()->json($cached);
        }
        
        try {
            // Запрос к Geocoder API
            $response = Http::timeout(5)->get($this->geocodeUrl, [
                'apikey' => $this->apiKey,
                'format' => 'json',
                'geocode' => $query,
                'results' => $limit,
                'lang' => 'ru_RU',
                // Ограничиваем поиск Россией для релевантных результатов
                'rspn' => 1,
                'll' => '37.6173,55.7558', // Центр России (Москва)
                'spn' => '40,40' // Охватываем большую территорию
            ]);
            
            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Geocoder API error',
                    'suggestions' => []
                ], $response->status());
            }
            
            $data = $response->json();
            $suggestions = [];
            
            // Обрабатываем результаты
            $geoObjects = $data['response']['GeoObjectCollection']['featureMember'] ?? [];
            
            foreach ($geoObjects as $item) {
                $geoObject = $item['GeoObject'];
                $address = $geoObject['metaDataProperty']['GeocoderMetaData']['text'] ?? '';
                
                // Получаем координаты
                $pos = $geoObject['Point']['pos'] ?? '';
                $coords = explode(' ', $pos);
                
                if ($address) {
                    $suggestions[] = [
                        'value' => $address,
                        'label' => $address,
                        'coordinates' => count($coords) === 2 ? [
                            'lat' => floatval($coords[1]),
                            'lng' => floatval($coords[0])
                        ] : null
                    ];
                }
            }
            
            $result = [
                'suggestions' => $suggestions,
                'query' => $query
            ];
            
            // Сохраняем в кеш на 5 минут
            Cache::put($cacheKey, $result, 300);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Request failed',
                'message' => $e->getMessage(),
                'suggestions' => []
            ], 500);
        }
    }
    
    /**
     * Геокодирование адреса (получение координат)
     */
    public function geocode(Request $request)
    {
        $request->validate([
            'address' => 'required|string|max:500'
        ]);
        
        $address = $request->input('address');
        
        // Кеш на 1 час для полного геокодирования
        $cacheKey = 'geocode_full_' . md5($address);
        $cached = Cache::get($cacheKey);
        
        if ($cached) {
            return response()->json($cached);
        }
        
        try {
            $response = Http::timeout(5)->get($this->geocodeUrl, [
                'apikey' => $this->apiKey,
                'format' => 'json',
                'geocode' => $address,
                'results' => 1,
                'lang' => 'ru_RU'
            ]);
            
            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Geocoder API error'
                ], $response->status());
            }
            
            $data = $response->json();
            $geoObjects = $data['response']['GeoObjectCollection']['featureMember'] ?? [];
            
            if (empty($geoObjects)) {
                return response()->json([
                    'error' => 'Address not found',
                    'found' => false
                ], 404);
            }
            
            $geoObject = $geoObjects[0]['GeoObject'];
            $pos = $geoObject['Point']['pos'] ?? '';
            $coords = explode(' ', $pos);
            
            $result = [
                'found' => true,
                'address' => $geoObject['metaDataProperty']['GeocoderMetaData']['text'] ?? $address,
                'coordinates' => [
                    'lat' => floatval($coords[1]),
                    'lng' => floatval($coords[0])
                ],
                'components' => $geoObject['metaDataProperty']['GeocoderMetaData']['Address']['Components'] ?? []
            ];
            
            Cache::put($cacheKey, $result, 3600);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Geocoding failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}