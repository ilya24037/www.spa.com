<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\AddItemController; // 🔥 ДОБАВЛЕНО
use App\Http\Controllers\AdController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\MyAdsController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Публичные маршруты
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// CSRF токен для AJAX запросов
Route::get('/csrf-token', function() {
    return response()->json(['token' => csrf_token()]);
});

// DigiSeller/WebMoney платежные маршруты
Route::middleware('auth')->group(function () {
    // Пополнение баланса
    Route::get('/payment/top-up', [PaymentController::class, 'topUpBalance'])->name('payment.top-up');
    Route::post('/payment/top-up', [PaymentController::class, 'createTopUpPayment'])->name('payment.create-top-up');
    
    // Активационные коды
    Route::post('/payment/activate-code', [PaymentController::class, 'activateCode'])->name('payment.activate-code');
});

// WebMoney callback (без middleware auth)
Route::post('/payment/webmoney/callback', [PaymentController::class, 'webmoneyCallback'])->name('payment.webmoney.callback');

// Тестовая страница для проверки навигации
Route::get('/test', function() {
    return Inertia::render('Test');
})->name('test');

// Тестовый маршрут для добавления фотографий
Route::get('/test/add-photos', [TestController::class, 'addPhotos'])->name('test.add-photos');

// Демонстрация компонента ItemCard
Route::get('/demo/item-card', function() {
    return Inertia::render('Demo/ItemCard');
})->name('demo.item-card');
Route::get('/test/add-local-photos', [TestController::class, 'addLocalPhotos'])->name('test.add-local-photos');

// Управление фотографиями мастеров
Route::prefix('masters/{master}/photos')->group(function () {
    Route::post('/', [App\Http\Controllers\MasterPhotoController::class, 'upload'])->name('master.photos.upload');
    Route::delete('/{photo}', [App\Http\Controllers\MasterPhotoController::class, 'destroy'])->name('master.photos.destroy');
    Route::patch('/{photo}/main', [App\Http\Controllers\MasterPhotoController::class, 'setMain'])->name('master.photos.set-main');
    Route::patch('/reorder', [App\Http\Controllers\MasterPhotoController::class, 'reorder'])->name('master.photos.reorder');
});

// Добавление локальных фотографий
Route::post('/master/photos/local', [App\Http\Controllers\MasterPhotoController::class, 'addLocalPhoto'])->name('master.photos.local');

