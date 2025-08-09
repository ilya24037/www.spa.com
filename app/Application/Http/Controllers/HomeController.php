<?php

namespace App\Application\Http\Controllers;

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
        // Получаем мастеров из базы данных или создаем тестовые данные
        try {
            $masters = MasterProfile::with(['user', 'masterServices', 'primaryLocation', 'locations'])
                ->where('status', 'active') // Используем правильное поле status вместо is_active
                ->take(12)
                ->get()
                ->map(function ($master) {
                    // Получаем основную локацию или первую доступную
                    $location = $master->primaryLocation ?? $master->locations->first();
                    
                    // Получаем услуги
                    $services = $master->masterServices->pluck('name')->take(3)->toArray();
                    if (empty($services)) {
                        $services = ['Классический массаж']; // Дефолтная услуга
                    }
                    
                    return [
                        'id' => $master->id,
                        'name' => $master->display_name ?? $master->user->name ?? 'Мастер',
                        'photo' => $master->avatar ?? '/images/no-photo.svg',
                        'rating' => $master->rating ?? 4.5,
                        'reviews_count' => $master->reviews_count ?? 0,
                        'price_from' => $master->masterServices->min('price') ?? 2000,
                        'services' => $services,
                        'district' => $location->district ?? 'Центральный район',
                        'metro' => $location->metro_station ?? null,
                        'experience_years' => $master->experience_years ?? 1,
                        'is_verified' => $master->is_verified ?? false,
                        'is_premium' => $master->is_premium ?? false,
                    ];
                });
                
            // Если мастеров нет в БД, создаем тестовые данные
            if ($masters->isEmpty()) {
                $masters = collect($this->getTestMasters());
            }
        } catch (\Exception $e) {
            // В случае ошибки БД используем тестовые данные
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