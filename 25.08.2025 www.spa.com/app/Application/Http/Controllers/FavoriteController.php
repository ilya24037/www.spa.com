<?php

namespace App\Application\Http\Controllers;

use App\Domain\Ad\Services\AdProfileService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FavoriteController extends Controller
{
    private AdProfileService $adProfileService;

    public function __construct(AdProfileService $adProfileService)
    {
        $this->adProfileService = $adProfileService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        
        // ✅ DDD: Используем новый интеграционный метод вместо $user->favorites()
        $favorites = $user->getFavorites();
        $favoritesCount = $user->getFavoritesCount();
        $favoritesStats = $user->getFavoritesStatistics();

        // Получаем счетчики через сервис
        $counts = $this->adProfileService->getUserAdCounts($user);
        
        // Статистика пользователя через сервис
        $userStats = $this->adProfileService->getUserStats($user);

        return Inertia::render('Favorites/Index', [
            'favorites' => $favorites,
            'favoritesCount' => $favoritesCount,
            'favoritesStats' => $favoritesStats,
            'counts' => $counts,
            'userStats' => $userStats,
        ]);
    }

    public function toggle(Request $request)
    {
        $user = $request->user();
        $adId = $request->input('ad_id');
        
        if (!$adId) {
            return response()->json([
                'success' => false,
                'message' => 'ID объявления не указан'
            ], 400);
        }
        
        // ✅ DDD: Используем новый интеграционный метод вместо прямой работы с БД
        $result = $user->toggleFavorite($adId);
        
        if ($result) {
            $isFavorite = $user->hasFavorite($adId);
            $message = $isFavorite ? 'Добавлено в избранное' : 'Удалено из избранного';
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'is_favorite' => $isFavorite,
                'favorites_count' => $user->getFavoritesCount()
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Не удалось обновить избранное'
        ], 500);
    }
}