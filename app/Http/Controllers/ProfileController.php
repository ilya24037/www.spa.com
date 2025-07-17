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
use App\Models\Ad;

class ProfileController extends Controller
{
    /**
     * Отображение Dashboard (личного кабинета)
     */
    public function index(Request $request) {
        return redirect()->route('profile.items.active');
    }
    public function activeItems(Request $request) {
        return $this->renderItemsByStatus($request, 'active', 'Активные');
    }
    public function draftItems(Request $request) {
        return $this->renderItemsByStatus($request, 'draft', 'Черновики');
    }
    public function inactiveItems(Request $request) {
        return $this->renderItemsByStatus($request, 'inactive', 'Неактивные');
    }
    public function oldItems(Request $request) {
        return $this->renderItemsByStatus($request, 'old', 'Старые');
    }
    public function archiveItems(Request $request) {
        return $this->renderItemsByStatus($request, 'archived', 'Архив');
    }

    /**
     * Общий метод для рендеринга объявлений
     */
    private function renderItems($ads)
    {
        $user = auth()->user();
        
        // Преобразуем объявления в формат для карточек
        $profiles = $ads->map(function ($ad) {
            return [
                'id' => $ad->id,
                'slug' => Str::slug($ad->title),
                'name' => $ad->title,
                'status' => $ad->status,
                'is_active' => $ad->status === 'active',
                'price_from' => $ad->price ?? 0,
                'views_count' => 0, // Пока не реализовано
                'photos_count' => 0, // Пока не реализовано
                'avatar' => null, // Пока не реализовано
                'city' => 'Москва', // Из адреса или по умолчанию
                'address' => $ad->address ?? '',
                'district' => $ad->travel_area ?? '',
                'home_visit' => is_array($ad->service_location) ? in_array('client_home', $ad->service_location) : false,
                'availability' => $ad->status === 'active' ? 'Доступен' : 'Недоступен',
                'messages_count' => 0, // Пока не реализовано
                'services_list' => $ad->specialty ?? '',
                'full_address' => $ad->address ?? 'Адрес не указан',
                'rejection_reason' => null,
                'bookings_count' => 0,
                'reviews_count' => 0,
                'description' => $ad->description,
                'formatted_price' => $ad->formatted_price,
                'phone' => $ad->phone,
                'contact_method' => $ad->contact_method,
                'created_at' => $ad->created_at->format('d.m.Y'),
                'updated_at' => $ad->updated_at->format('d.m.Y'),
            ];
        });
        
        // Подсчеты для бокового меню (всех объявлений пользователя)
        $allAds = Ad::where('user_id', $user->id)->get();
        $counts = [
            'active' => $allAds->where('status', 'active')->count(),
            'draft' => $allAds->where('status', 'draft')->count(),
            'archived' => $allAds->where('status', 'archived')->count(),
            'inactive' => $allAds->where('status', 'inactive')->count(),
            'old' => $allAds->where('status', 'old')->count(),
            'bookings' => $user->bookings()->where('status', 'pending')->count(),
            'favorites' => $user->favorites()->count(),
            'unreadMessages' => 0,
        ];
        
        // Статистика пользователя  
        $userStats = [
            'rating' => 0, // 🔥 Временно 0, пока нет поля rating_overall
            'reviewsCount' => $user->reviews()->count(),
            'balance' => $user->balance ?? 0,
        ];
        
        return Inertia::render('Dashboard', [
            'profiles' => $profiles,
            'counts' => $counts,
            'userStats' => $userStats,
        ]);
    }

    private function renderItemsByStatus($request, $status, $title) {
        $user = $request->user();
        $ads = \App\Models\Ad::where('user_id', $user->id)->where('status', $status)->orderBy('created_at', 'desc')->get();
        // Преобразование и подсчёты (оставить как было)
        $profiles = $ads->map(function ($ad) {
            return [
                'id' => $ad->id,
                'slug' => Str::slug($ad->title),
                'name' => $ad->title,
                'status' => $ad->status,
                'is_active' => $ad->status === 'active',
                'price_from' => $ad->price ?? 0,
                'views_count' => 0, // Пока не реализовано
                'photos_count' => 0, // Пока не реализовано
                'avatar' => null, // Пока не реализовано
                'city' => 'Москва', // Из адреса или по умолчанию
                'address' => $ad->address ?? '',
                'district' => $ad->travel_area ?? '',
                'home_visit' => is_array($ad->service_location) ? in_array('client_home', $ad->service_location) : false,
                'availability' => $ad->status === 'active' ? 'Доступен' : 'Недоступен',
                'messages_count' => 0, // Пока не реализовано
                'services_list' => $ad->specialty ?? '',
                'full_address' => $ad->address ?? 'Адрес не указан',
                'rejection_reason' => null,
                'bookings_count' => 0,
                'reviews_count' => 0,
                'description' => $ad->description,
                'formatted_price' => $ad->formatted_price,
                'phone' => $ad->phone,
                'contact_method' => $ad->contact_method,
                'created_at' => $ad->created_at->format('d.m.Y'),
                'updated_at' => $ad->updated_at->format('d.m.Y'),
            ];
        });
        $counts = [
            'active' => \App\Models\Ad::where('user_id', $user->id)->where('status', 'active')->count(),
            'draft' => \App\Models\Ad::where('user_id', $user->id)->where('status', 'draft')->count(),
            'inactive' => \App\Models\Ad::where('user_id', $user->id)->where('status', 'inactive')->count(),
            'old' => \App\Models\Ad::where('user_id', $user->id)->where('status', 'old')->count(),
            'archived' => \App\Models\Ad::where('user_id', $user->id)->where('status', 'archived')->count(),
        ];
        $userStats = [
            'rating' => 0,
            'reviewsCount' => 0,
            'balance' => 0,
        ];
        return Inertia::render('Dashboard', [
            'profiles' => $profiles,
            'counts' => $counts,
            'userStats' => $userStats,
            'activeTab' => $status,
            'title' => $title,
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