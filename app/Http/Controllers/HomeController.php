<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Создаем коллекцию карточек
        $cards = collect([
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
            $cards = $cards->filter(function($card) use ($search) {
                return stripos($card['name'], $search) !== false || 
                       stripos($card['specialization'], $search) !== false;
            });
        }

        // Сортировка
        switch ($sort) {
            case 'price_asc':
                $cards = $cards->sortBy('pricePerHour')->values();
                break;
            case 'price_desc':
                $cards = $cards->sortByDesc('pricePerHour')->values();
                break;
            case 'rating':
                $cards = $cards->sortByDesc('rating')->values();
                break;
            default:
                $cards = $cards->values();
                break;
        }

        // Отладочная информация
        \Log::info('Cards count: ' . $cards->count());
        \Log::info('Cards data: ' . $cards->toJson());

        return Inertia::render('Home', [
            'cards' => $cards->toArray(),
            'filters' => [
                'search' => $search,
                'category' => $category,
                'sort' => $sort
            ],
            'cities' => [
                'Москва',
                'Санкт-Петербург', 
                'Новосибирск',
                'Екатеринбург',
                'Нижний Новгород',
                'Казань'
            ]
        ]);
    }
}