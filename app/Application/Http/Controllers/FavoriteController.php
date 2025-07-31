<?php

namespace App\Application\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class FavoriteController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
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

        // Подсчеты для бокового меню (как в ProfileController)
        $allAds = \App\Models\Ad::where('user_id', $user->id)->get();
        $counts = [
            'active' => $allAds->where('status', 'active')->count(),
            'draft' => $allAds->where('status', 'draft')->count(),
            'archived' => $allAds->where('status', 'archived')->count(),
            'waiting_payment' => $allAds->where('status', 'waiting_payment')->count(),
            'old' => $allAds->where('status', 'archived')->count(),
            'bookings' => $user->bookings()->where('status', 'pending')->count(),
            'favorites' => $user->favorites()->count(),
            'unreadMessages' => 0,
        ];
        
        // Статистика пользователя  
        $userStats = [
            'rating' => 0, // Временно 0, пока нет поля rating_overall
            'reviewsCount' => $user->reviews()->count(),
            'balance' => $user->balance ?? 0,
        ];

        return Inertia::render('Favorites/Index', [
            'favorites' => $favorites,
            'counts' => $counts,
            'userStats' => $userStats,
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