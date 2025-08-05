<?php

namespace App\Application\Http\Controllers;

use App\Domain\Ad\Services\AdProfileService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompareController extends Controller
{
    private AdProfileService $adProfileService;

    public function __construct(AdProfileService $adProfileService)
    {
        $this->adProfileService = $adProfileService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        
        // Тестовые данные для сравнения
        $compareList = [];

        // Получаем счетчики через сервис
        $counts = $this->adProfileService->getUserAdCounts($user);
        
        // Статистика пользователя через сервис
        $userStats = $this->adProfileService->getUserStats($user);

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