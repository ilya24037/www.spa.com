<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Domain\Master\Models\MasterProfile;
use App\Application\Http\Controllers\Booking\BookingController;
use App\Application\Http\Controllers\SearchController;
use App\Application\Http\Controllers\MasterController;
use App\Application\Http\Controllers\FavoriteController;
use App\Application\Http\Controllers\Api\ReviewController;

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

    // Публичные роуты для расписания (не требуют авторизации для просмотра)
    Route::prefix('masters')->group(function () {
        Route::get('/{master}/schedule', [BookingController::class, 'getMasterSchedule']);
        Route::get('/{master}/time-slots', [BookingController::class, 'getTimeSlots']);
    });

    // Защищенные роуты (требуют авторизации)
    Route::middleware('auth:sanctum')->prefix('bookings')->group(function () {
        // CRUD операции с бронированиями
        Route::post('/', [BookingController::class, 'store']);           // Создать бронирование
        Route::get('/', [BookingController::class, 'index']);            // Список бронирований
        Route::get('/{booking}', [BookingController::class, 'show']);    // Просмотр бронирования
        
        // Действия с бронированиями
        Route::patch('/{booking}/cancel', [BookingController::class, 'cancel']);     // Отменить
        Route::patch('/{booking}/confirm', [BookingController::class, 'confirm']);   // Подтвердить (мастер)
        Route::patch('/{booking}/complete', [BookingController::class, 'complete']); // Завершить (мастер)
        Route::patch('/{booking}/reschedule', [BookingController::class, 'reschedule']); // Перенести
        
        // Проверка доступности времени
        Route::post('/check-availability', [BookingController::class, 'checkAvailability']);
        
        // Статистика и дополнительные функции
        Route::get('/stats', [BookingController::class, 'getStats']);
        Route::post('/{booking}/reminder', [BookingController::class, 'sendReminder']);
    });

    // Роуты для пользователей
    Route::middleware('auth:sanctum')->prefix('user')->group(function () {
        Route::get('/bookings', [BookingController::class, 'getUserBookings']); // Бронирования пользователя
        
        // =================== ИЗБРАННОЕ ===================
        Route::prefix('favorites')->group(function () {
            Route::get('/', [\App\Application\Http\Controllers\User\FavoritesController::class, 'index']); // Список избранного
            Route::post('/', [\App\Application\Http\Controllers\User\FavoritesController::class, 'store']); // Добавить в избранное
            Route::delete('/{favorite}', [\App\Application\Http\Controllers\User\FavoritesController::class, 'destroy']); // Удалить из избранного
            Route::delete('/all', [\App\Application\Http\Controllers\User\FavoritesController::class, 'destroyAll']); // Очистить все
            Route::delete('/type/{type}', [\App\Application\Http\Controllers\User\FavoritesController::class, 'destroyByType']); // Очистить по типу
            Route::get('/export', [\App\Application\Http\Controllers\User\FavoritesController::class, 'export']); // Экспорт
        });
        
        // =================== ОТЗЫВЫ ===================
        Route::prefix('reviews')->group(function () {
            Route::get('/', [ReviewController::class, 'index']); // Список отзывов
            Route::get('/{review}', [ReviewController::class, 'show']); // Просмотр отзыва
            Route::post('/', [ReviewController::class, 'store']); // Создать отзыв
            Route::put('/{review}', [ReviewController::class, 'update']); // Обновить отзыв
            Route::delete('/{review}', [ReviewController::class, 'destroy']); // Удалить отзыв
        });
    });

    // =================== МАСТЕРА (существующие) ===================
    
    // Поиск мастеров
    Route::get('/masters/search', [SearchController::class, 'masters']);
    
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
    

    // Публичные API (ранее были в web.php под prefix('api'))
    Route::get('/masters', [MasterController::class, 'apiIndex']);
    Route::get('/masters/{master}', [MasterController::class, 'apiShow'])->whereNumber('master');
    Route::get('/search/autocomplete', [SearchController::class, 'autocomplete']);
    Route::get('/search/suggestions', [SearchController::class, 'suggestions']);
    Route::get('/search/quick', [SearchController::class, 'quick']);
    Route::get('/search/similar/{id}', [SearchController::class, 'similar']);
    Route::post('/search/geo', [SearchController::class, 'geo']);
    Route::get('/search/export', [SearchController::class, 'export']);

    // Защищенные API
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', fn () => auth()->user()->load('masterProfile'));
        // Исправлено: используем контроллер пользователя с существующим методом index
        Route::get(
            '/favorites',
            [\App\Application\Http\Controllers\User\FavoritesController::class, 'index']
        );
        Route::post('/favorites/toggle', [FavoriteController::class, 'toggle']);
        Route::get('/bookings/available-slots', [BookingController::class, 'availableSlots']);
        Route::get('/search/statistics', [SearchController::class, 'statistics'])->middleware('can:viewSearchStatistics');
        
        // =================== ВЕРИФИКАЦИЯ ОБЪЯВЛЕНИЙ ===================
        Route::prefix('ads/{ad}/verification')->group(function () {
            Route::post('/photo', [\App\Application\Http\Controllers\Api\AdVerificationController::class, 'uploadPhoto']);
            Route::post('/video', [\App\Application\Http\Controllers\Api\AdVerificationController::class, 'uploadVideo']);
            Route::get('/status', [\App\Application\Http\Controllers\Api\AdVerificationController::class, 'getStatus']);
            Route::delete('/photo', [\App\Application\Http\Controllers\Api\AdVerificationController::class, 'deletePhoto']);
        });
        Route::get('/verification/instructions', [\App\Application\Http\Controllers\Api\AdVerificationController::class, 'getInstructions']);
    });
}); 