<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\AddItemController; // 🔥 ДОБАВЛЕНО
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Публичные маршруты
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search');

/*  Карточка мастера  →   /masters/<slug>-<id>  */
Route::get('/masters/{slug}-{master}', [MasterController::class, 'show'])
    ->where(['master' => '[0-9]+', 'slug' => '.*'])
    ->name('masters.show');

/*  Сравнение (доступно без авторизации)  */
Route::prefix('compare')->name('compare.')->group(function () {
    Route::get('/', [CompareController::class, 'index'])->name('index');
    Route::post('/add', [CompareController::class, 'add'])->name('add');
    Route::delete('/{master}', [CompareController::class, 'remove'])
        ->whereNumber('master')
        ->name('remove');
});

/*
|--------------------------------------------------------------------------
| API-маршруты (AJAX)
|--------------------------------------------------------------------------
*/
Route::prefix('api')->group(function () {

    /* публичные */
    Route::get('/masters', [MasterController::class, 'apiIndex']);
    Route::get('/masters/{master}', [MasterController::class, 'apiShow'])->whereNumber('master');
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/search/suggestions', [SearchController::class, 'suggestions']);

    /* защищённые */
    Route::middleware('auth')->group(function () {
        Route::get('/user', fn () => auth()->user()->load('masterProfile'));
        Route::get('/favorites', [FavoriteController::class, 'apiIndex']);
        Route::post('/favorites/toggle', [FavoriteController::class, 'toggle']);
        Route::get('/bookings/available-slots', [BookingController::class, 'availableSlots']);
    });
});

/*
|--------------------------------------------------------------------------
| Приватные маршруты (только для авторизованных)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    /*
    |----------------------------------------------------------------------
    | Личный кабинет   (как у Avito:  /profile  →  список объявлений)
    |----------------------------------------------------------------------
    */
    // 🔥 ИЗМЕНЕНО: используем метод контроллера вместо анонимной функции
    Route::get('/profile', [ProfileController::class, 'index'])
        ->name('profile.dashboard');

    /*  alias /dashboard  → /profile (необязательный)  */
    Route::redirect('/dashboard', '/profile');

    /*
    | Профиль пользователя (редактирование / пароль / удаление)
    | Урлы:  /profile/edit, /profile   [PATCH|DELETE]
    */
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/edit',  [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/',    [ProfileController::class, 'update'])->name('update');
        Route::delete('/',   [ProfileController::class, 'destroy'])->name('destroy');
        
        // 🔥 ДОБАВЛЕНО: дополнительные маршруты для Dashboard
        Route::get('/reviews', fn() => Inertia::render('Profile/Reviews'))->name('reviews');
        Route::get('/security', fn() => Inertia::render('Profile/Security'))->name('security');
    });

    /*
    | ЛК мастера  (/masters/create, /masters/{id}/edit, …)
    */
    Route::prefix('masters')->name('masters.')->group(function () {
        Route::get('/create',               [MasterController::class, 'create'])->name('create');
        Route::post('/',                    [MasterController::class, 'store'])->name('store');
        Route::get('/{master}/edit',        [MasterController::class, 'edit'])->whereNumber('master')->name('edit');
        Route::put('/{master}',             [MasterController::class, 'update'])->whereNumber('master')->name('update');
        Route::delete('/{master}',          [MasterController::class, 'destroy'])->whereNumber('master')->name('destroy');
        
        // 🔥 ДОБАВЛЕНО: маршруты для управления статусом профилей
        Route::patch('/{master}/toggle',    [ProfileController::class, 'toggleProfile'])->whereNumber('master')->name('toggle');
        Route::patch('/{master}/publish',   [ProfileController::class, 'publishProfile'])->whereNumber('master')->name('publish');
        Route::patch('/{master}/restore',   [ProfileController::class, 'restoreProfile'])->whereNumber('master')->name('restore');
    });

    /*
    | Избранное
    */
    Route::prefix('favorites')->name('favorites.')->group(function () {
        Route::get('/',        [FavoriteController::class, 'index'])->name('index');
        Route::post('/toggle', [FavoriteController::class, 'toggle'])->name('toggle');
    });

    /*
    | Бронирования
    */
    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/',                    [BookingController::class, 'index'])->name('index');
        Route::get('/create',              [BookingController::class, 'create'])->name('create');
        Route::post('/',                   [BookingController::class, 'store'])->name('store');
        Route::get('/{booking}',           [BookingController::class, 'show'])->name('show');
        Route::post('/{booking}/cancel',   [BookingController::class, 'cancel'])->name('cancel');
        Route::post('/{booking}/confirm',  [BookingController::class, 'confirm'])->name('confirm');
        Route::post('/{booking}/complete', [BookingController::class, 'complete'])->name('complete');
    });

    /*
    | Размещение объявлений (как у Avito)
    */
    Route::prefix('additem')->name('additem.')->group(function () {
        Route::get('/',              [AddItemController::class, 'index'])->name('index');
        Route::get('/massage',       [AddItemController::class, 'massage'])->name('massage');
        Route::post('/massage',      [AddItemController::class, 'storeMassage'])->name('massage.store');
        Route::get('/erotic',        [AddItemController::class, 'erotic'])->name('erotic');
        Route::get('/strip',         [AddItemController::class, 'strip'])->name('strip');
        Route::get('/escort',        [AddItemController::class, 'escort'])->name('escort');
    });
    
    // 🔥 ДОБАВЛЕНО: дополнительные маршруты для полного функционала Dashboard
    Route::get('/messages', fn() => Inertia::render('Messages/Index'))->name('messages.index');
    Route::get('/notifications', fn() => Inertia::render('Notifications/Index'))->name('notifications.index');
    Route::get('/wallet', fn() => Inertia::render('Wallet/Index'))->name('wallet.index');
    Route::get('/services', fn() => Inertia::render('Services/Index'))->name('services.index');
    Route::get('/subscription', fn() => Inertia::render('Subscription/Index'))->name('subscription.index');
    Route::get('/settings', fn() => Inertia::render('Settings/Index'))->name('settings.index');
});

/*
|--------------------------------------------------------------------------
| Аутентификация  (Laravel Breeze / Jetstream)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';