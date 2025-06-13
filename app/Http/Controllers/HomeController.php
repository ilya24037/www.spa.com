<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Тестовые данные мастеров
        $masters = collect([
            [
                'id' => 1,
                'name' => 'Анна Иванова',
                'specialization' => 'Классический массаж',
                'age' => 28,
                'height' => 165,
                'rating' => 4.8,
                'reviewsCount' => 142,
                'pricePerHour' => 3000,
                'photo' => 'https://images.unsplash.com/photo-1594824576852-29b94b89d043?w=400&h=600&fit=crop',
                'photosCount' => 5,
                'isAvailableNow' => true,
                'phone' => '+79991234567',
                'isFavorite' => false,
                'latitude' => 55.7558,
                'longitude' => 37.6173
            ],
            [
                'id' => 2,
                'name' => 'Елена Петрова',
                'specialization' => 'Тайский массаж',
                'age' => 32,
                'height' => 170,
                'rating' => 4.9,
                'reviewsCount' => 98,
                'pricePerHour' => 3500,
                'photo' => 'https://images.unsplash.com/photo-1582750433449-648ed127bb54?w=400&h=600&fit=crop',
                'photosCount' => 7,
                'isAvailableNow' => false,
                'phone' => '+79991234568',
                'isFavorite' => false,
                'latitude' => 55.7612,
                'longitude' => 37.6208
            ],
            [
                'id' => 3,
                'name' => 'Ольга Сидорова',
                'specialization' => 'Спортивный массаж',
                'age' => 26,
                'height' => 168,
                'rating' => 4.7,
                'reviewsCount' => 156,
                'pricePerHour' => 2800,
                'photo' => 'https://images.unsplash.com/photo-1607990281513-2c110a25bd8c?w=400&h=600&fit=crop',
                'photosCount' => 4,
                'isAvailableNow' => true,
                'phone' => '+79991234569',
                'isFavorite' => false,
                'latitude' => 55.7489,
                'longitude' => 37.6217
            ],
            [
                'id' => 4,
                'name' => 'Мария Козлова',
                'specialization' => 'Антицеллюлитный массаж',
                'age' => 30,
                'height' => 172,
                'rating' => 4.6,
                'reviewsCount' => 89,
                'pricePerHour' => 3200,
                'photo' => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=400&h=600&fit=crop',
                'photosCount' => 6,
                'isAvailableNow' => true,
                'phone' => '+79991234570',
                'isFavorite' => false,
                'latitude' => 55.7558,
                'longitude' => 37.6173
            ],
            [
                'id' => 5,
                'name' => 'Татьяна Смирнова',
                'specialization' => 'Релакс массаж',
                'age' => 35,
                'height' => 165,
                'rating' => 4.9,
                'reviewsCount' => 203,
                'pricePerHour' => 2900,
                'photo' => 'https://images.unsplash.com/photo-1612531386530-97d2e4bb048d?w=400&h=600&fit=crop',
                'photosCount' => 8,
                'isAvailableNow' => false,
                'phone' => '+79991234571',
                'isFavorite' => false,
                'latitude' => 55.7489,
                'longitude' => 37.6217
            ],
            [
                'id' => 6,
                'name' => 'Наталья Волкова',
                'specialization' => 'Лечебный массаж',
                'age' => 40,
                'height' => 168,
                'rating' => 4.8,
                'reviewsCount' => 175,
                'pricePerHour' => 3800,
                'photo' => 'https://images.unsplash.com/photo-1551836022-deb4988cc6c0?w=400&h=600&fit=crop',
                'photosCount' => 9,
                'isAvailableNow' => true,
                'phone' => '+79991234572',
                'isFavorite' => false,
                'latitude' => 55.7612,
                'longitude' => 37.6208
            ]
        ]);

        // Фильтрация и поиск
        $search = $request->get('search');
        $category = $request->get('category');
        $sort = $request->get('sort', 'default');

        if ($search) {
            $masters = $masters->filter(function($master) use ($search) {
                return stripos($master['name'], $search) !== false || 
                       stripos($master['specialization'], $search) !== false;
            });
        }

        // Сортировка
        switch ($sort) {
            case 'price_asc':
                $masters = $masters->sortBy('pricePerHour');
                break;
            case 'price_desc':
                $masters = $masters->sortByDesc('pricePerHour');
                break;
            case 'rating':
                $masters = $masters->sortByDesc('rating');
                break;
        }

        // Пагинация (имитация)
        $page = $request->get('page', 1);
        $perPage = 12;
        $total = $masters->count();
        $masters = $masters->forPage($page, $perPage)->values();

        return Inertia::render('Home', [
            'masters' => [
                'data' => $masters->toArray(),
                'current_page' => (int) $page,
                'last_page' => (int) ceil($total / $perPage),
                'total' => $total
            ],
            'filters' => [
                'search' => $search,
                'category' => $category,
                'sort' => $sort
            ],
            'total' => $total,
            'current_page' => (int) $page,
            'last_page' => (int) ceil($total / $perPage),
            'currentDistrict' => 'Москве',
            'categories' => [
                [
                    'id' => 1,
                    'name' => 'Классический массаж',
                    'icon' => '',
                    'children' => [
                        ['id' => 11, 'name' => 'Расслабляющий', 'slug' => 'relaxing', 'services_count' => 45],
                        ['id' => 12, 'name' => 'Лечебный', 'slug' => 'therapeutic', 'services_count' => 32]
                    ]
                ],
                [
                    'id' => 2,
                    'name' => 'Спортивный массаж',
                    'icon' => '',
                    'children' => [
                        ['id' => 21, 'name' => 'Восстановительный', 'slug' => 'recovery', 'services_count' => 28],
                        ['id' => 22, 'name' => 'Подготовительный', 'slug' => 'preparation', 'services_count' => 15]
                    ]
                ],
                [
                    'id' => 3,
                    'name' => 'Тайский массаж',
                    'icon' => '',
                    'children' => [
                        ['id' => 31, 'name' => 'Традиционный', 'slug' => 'traditional', 'services_count' => 22],
                        ['id' => 32, 'name' => 'С маслами', 'slug' => 'oil', 'services_count' => 18]
                    ]
                ],
                [
                    'id' => 4,
                    'name' => 'Антицеллюлитный',
                    'icon' => '',
                    'children' => [
                        ['id' => 41, 'name' => 'Вакуумный', 'slug' => 'vacuum', 'services_count' => 15],
                        ['id' => 42, 'name' => 'Ручной', 'slug' => 'manual', 'services_count' => 25]
                    ]
                ]
            ]
        ]);
    }
}