<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\CompareController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Главная страница с каталогом мастеров
Route::get('/', [HomeController::class, 'index'])->name('home');

// Дашборд (после авторизации)
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Мастера
Route::prefix('masters')->group(function () {
    Route::get('/create', [MasterController::class, 'create'])->name('masters.create');
    Route::post('/', [MasterController::class, 'store'])->name('masters.store');
    Route::get('/{master}', [MasterController::class, 'show'])->name('masters.show');
    Route::get('/{master}/edit', [MasterController::class, 'edit'])->name('masters.edit')->middleware('auth');
    Route::put('/{master}', [MasterController::class, 'update'])->name('masters.update')->middleware('auth');
    Route::delete('/{master}', [MasterController::class, 'destroy'])->name('masters.destroy')->middleware('auth');
});

// Избранное
Route::middleware('auth')->group(function () {
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites');
    Route::post('/favorites/toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
});

// Сравнение
Route::get('/compare', [CompareController::class, 'index'])->name('compare');
Route::post('/compare/add', [CompareController::class, 'add'])->name('compare.add');
Route::delete('/compare/{master}', [CompareController::class, 'remove'])->name('compare.remove');

// Профиль пользователя
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';