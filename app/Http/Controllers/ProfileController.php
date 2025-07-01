<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Отображение Dashboard (личного кабинета)
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Загружаем профили мастера с подсчетами
        $profiles = $user->masterProfiles()
            ->with(['services:id,master_profile_id,name,price', 'photos'])
            ->withCount(['bookings', 'reviews']) // 🔥 Убрал views, т.к. используем поле views_count
            ->get()
            ->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'slug' => $profile->slug ?? Str::slug($profile->display_name ?? $profile->name ?? 'profile'),
                    'name' => $profile->display_name ?? $profile->name ?? 'Без названия',
                    'status' => $profile->status ?? 'active', // active, draft, archived
                    'is_active' => $profile->is_active ?? true,
                    'price_from' => $profile->price_from ?? 0,
                    'views_count' => $profile->views_count ?? 0, // 🔥 Используем поле напрямую
                    'photos' => $profile->photos ?? [],
                    'services_list' => $profile->services ? $profile->services->pluck('name')->join(', ') : '',
                    'full_address' => $profile->full_address ?? ($profile->city ?? 'Город не указан') . ', ' . ($profile->address ?? 'адрес не указан'),
                    'rejection_reason' => $profile->rejection_reason,
                    'bookings_count' => $profile->bookings_count ?? 0,
                    'reviews_count' => $profile->reviews_count ?? 0,
                ];
            });
        
        // Подсчеты для бокового меню
        $counts = [
            'profiles' => $user->masterProfiles()->count(),
            'bookings' => $user->bookings()->where('status', 'pending')->count(),
            'favorites' => $user->favorites()->count(),
            'unreadMessages' => 0, // TODO: Реализовать подсчет непрочитанных сообщений
        ];
        
        // Статистика пользователя
        $userStats = [
            'rating' => $user->reviews()->avg('rating_overall') ?: 0,
            'reviewsCount' => $user->reviews()->count(),
            'balance' => $user->balance ?? 0,
        ];
        
        return Inertia::render('Dashboard', [
            'profiles' => $profiles,
            'counts' => $counts,
            'userStats' => $userStats,
            'user'      => $user,
        ]);
    }

    /**
     * Display the user's profile form.
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
     * Update the user's profile information.
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
     * Delete the user's account.
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
    
    /**
     * Переключение статуса профиля мастера
     */
    public function toggleProfile(Request $request, $masterId): RedirectResponse
    {
        $profile = $request->user()->masterProfiles()->findOrFail($masterId);
        $profile->update(['is_active' => !$profile->is_active]);
        
        return back()->with('success', 'Статус анкеты изменен');
    }

    /**
     * Публикация черновика
     */
    public function publishProfile(Request $request, $masterId): RedirectResponse
    {
        $profile = $request->user()->masterProfiles()->findOrFail($masterId);
        $profile->update(['status' => 'active']);
        
        return back()->with('success', 'Анкета опубликована');
    }

    /**
     * Восстановление из архива
     */
    public function restoreProfile(Request $request, $masterId): RedirectResponse
    {
        $profile = $request->user()->masterProfiles()->findOrFail($masterId);
        $profile->update(['status' => 'active']);
        
        return back()->with('success', 'Анкета восстановлена');
    }
}