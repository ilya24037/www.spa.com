<?php

namespace App\Application\Http\Controllers\Profile;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'profile' => $user->getProfile(),
                'master_profile' => $user->getMasterProfile(),
            ],
            'stats' => [
                'bookings_count' => $user->getBookingsCount(),
                'active_bookings' => $user->hasActiveBookings(),
                'profile_completion' => $user->hasCompleteProfile() ? 100 : 75,
                // ✅ DDD: Используем новые интеграционные методы
                'reviews_count' => $user->getReceivedReviewsCount(),
                'favorites_count' => $user->getFavoritesCount(),
                'ads_count' => $user->getAdsCount(),
            ],
            'recent_bookings' => $user->getBookings()
                ->take(5)
                ->map(function ($booking) {
                    return [
                        'id' => $booking->id,
                        'status' => $booking->status,
                        'scheduled_at' => $booking->scheduled_at,
                        'service_type' => $booking->service_type,
                        'price' => $booking->price,
                    ];
                }),
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
            'user' => [
                'id' => $request->user()->id,
                'email' => $request->user()->email,
                'role' => $request->user()->role,
                'profile' => $request->user()->getProfile(),
                'master_profile' => $request->user()->getMasterProfile(),
                'can_create_master_profile' => $request->user()->canCreateMasterProfile(),
            ]
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

        auth()->logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}