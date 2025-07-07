<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
|  Laravel Breeze / Auth routes
|--------------------------------------------------------------------------
|  Эти маршруты поставляются пакетом Breeze. При необходимости
|  можно свободно изменять их или переносить в routes/web.php.
*/

/* -------------------------------------------------
 |  Controllers
 * -------------------------------------------------*/
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;

/* -------------------------------------------------
 |  Guests (неавторизованный пользователь)
 * -------------------------------------------------*/
Route::middleware('guest')->group(function () {
    // Регистрация
    Route::get('register',  [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    // Авторизация
    Route::get('login',  [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Сброс пароля
    Route::get ('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get ('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password',        [NewPasswordController::class, 'store'])->name('password.store');
});

/* -------------------------------------------------
 |  Authenticated user
 * -------------------------------------------------*/
Route::middleware('auth')->group(function () {

    // Подтверждение e-mail
    Route::get('verify-email', EmailVerificationPromptController::class)   // ← только один класс!
         ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
         ->middleware(['signed', 'throttle:6,1'])
         ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
         ->middleware('throttle:6,1')
         ->name('verification.send');

    // Подтверждение пароля
    Route::get ('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    // Смена пароля
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // Выход
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
