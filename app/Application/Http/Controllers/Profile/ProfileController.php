<?php

namespace App\Application\Http\Controllers\Profile;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Контроллер для управления профилем пользователя
 * Отвечает за редактирование и удаление профиля
 */
class ProfileController extends Controller
{
    /**
     * Отображение личного кабинета
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        return Inertia::render('Dashboard', [
            'user' => $user->load(['profile', 'masterProfile']),
            'stats' => [
                'bookings_count' => $user->bookings()->count(),
                'reviews_count' => $user->reviews()->count(),
                'favorites_count' => $user->favorites()->count(),
            ],
            'recent_bookings' => $user->bookings()
                ->with(['masterProfile.user', 'service'])
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }

    /**
     * Отображение формы профиля пользователя
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'user' => $request->user()->load('masterProfile')
        ]);
    }

    /**
     * Обновление информации профиля пользователя
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Удаление аккаунта пользователя
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}