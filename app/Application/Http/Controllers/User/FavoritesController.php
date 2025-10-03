<?php

namespace App\Application\Http\Controllers\User;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\User\StoreFavoriteRequest;
use App\Domain\User\Models\UserFavorite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FavoritesController extends Controller
{
    /**
     * Получить список избранного пользователя
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Не авторизован'], 401);
            }
            
            // Получаем избранное с связанными данными
            $favorites = UserFavorite::where('user_id', $user->id)
                ->with(['ad', 'user'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($favorite) {
                    $item = null;
                    $type = 'unknown';

                    // Определяем тип и получаем данные
                    if ($favorite->ad_id) {
                        $type = 'ad';
                        $item = $favorite->ad ? [
                            'id' => $favorite->ad->id,
                            'name' => $favorite->ad->title ?? 'Без названия',
                            'image' => $favorite->ad->main_photo ?? null,
                            'rating' => $favorite->ad->rating ?? 0,
                            'price' => $favorite->ad->price_from ?? 0,
                            'location' => $favorite->ad->district ?? null,
                            'description' => $favorite->ad->description ?? null
                        ] : null;
                    } elseif ($favorite->favorited_user_id) {
                        $type = 'user';
                        $item = $favorite->user ? [
                            'id' => $favorite->user->id,
                            'name' => $favorite->user->name ?? 'Пользователь',
                            'image' => $favorite->user->avatar ?? null,
                            'rating' => $favorite->user->rating ?? 0,
                            'price' => $favorite->user->price_from ?? 0,
                            'location' => $favorite->user->district ?? null,
                            'description' => $favorite->user->description ?? null
                        ] : null;
                    }

                    return [
                        'id' => $favorite->id,
                        'type' => $type,
                        'itemId' => $favorite->ad_id ?? $favorite->favorited_user_id,
                        'addedAt' => $favorite->created_at->toISOString(),
                        'item' => $item
                    ];
                })
                ->filter(function ($favorite) {
                    return $favorite['item'] !== null;
                })
                ->values();
            
            return response()->json([
                'favorites' => $favorites,
                'total' => $favorites->count()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка загрузки избранного: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Добавить в избранное
     */
    public function store(StoreFavoriteRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Не авторизован'], 401);
            }
            
            $type = $request->input('type');
            $itemId = $request->input('item_id');
            
            // Проверяем что элемент еще не в избранном
            $existingFavorite = UserFavorite::where('user_id', $user->id);

            if ($type === 'user') {
                $existingFavorite->where('favorited_user_id', $itemId);
            } elseif ($type === 'ad') {
                $existingFavorite->where('ad_id', $itemId);
            } else {
                return response()->json(['error' => 'Неподдерживаемый тип'], 400);
            }

            if ($existingFavorite->exists()) {
                return response()->json(['error' => 'Уже в избранном'], 409);
            }

            // Создаем запись
            $favoriteData = ['user_id' => $user->id];

            if ($type === 'user') {
                $favoriteData['favorited_user_id'] = $itemId;
            } elseif ($type === 'ad') {
                $favoriteData['ad_id'] = $itemId;
            }

            $favorite = UserFavorite::create($favoriteData);

            // Загружаем связанные данные для ответа
            $favorite->load(['ad', 'user']);
            
            return response()->json([
                'favorite' => [
                    'id' => $favorite->id,
                    'type' => $type,
                    'itemId' => $itemId,
                    'addedAt' => $favorite->created_at->toISOString()
                ]
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка добавления в избранное: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Удалить из избранного
     */
    public function destroy($favoriteId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Не авторизован'], 401);
            }
            
            $favorite = UserFavorite::where('user_id', $user->id)
                ->where('id', $favoriteId)
                ->first();
                
            if (!$favorite) {
                return response()->json(['error' => 'Избранное не найдено'], 404);
            }
            
            $favorite->delete();
            
            return response()->json(['message' => 'Удалено из избранного']);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка удаления: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Очистить все избранное
     */
    public function destroyAll(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Не авторизован'], 401);
            }
            
            $count = UserFavorite::where('user_id', $user->id)->delete();
            
            return response()->json([
                'message' => 'Избранное очищено',
                'deleted_count' => $count
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка очистки: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Очистить избранное по типу
     */
    public function destroyByType($type): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Не авторизован'], 401);
            }
            
            $query = UserFavorite::where('user_id', $user->id);

            if ($type === 'user') {
                $query->whereNotNull('favorited_user_id');
            } elseif ($type === 'ad') {
                $query->whereNotNull('ad_id');
            } else {
                return response()->json(['error' => 'Неподдерживаемый тип'], 400);
            }
            
            $count = $query->delete();
            
            return response()->json([
                'message' => "Избранное типа {$type} очищено",
                'deleted_count' => $count
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка очистки: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Экспорт избранного
     */
    public function export(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Не авторизован'], 401);
            }
            
            $favorites = UserFavorite::where('user_id', $user->id)
                ->with(['ad', 'user'])
                ->get();

            $exportData = [
                'exported_at' => now()->toISOString(),
                'user_id' => $user->id,
                'total_count' => $favorites->count(),
                'favorites' => $favorites->map(function ($favorite) {
                    return [
                        'id' => $favorite->id,
                        'type' => $favorite->ad_id ? 'ad' : 'user',
                        'item_id' => $favorite->ad_id ?? $favorite->favorited_user_id,
                        'added_at' => $favorite->created_at->toISOString()
                    ];
                })
            ];
            
            return response()->json($exportData);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка экспорта: ' . $e->getMessage()
            ], 500);
        }
    }
}