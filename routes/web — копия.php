<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\SearchController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Публичные маршруты
|--------------------------------------------------------------------------
*/

// Главная страница
Route::get('/', [HomeController::class, 'index'])->name('home');

// Поиск
Route::get('/search', [SearchController::class, 'index'])->name('search');

// Мастера (публичный просмотр)
Route::get('/masters/{master}', [MasterController::class, 'show'])->name('masters.show');

// Сравнение (работает без авторизации через сессию)
Route::prefix('compare')->name('compare.')->group(function () {
    Route::get('/', [CompareController::class, 'index'])->name('index');
    Route::post('/add', [CompareController::class, 'add'])->name('add');
    Route::delete('/{master}', [CompareController::class, 'remove'])->name('remove');
});

/*
|--------------------------------------------------------------------------
| API маршруты (для AJAX запросов)
|--------------------------------------------------------------------------
*/

Route::prefix('api')->group(function () {
    // Публичные API
    Route::get('/masters', [MasterController::class, 'apiIndex']);
    Route::get('/masters/{master}', [MasterController::class, 'apiShow']);
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/search/suggestions', [SearchController::class, 'suggestions']);
    
    // Защищённые API
    Route::middleware('auth')->group(function () {
        Route::get('/user', fn() => auth()->user()->load('masterProfile'));
        Route::get('/favorites', [FavoriteController::class, 'apiIndex']);
        Route::post('/favorites/toggle', [FavoriteController::class, 'toggle']);
        Route::get('/bookings/available-slots', [BookingController::class, 'availableSlots']);
    });
});

/*
|--------------------------------------------------------------------------
| Защищённые маршруты (требуют авторизации)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    
    // Дашборд
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
    
    // Профиль пользователя
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
    
    // Управление профилем мастера
    Route::prefix('masters')->name('masters.')->group(function () {
        Route::get('/create', [MasterController::class, 'create'])->name('create');
        Route::post('/', [MasterController::class, 'store'])->name('store');
        Route::get('/{master}/edit', [MasterController::class, 'edit'])->name('edit');
        Route::put('/{master}', [MasterController::class, 'update'])->name('update');
        Route::delete('/{master}', [MasterController::class, 'destroy'])->name('destroy');
    });
    
    // Избранное
    Route::prefix('favorites')->name('favorites.')->group(function () {
        Route::get('/', [FavoriteController::class, 'index'])->name('index');
        Route::post('/toggle', [FavoriteController::class, 'toggle'])->name('toggle');
    });
    
    // Бронирования
    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [BookingController::class, 'index'])->name('index');
        Route::get('/create', [BookingController::class, 'create'])->name('create'); // ✅ ДОБАВЛЕН
        Route::post('/', [BookingController::class, 'store'])->name('store');
        Route::get('/{booking}', [BookingController::class, 'show'])->name('show');
        Route::post('/{booking}/cancel', [BookingController::class, 'cancel'])->name('cancel');
        Route::post('/{booking}/confirm', [BookingController::class, 'confirm'])->name('confirm');
        Route::post('/{booking}/complete', [BookingController::class, 'complete'])->name('complete'); // ✅ ДОБАВЛЕН
    });
});

/*
|--------------------------------------------------------------------------
| Маршруты аутентификации
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';