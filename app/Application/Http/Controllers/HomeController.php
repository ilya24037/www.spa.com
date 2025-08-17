<?php

namespace App\Application\Http\Controllers;

use App\Domain\Ad\Models\Ad;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Services\MasterService;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Service\Models\MassageCategory;
use App\Domain\Master\DTOs\MasterFilterDTO;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    // ВРЕМЕННО УБИРАЕМ ВСЕ ЗАВИСИМОСТИ
    public function __construct()
    {
        // Пустой конструктор
    }
    public function index(Request $request)
    {
        // Получаем объявления из базы данных вместо MasterProfile
        try {
            $ads = Ad::where('status', 'active')
                ->whereNotNull('address')
                ->take(12)
                ->get();
                
            \Log::info('🗺️ HomeController: найдено активных объявлений', [
                'count' => $ads->count(),
                'ids' => $ads->pluck('id')->toArray()
            ]);
                
            $ads = $ads->map(function ($ad) {
                    // Парсим geo для получения координат
                    $geo = is_string($ad->geo) ? json_decode($ad->geo, true) : $ad->geo;
                    $lat = null;
                    $lng = null;
                    
                    if (is_array($geo)) {
                        // Проверяем два формата координат
                        if (isset($geo['lat']) && isset($geo['lng'])) {
                            // Формат: {"lat": 58.0, "lng": 56.0}
                            $lat = (float)$geo['lat'];
                            $lng = (float)$geo['lng'];
                        } elseif (isset($geo['coordinates']['lat']) && isset($geo['coordinates']['lng'])) {
                            // Формат: {"coordinates": {"lat": 58.0, "lng": 56.0}}
                            $lat = (float)$geo['coordinates']['lat'];
                            $lng = (float)$geo['coordinates']['lng'];
                        }
                    }
                    
                    // Парсим services
                    $services = [];
                    if ($ad->services) {
                        $servicesData = is_string($ad->services) ? json_decode($ad->services, true) : $ad->services;
                        if (is_array($servicesData)) {
                            // Берем первые 3 услуги
                            $services = array_slice(array_keys($servicesData), 0, 3);
                        }
                    }
                    if (empty($services)) {
                        $services = ['Классический массаж'];
                    }
                    
                    // Парсим photos для получения первого фото (логика из ProfileController - работает правильно)
                    $photo = '/images/no-photo.svg';
                    if ($ad->photos) {
                        $photos = is_string($ad->photos) ? json_decode($ad->photos, true) : $ad->photos;
                        if (is_array($photos) && !empty($photos)) {
                            $firstPhoto = $photos[0];
                            // Проверяем разные форматы хранения фото (как в ProfileController)
                            if (is_array($firstPhoto)) {
                                // Фото может быть массивом с ключами preview, url, src
                                $photo = $firstPhoto['preview'] ?? $firstPhoto['url'] ?? $firstPhoto['src'] ?? '/images/no-photo.svg';
                            } elseif (is_string($firstPhoto)) {
                                // Или просто строкой с URL
                                $photo = $firstPhoto;
                            }
                        }
                    }
                    
                    // Парсим prices для получения минимальной цены
                    $priceFrom = 2000;
                    if ($ad->prices) {
                        $prices = is_string($ad->prices) ? json_decode($ad->prices, true) : $ad->prices;
                        if (is_array($prices) && !empty($prices)) {
                            // Проверяем что есть поле price в элементах массива
                            $priceValues = array_column($prices, 'price');
                            if (!empty($priceValues)) {
                                $priceFrom = min($priceValues);
                            }
                        }
                    }
                    
                    // Если prices пусто, пробуем взять из поля price
                    if ($priceFrom === 2000 && $ad->price) {
                        $priceFrom = (float)$ad->price;
                    }
                    
                    return [
                        'id' => $ad->id,
                        'name' => $ad->title ?? 'Мастер',
                        'photo' => $photo,
                        'rating' => 4.5, // Пока используем заглушку
                        'reviews_count' => 0, // Пока используем заглушку
                        'price_from' => $priceFrom,
                        'services' => $services,
                        'district' => $geo['district'] ?? 'Центральный район',
                        'metro' => null, // Пока не используется
                        'experience_years' => 1, // Пока используем заглушку
                        'is_verified' => false,
                        'is_premium' => false,
                        // Добавляем данные для карты
                        'address' => $ad->address,
                        'lat' => $lat,
                        'lng' => $lng,
                        'geo' => $geo
                    ];
                });
                
            // Если объявлений нет в БД, создаем тестовые данные
            if ($ads->isEmpty()) {
                \Log::warning('🗺️ HomeController: объявления пусты, используем тестовые данные');
                $masters = collect($this->getTestMasters());
            } else {
                \Log::info('🗺️ HomeController: используем реальные объявления', [
                    'count' => $ads->count()
                ]);
                $masters = $ads;
            }
        } catch (\Exception $e) {
            // В случае ошибки БД используем тестовые данные
            \Log::error('Ошибка загрузки объявлений', ['error' => $e->getMessage()]);
            $masters = collect($this->getTestMasters());
        }
        
        // Получаем категории
        $categories = $this->getCategories();
        
        return Inertia::render('Home', [
            'masters' => [
                'data' => $masters,
                'meta' => [
                    'total' => $masters->count(),
                    'per_page' => 12,
                    'current_page' => 1
                ]
            ],
            'filters' => [
                'price_min' => $request->get('price_min'),
                'price_max' => $request->get('price_max'),
                'services' => $request->get('services', []),
                'districts' => $request->get('districts', [])
            ],
            'categories' => $categories,
            'districts' => $this->getDistricts(),
            'priceRange' => ['min' => 1000, 'max' => 10000],
            'currentCity' => $request->get('city', 'Москва')
        ]);
    }
    
    /**
     * Тестовые данные мастеров
     */
    private function getTestMasters(): array
    {
        // Массив доступных фото
        $photos = [
            '/images/masters/загруженное.png',
            '/images/masters/загруженное (4).png',
            '/images/masters/загруженное (5).png',
            '/images/masters/загруженное (6).png',
            '/images/masters/загруженное (7).png',
            '/images/masters/загруженное (8).png',
        ];
        
        return [
            [
                'id' => 1,
                'name' => 'Анна Петрова',
                'photo' => $photos[0] ?? '/images/no-photo.svg',
                'rating' => 4.8,
                'reviews_count' => 47,
                'price_from' => 2500,
                'services' => ['Классический массаж', 'Релакс массаж', 'Антицеллюлитный'],
                'district' => 'Центральный',
                'metro' => 'Арбатская',
                'experience_years' => 5,
                'is_verified' => true,
                'is_premium' => true,
            ],
            [
                'id' => 2,
                'name' => 'Ирина Сидорова',
                'photo' => $photos[1] ?? '/images/no-photo.svg',
                'rating' => 4.9,
                'reviews_count' => 89,
                'price_from' => 3000,
                'services' => ['Тайский массаж', 'Стоун-терапия', 'Ароматерапия'],
                'district' => 'Пресненский',
                'metro' => 'Баррикадная',
                'experience_years' => 8,
                'is_verified' => true,
            ],
            [
                'id' => 3,
                'name' => 'Елена Иванова',
                'photo' => $photos[2] ?? '/images/no-photo.svg',
                'rating' => 4.7,
                'reviews_count' => 35,
                'price_from' => 2000,
                'services' => ['Спортивный массаж', 'Лечебный массаж', 'Мануальная терапия'],
                'district' => 'Тверской',
                'metro' => 'Тверская',
                'experience_years' => 3,
            ],
            [
                'id' => 4,
                'name' => 'Мария Козлова',
                'photo' => $photos[3] ?? '/images/no-photo.svg',
                'rating' => 5.0,
                'reviews_count' => 122,
                'price_from' => 3500,
                'services' => ['Лимфодренажный', 'Антицеллюлитный', 'Обертывания'],
                'district' => 'Арбат',
                'metro' => 'Смоленская',
                'experience_years' => 10,
                'is_premium' => true,
            ],
            [
                'id' => 5,
                'name' => 'Ольга Новикова',
                'photo' => $photos[4] ?? '/images/no-photo.svg',
                'rating' => 4.6,
                'reviews_count' => 28,
                'price_from' => 2200,
                'services' => ['Расслабляющий массаж', 'СПА программы', 'Пилинг'],
                'district' => 'Хамовники',
                'metro' => 'Парк Культуры',
                'experience_years' => 4,
                'is_verified' => true,
            ],
            [
                'id' => 6,
                'name' => 'Светлана Морозова',
                'photo' => $photos[5] ?? '/images/no-photo.svg',
                'rating' => 4.9,
                'reviews_count' => 67,
                'price_from' => 2800,
                'services' => ['Медовый массаж', 'Баночный массаж', 'Рефлексотерапия'],
                'district' => 'Замоскворечье',
                'metro' => 'Новокузнецкая',
                'experience_years' => 6,
            ]
        ];
    }
    
    /**
     * Получить список категорий
     */
    private function getCategories(): array
    {
        try {
            return MassageCategory::select('id', 'name', 'icon')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            // Возвращаем тестовые категории
            return [
                ['id' => 1, 'name' => 'Классический массаж', 'icon' => '💆'],
                ['id' => 2, 'name' => 'Тайский массаж', 'icon' => '🧘'],
                ['id' => 3, 'name' => 'Спортивный массаж', 'icon' => '🏃'],
                ['id' => 4, 'name' => 'Лечебный массаж', 'icon' => '🏥'],
                ['id' => 5, 'name' => 'Антицеллюлитный', 'icon' => '✨'],
                ['id' => 6, 'name' => 'СПА программы', 'icon' => '🌺'],
            ];
        }
    }
    
    /**
     * Получить список районов
     */
    private function getDistricts(): array
    {
        return [
            'Центральный',
            'Пресненский',
            'Тверской',
            'Арбат',
            'Хамовники',
            'Замоскворечье',
            'Басманный',
            'Красносельский',
            'Мещанский',
            'Таганский'
        ];
    }
}