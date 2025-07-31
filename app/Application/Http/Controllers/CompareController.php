<?php

namespace App\Application\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class CompareController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Тестовые данные для сравнения
        $compareList = [];

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

        return Inertia::render('Compare/Index', [
            'compareList' => $compareList,
            'counts' => $counts,
            'userStats' => $userStats,
        ]);
    }

    public function add(Request $request)
    {
        $masterId = $request->master_id;
        
        // Логика добавления в сравнение
        
        return response()->json([
            'success' => true,
            'message' => 'Добавлено к сравнению'
        ]);
    }

    public function remove($masterId)
    {
        // Логика удаления из сравнения
        
        return response()->json([
            'success' => true,
            'message' => 'Удалено из сравнения'
        ]);
    }
}