<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| TEST Routes БЕЗ CSRF middleware
|--------------------------------------------------------------------------
| Эти маршруты ТОЛЬКО для тестирования и разработки
| НЕ используются в production
*/

// Тестовый маршрут для проверки исправления черновиков
Route::post('/test-draft-fix', function (Request $request) {
    try {
        $draftService = app(\App\Domain\Ad\Services\DraftService::class);
        $user = \App\Domain\User\Models\User::first(); // Берем первого пользователя для теста
        
        // Извлекаем ID как в основном контроллере  
        $adId = $request->input('ad_id') ?: $request->input('id');
        $adId = $adId ? (int) $adId : null;
        
        \Illuminate\Support\Facades\Log::info('TEST: DraftService::saveOrUpdate', [
            'ad_id_raw' => $request->input('ad_id'),
            'id_raw' => $request->input('id'),
            'ad_id_parsed' => $adId,
            'user_id' => $user->id,
            'title' => $request->input('title')
        ]);
        
        $data = [
            'title' => $request->input('title', 'Test Draft Fix'),
            'description' => $request->input('description', 'Test Description Fix'),
            'specialty' => $request->input('specialty', 'Test Specialty'),
            'phone' => $request->input('phone', '+7 (999) 999-99-99'),
            'status' => 'draft',
            'user_id' => $user->id
        ];
        
        $result = $draftService->saveOrUpdate($data, $user, $adId);
        
        return response()->json([
            'success' => true,
            'message' => 'Draft successfully saved (NO CSRF)',
            'ad_id' => $result->id,
            'was_updated' => $adId && $adId > 0 && !$result->wasRecentlyCreated,
            'recently_created' => $result->wasRecentlyCreated ?? false,
            'total_drafts' => \App\Domain\Ad\Models\Ad::where('status', 'draft')->count()
        ], 200, [], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        return response()->json([
            'success' => false, 
            'error' => 'Error: ' . mb_convert_encoding($e->getMessage(), 'UTF-8', 'UTF-8'),
            'error_class' => get_class($e)
        ], 500);
    }
})->name('test.draft.fix');

// API для подсчета черновиков
Route::get('/test-drafts-count', function () {
    try {
        $count = \App\Domain\Ad\Models\Ad::where('status', 'draft')->count();
        return response()->json(['count' => $count]);
    } catch (Exception $e) {
        return response()->json(['count' => 0, 'error' => $e->getMessage()]);
    }
})->name('test.drafts.count');

// API для получения списка черновиков тестового пользователя
Route::get('/test-user-drafts', function () {
    try {
        $user = \App\Domain\User\Models\User::first(); // Тестовый пользователь
        
        if (!$user) {
            return response()->json(['drafts' => [], 'error' => 'No test user found']);
        }
        
        $drafts = \App\Domain\Ad\Models\Ad::where('user_id', $user->id)
            ->where('status', 'draft')
            ->orderBy('updated_at', 'desc')
            ->get(['id', 'title', 'created_at', 'updated_at'])
            ->map(function ($draft) {
                return [
                    'id' => $draft->id,
                    'title' => $draft->title ?: 'Без названия',
                    'created_at' => $draft->created_at->format('d.m.Y H:i'),
                    'updated_at' => $draft->updated_at->format('d.m.Y H:i')
                ];
            });
        
        return response()->json([
            'drafts' => $drafts,
            'user_id' => $user->id,
            'user_email' => $user->email ?? 'No email'
        ]);
    } catch (Exception $e) {
        return response()->json(['drafts' => [], 'error' => $e->getMessage()]);
    }
})->name('test.user.drafts');