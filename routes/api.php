<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\MasterProfile;
use App\Http\Controllers\BookingController;

Route::middleware('api')->group(function () {
    // Тестовый маршрут
    Route::get('/test', function () {
        return response()->json(['status' => 'ok', 'time' => now()]);
    });

    // =================== СИСТЕМА БРОНИРОВАНИЯ ===================
    
    // Публичные роуты (доступны без авторизации)
    Route::prefix('bookings')->group(function () {
        // Получить доступные слоты для мастера и услуги
        Route::get('/available-slots', [BookingController::class, 'availableSlots']);
    });

    // Защищенные роуты (требуют авторизации)
    Route::middleware('auth:sanctum')->prefix('bookings')->group(function () {
        // CRUD операции с бронированиями
        Route::post('/', [BookingController::class, 'store']);           // Создать бронирование
        Route::get('/', [BookingController::class, 'index']);            // Список бронирований
        Route::get('/{booking}', [BookingController::class, 'show']);    // Просмотр бронирования
        
        // Действия с бронированиями
        Route::post('/{booking}/cancel', [BookingController::class, 'cancel']);     // Отменить
        Route::post('/{booking}/confirm', [BookingController::class, 'confirm']);   // Подтвердить (мастер)
        Route::post('/{booking}/complete', [BookingController::class, 'complete']); // Завершить (мастер)
    });

    // =================== МАСТЕРА (существующие) ===================
    
    // Получить информацию о фотографиях мастера
    Route::get('/masters/{master}/photos', function ($masterId) {
        try {
            $master = MasterProfile::find($masterId);
            if (!$master) {
                return response()->json(['error' => 'Мастер не найден'], 404);
            }
            
            return response()->json([
                'master_id' => $master->id,
                'master_name' => $master->display_name,
                'folder_name' => $master->folder_name ?? 'unknown',
                'count' => $master->photos()->count(),
                'photos' => $master->photos()->orderBy('sort_order')->get()->map(function ($photo) use ($master) {
                    return [
                        'id' => $photo->id,
                        'filename' => $photo->filename ?? 'unknown',
                        'url' => $photo->filename ? "/masters/{$master->folder_name}/photo/{$photo->filename}" : null,
                        'thumb_url' => $photo->filename ? "/masters/{$master->folder_name}/photo/thumb_{$photo->filename}" : null,
                        'is_main' => $photo->is_main,
                        'sort_order' => $photo->sort_order,
                        'file_size' => $photo->file_size,
                        'mime_type' => $photo->mime_type
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка сервера: ' . $e->getMessage()
            ], 500);
        }
    });
    
    // Получить список всех мастеров
    Route::get('/masters', function () {
        try {
            $masters = MasterProfile::select('id', 'display_name')
                ->orderBy('display_name')
                ->get()
                ->map(function ($master) {
                    return [
                        'id' => $master->id,
                        'name' => $master->display_name,
                        'folder' => $master->folder_name,
                        'photos_count' => $master->photos()->count()
                    ];
                });
                
            return response()->json($masters);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка сервера: ' . $e->getMessage()
            ], 500);
        }
    });
}); 