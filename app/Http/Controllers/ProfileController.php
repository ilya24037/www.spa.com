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
     * Отображение личного кабинета - сразу показываем активные объявления (как у Avito)
     */
    public function index(Request $request)
    {
        // Используем ту же логику, что и для активных объявлений
        return $this->renderItemsByStatus($request, 'active', 'Мои объявления');
    }
    public function activeItems(Request $request) {
        return $this->renderItemsByStatus($request, 'active', 'Активные');
    }
    public function draftItems(Request $request) {
        return $this->renderItemsByStatus($request, 'draft', 'Черновики');
    }
    public function inactiveItems(Request $request) {
        return $this->renderItemsByStatus($request, 'waiting_payment', 'Ждут действий');
    }
    public function oldItems(Request $request) {
        return $this->renderItemsByStatus($request, 'archived', 'Старые');
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
            // Получаем первое фото из массива photos
            $mainImage = null;
            $photosCount = 0;
            
            // Проверяем, что photos не null и не пустая строка
            if ($ad->photos && $ad->photos !== 'null' && $ad->photos !== '') {
                // Если photos - это JSON строка, декодируем её
                if (is_string($ad->photos)) {
                    $photosArray = json_decode($ad->photos, true);
                    if (is_array($photosArray) && count($photosArray) > 0) {
                        $mainImage = $photosArray[0]['preview'] ?? $photosArray[0]['url'] ?? null;
                        $photosCount = count($photosArray);
                    }
                } elseif (is_array($ad->photos) && count($ad->photos) > 0) {
                    $mainImage = $ad->photos[0]['preview'] ?? $ad->photos[0]['url'] ?? null;
                    $photosCount = count($ad->photos);
                }
            }
            
            // Если нет фото в объявлении, используем тестовое
            if (!$mainImage || $mainImage === 'undefined') {
                $mainImage = '/images/masters/demo-' . (($ad->id % 4) + 1) . '.jpg';
                $photosCount = rand(1, 4);
            }
            
            return [
                'id' => $ad->id,
                'slug' => Str::slug($ad->title),
                'name' => $ad->title,
                'status' => $ad->status,
                'is_active' => $ad->status === 'active',
                'price_from' => $ad->price ?? 0,
                'views_count' => $ad->views_count ?? rand(10, 100),
                'photos_count' => $photosCount,
                'avatar' => $mainImage,
                'main_image' => $mainImage,
                'city' => 'Москва', // Из адреса или по умолчанию
                'address' => $ad->address ?? '',
                'district' => $ad->travel_area ?? '',
                'home_service' => is_array($ad->service_location) ? in_array('client_home', $ad->service_location) : false,
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
                // Дополнительные поля для ItemCard
                'company_name' => 'Массажный салон',
                'expires_at' => now()->addDays(30)->toISOString(), // 30 дней от сегодня
                'new_messages_count' => 0,
                'subscribers_count' => rand(0, 10),
                'favorites_count' => rand(0, 25),
            ];
        });
        
        // Подсчеты для бокового меню (всех объявлений пользователя)
        $allAds = Ad::where('user_id', $user->id)->get();
        $counts = [
            'active' => $allAds->where('status', 'active')->count(),
            'draft' => $allAds->where('status', 'draft')->count(),
            'archived' => $allAds->where('status', 'archived')->count(),
            'waiting_payment' => $allAds->where('status', 'waiting_payment')->count(),
            'old' => $allAds->where('status', 'archived')->count(), // старые = архивные
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
            'activeTab' => 'inactive', // Для главной страницы показываем как inactive
        ]);
    }

    private function renderItemsByStatus($request, $status, $title) {
        $user = $request->user();
        
        // Оптимизируем запрос - добавляем лимит и выбираем только нужные поля
        $ads = \App\Models\Ad::where('user_id', $user->id)
            ->where('status', $status)
            ->select(['id', 'title', 'status', 'price', 'address', 'travel_area', 'specialty', 'description', 'phone', 'contact_method', 'photos', 'service_location', 'created_at', 'updated_at'])
            ->orderBy('created_at', 'desc')
            ->limit(100) // Ограничиваем количество записей
            ->get();
        // Преобразование и подсчёты с правильной обработкой фотографий
        $profiles = $ads->map(function ($ad) {
            // Получаем первое фото из массива photos (используем ту же логику что и в renderItems)
            $mainImage = null;
            $photosCount = 0;
            
            // Проверяем, что photos не null и не пустая строка
            if ($ad->photos && $ad->photos !== 'null' && $ad->photos !== '') {
                // Если photos - это JSON строка, декодируем её
                if (is_string($ad->photos)) {
                    $photosArray = json_decode($ad->photos, true);
                    if (is_array($photosArray) && count($photosArray) > 0) {
                        $mainImage = $photosArray[0]['preview'] ?? $photosArray[0]['url'] ?? null;
                        $photosCount = count($photosArray);
                    }
                } elseif (is_array($ad->photos) && count($ad->photos) > 0) {
                    $mainImage = $ad->photos[0]['preview'] ?? $ad->photos[0]['url'] ?? null;
                    $photosCount = count($ad->photos);
                }
            }
            
            // Если нет фото в объявлении, используем тестовое
            if (!$mainImage || $mainImage === 'undefined') {
                $mainImage = '/images/masters/demo-' . (($ad->id % 4) + 1) . '.jpg';
                $photosCount = rand(1, 4);
            }
            
            return [
                'id' => $ad->id,
                'slug' => Str::slug($ad->title),
                'name' => $ad->title,
                'status' => $ad->status,
                'is_active' => $ad->status === 'active',
                'price_from' => $ad->price ?? 0,
                'views_count' => $ad->views_count ?? rand(10, 100),
                'photos_count' => $photosCount,
                'avatar' => $mainImage,
                'main_image' => $mainImage,
                'city' => 'Москва', // Из адреса или по умолчанию
                'address' => $ad->address ?? '',
                'district' => $ad->travel_area ?? '',
                'home_service' => is_array($ad->service_location) ? in_array('client_home', $ad->service_location) : false,
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
                // Дополнительные поля для ItemCard
                'company_name' => 'Массажный салон',
                'expires_at' => now()->addDays(30)->toISOString(), // 30 дней от сегодня
                'new_messages_count' => 0,
                'subscribers_count' => rand(0, 10),
                'favorites_count' => rand(0, 25),
            ];
        });
        // Оптимизируем подсчеты - используем один запрос с группировкой
        $countsQuery = \App\Models\Ad::where('user_id', $user->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        $counts = [
            'active' => $countsQuery['active'] ?? 0,
            'draft' => $countsQuery['draft'] ?? 0,
            'waiting_payment' => $countsQuery['waiting_payment'] ?? 0,
            'old' => $countsQuery['archived'] ?? 0, // старые = архивные
            'archived' => $countsQuery['archived'] ?? 0,
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
            'activeTab' => $status === 'waiting_payment' ? 'inactive' : $status,
            'title' => $title
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