// Публичный маршрут для тестирования загрузки фотографий (без авторизации и CSRF)
Route::post('/masters/{master}/upload/photos/test', function(\Illuminate\Http\Request $request, $masterId) {
    $master = \App\Models\MasterProfile::findOrFail($masterId);
    
    $request->validate([
        'photos' => 'required|array|min:1|max:10',
        'photos.*' => 'required|image|mimes:jpeg,png,webp|max:10240',
    ]);

    try {
        $mediaService = new \App\Services\MediaProcessingService();
        $photos = $mediaService->uploadPhotos($master, $request->file('photos'));

        return response()->json([
            'success' => true,
            'message' => 'Загружено ' . count($photos) . ' фотографий',
            'photos' => collect($photos)->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'filename' => $photo->filename,
                    'original_url' => $photo->original_url,
                    'medium_url' => $photo->medium_url,
                    'thumb_url' => $photo->thumb_url,
                    'is_main' => $photo->is_main,
                    'sort_order' => $photo->sort_order,
                ];
            }),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Ошибка загрузки фотографий: ' . $e->getMessage()
        ], 500);
    }
})->name('master.upload.photos.test');

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
    | Личный кабинет   (как у Avito:  /profile  →  главная страница ЛК)
    |----------------------------------------------------------------------
    */
    // 🔥 ИЗМЕНЕНО: используем метод контроллера вместо анонимной функции
    Route::get('/profile', [ProfileController::class, 'index'])
        ->name('profile.dashboard');

    /*  alias /dashboard  → /profile (необязательный)  */
    Route::redirect('/dashboard', '/profile');
    
    /*  Алиас для маршрута dashboard  */
    Route::get('/dashboard', function() {
        return redirect('/profile');
    })->name('dashboard');
    
    /*
    | Мои объявления (как на Avito)
    */
    Route::prefix('my-ads')->name('my-ads.')->group(function () {
        Route::get('/', [MyAdsController::class, 'index'])->name('index');
        Route::post('/{ad}/pay', [MyAdsController::class, 'pay'])->name('pay');
        Route::post('/{ad}/deactivate', [MyAdsController::class, 'deactivate'])->name('deactivate');
        Route::delete('/{ad}', [MyAdsController::class, 'destroy'])->name('destroy');
        Route::post('/{ad}/publish', [MyAdsController::class, 'publish'])->name('publish');
        Route::post('/{ad}/restore', [MyAdsController::class, 'restore'])->name('restore');
    });

                    /*
                | Система оплаты объявлений
                */
                Route::prefix('payment')->name('payment.')->group(function () {
                    Route::get('/ad/{ad}/select-plan', [PaymentController::class, 'selectPlan'])->name('select-plan');
                    Route::post('/ad/{ad}/checkout', [PaymentController::class, 'checkout'])->name('checkout');
                    Route::post('/{payment}/process', [PaymentController::class, 'process'])->name('process');
                    Route::get('/{payment}/success', [PaymentController::class, 'success'])->name('success');
                    Route::get('/history', [PaymentController::class, 'history'])->name('history');
                    
                    // СБП платежи
                    Route::get('/{payment}/sbp-qr', [PaymentController::class, 'sbpQr'])->name('sbp-qr');
                    Route::get('/{payment}/check-status', [PaymentController::class, 'checkStatus'])->name('check-status');
                });

    /*
    | Профиль пользователя (редактирование / пароль / удаление)
    | Урлы:  /profile/edit, /profile   [PATCH|DELETE]
    */
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
        
        Route::prefix('items')->name('items.')->group(function () {
            Route::get('/active/all', [ProfileController::class, 'activeItems'])->name('active');
            Route::get('/draft/all', [ProfileController::class, 'draftItems'])->name('draft');
            Route::get('/inactive/all', [ProfileController::class, 'inactiveItems'])->name('inactive');
            Route::get('/old/all', [ProfileController::class, 'oldItems'])->name('old');
            Route::get('/archive/all', [ProfileController::class, 'archiveItems'])->name('archive');
        });
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
        
        // 🔥 ДОБАВЛЕНО: маршруты для загрузки медиа
        Route::post('/{master}/upload/avatar', [App\Http\Controllers\MediaUploadController::class, 'uploadAvatar'])->name('upload.avatar');
        Route::post('/{master}/upload/photos', [App\Http\Controllers\MediaUploadController::class, 'uploadPhotos'])->name('upload.photos');
        Route::post('/{master}/upload/video', [App\Http\Controllers\MediaUploadController::class, 'uploadVideo'])->name('upload.video');
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
    | Управление медиафайлами
    */
    Route::prefix('media')->name('media.')->group(function () {
        Route::delete('/photos/{photo}', [App\Http\Controllers\MediaUploadController::class, 'deletePhoto'])->name('photos.delete');
        Route::delete('/videos/{video}', [App\Http\Controllers\MediaUploadController::class, 'deleteVideo'])->name('videos.delete');
        Route::post('/photos/{photo}/set-main', [App\Http\Controllers\MediaUploadController::class, 'setMainPhoto'])->name('photos.set-main');
        Route::post('/photos/reorder', [App\Http\Controllers\MediaUploadController::class, 'reorderPhotos'])->name('photos.reorder');
    });

    /*
    | Размещение объявлений (как у Avito - /additem)
    */
    Route::get('/additem', [AdController::class, 'addItem'])->name('additem');
    Route::post('/additem', [AdController::class, 'store'])->name('additem.store');
    
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

// Медиа с AI-обработкой
Route::prefix('masters/{master}/media')->group(function () {
    Route::post('/upload-ai', [App\Http\Controllers\MasterMediaController::class, 'uploadWithAI'])
        ->name('master.media.upload-ai');
    Route::post('/process-privacy', [App\Http\Controllers\MasterMediaController::class, 'processPrivacy'])
        ->name('master.media.process-privacy');
    Route::patch('/blur-settings', [App\Http\Controllers\MasterMediaController::class, 'updateBlurSettings'])
        ->name('master.media.blur-settings');
});

