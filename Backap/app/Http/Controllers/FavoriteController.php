<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class FavoriteController extends Controller
{
    public function index()
    {
        // Тестовые данные избранного
        $favorites = [
            [
                'id' => 1,
                'name' => 'Анна Иванова',
                'specialization' => 'Классический массаж',
                'age' => 28,
                'height' => 165,
                'rating' => 4.8,
                'reviewsCount' => 142,
                'pricePerHour' => 3000,
                'photo' => '/images/masters/1.jpg',
                'photosCount' => 5,
                'isAvailableNow' => true,
                'phone' => '+79991234567',
                'isFavorite' => true
            ]
        ];

        return Inertia::render('Favorites/Index', [
            'favorites' => $favorites
        ]);
    }

    public function toggle(Request $request)
    {
        $masterId = $request->master_id;
        
        // Здесь должна быть логика добавления/удаления из избранного
        // Например, работа с базой данных
        
        return response()->json([
            'success' => true,
            'message' => 'Добавлено в избранное'
        ]);
    }
}