// Публичные маршруты для медиафайлов
Route::get('/masters/{master}/avatar', function($master) {
    $masterProfile = \App\Models\MasterProfile::findOrFail($master);
    $disk = \Illuminate\Support\Facades\Storage::disk('masters_public');
    $path = "{$masterProfile->folder_name}/avatar.jpg";
    
    if (!$disk->exists($path)) {
        return response()->file(public_path('images/default-avatar.jpg'));
    }
    
    return response()->file($disk->path($path));
})->name('master.avatar');

Route::get('/masters/{master}/avatar/thumb', function($master) {
    $masterProfile = \App\Models\MasterProfile::findOrFail($master);
    $disk = \Illuminate\Support\Facades\Storage::disk('masters_public');
    $path = "{$masterProfile->folder_name}/avatar_thumb.jpg";
    
    if (!$disk->exists($path)) {
        return response()->file(public_path('images/default-avatar.jpg'));
    }
    
    return response()->file($disk->path($path));
})->name('master.avatar.thumb');

// Удалены дублирующиеся маршруты - используются контроллеры ниже

// Маршруты для медиа файлов мастеров
Route::get('/masters/{master}/photo/{filename}', [App\Http\Controllers\MasterMediaController::class, 'photo'])->name('master.photo');
Route::get('/masters/{master}/video/{filename}', [App\Http\Controllers\MasterMediaController::class, 'video'])->name('master.video');
Route::get('/masters/{master}/video/poster/{filename}', [App\Http\Controllers\MasterMediaController::class, 'videoPoster'])->name('master.video.poster');
Route::get('/masters/{master}/avatar', [App\Http\Controllers\MasterMediaController::class, 'avatar'])->name('master.avatar');
Route::get('/masters/{master}/avatar/thumb', [App\Http\Controllers\MasterMediaController::class, 'avatarThumb'])->name('master.avatar.thumb');

// Маршруты для загрузки медиа (только для авторизованных)
Route::middleware('auth')->group(function () {
    Route::post('/masters/{master}/upload/avatar', [App\Http\Controllers\MediaUploadController::class, 'uploadAvatar'])->name('master.upload.avatar');
    Route::post('/masters/{master}/upload/photos', [App\Http\Controllers\MediaUploadController::class, 'uploadPhotos'])->name('master.upload.photos');
    Route::post('/masters/{master}/upload/video', [App\Http\Controllers\MediaUploadController::class, 'uploadVideo'])->name('master.upload.video');
    
    Route::delete('/photos/{photo}', [App\Http\Controllers\MediaUploadController::class, 'deletePhoto'])->name('master.delete.photo');
    Route::delete('/videos/{video}', [App\Http\Controllers\MediaUploadController::class, 'deleteVideo'])->name('master.delete.video');
    
    Route::post('/masters/{master}/photos/reorder', [App\Http\Controllers\MediaUploadController::class, 'reorderPhotos'])->name('master.reorder.photos');
    Route::post('/photos/{photo}/set-main', [App\Http\Controllers\MediaUploadController::class, 'setMainPhoto'])->name('master.set.main.photo');
    
    // Маршруты для объявлений
            // Удалено: ads/create - теперь используем /additem
    Route::post('/ads', [AdController::class, 'store'])->name('ads.store');
    Route::post('/ads/draft', [AdController::class, 'storeDraft'])->name('ads.draft');
    Route::post('/ads/publish', [AdController::class, 'publish'])->name('ads.publish');
    Route::get('/ads/{ad}', [AdController::class, 'show'])->name('ads.show');
    Route::get('/ads/{ad}/edit', [AdController::class, 'edit'])->name('ads.edit');
    
    // Маршруты для черновиков
            Route::get('/draft/{ad}', [AdController::class, 'showDraft'])->name('ads.draft.show');
        // Удаление черновиков теперь через my-ads.destroy как и других объявлений
    Route::get('/ads/{ad}/data', [AdController::class, 'getData'])->name('ads.data');
    Route::put('/ads/{ad}', [AdController::class, 'update'])->name('ads.update');
    Route::delete('/ads/{ad}', [AdController::class, 'destroy'])->name('ads.destroy');
    Route::patch('/ads/{ad}/status', [AdController::class, 'toggleStatus'])->name('ads.toggle-status');
});